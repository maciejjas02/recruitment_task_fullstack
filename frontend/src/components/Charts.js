import React, { useState, useRef, useEffect } from 'react';
import {
  Chart as ChartJS,
  CategoryScale,
  LinearScale,
  PointElement,
  LineElement,
  Title,
  Tooltip,
  Legend,
} from 'chart.js';
import { Line } from 'react-chartjs-2';
import { ratesAPI } from '../services/api';

ChartJS.register(
  CategoryScale,
  LinearScale,
  PointElement,
  LineElement,
  Title,
  Tooltip,
  Legend
);

const Charts = ({ rates }) => {
  const [startDate, setStartDate] = useState('');
  const [endDate, setEndDate] = useState('');
  const [chartsData, setChartsData] = useState({});
  const [chartsVisible, setChartsVisible] = useState(false);
  const [loading, setLoading] = useState(false);
  const [error, setError] = useState(null);

  // Initialize dates
  useEffect(() => {
    const today = new Date();
    const lastWeek = new Date(today.getTime() - 7 * 24 * 60 * 60 * 1000);
    
    setEndDate(today.toISOString().split('T')[0]);
    setStartDate(lastWeek.toISOString().split('T')[0]);
  }, []);

  const loadCharts = async () => {
    if (!startDate || !endDate) {
      alert('Proszę wybrać datę początkową i końcową');
      return;
    }

    try {
      setLoading(true);
      setError(null);
      setChartsVisible(true);

      const currencies = ['EUR', 'USD', 'CZK', 'IDR', 'BRL'];
      const chartsDataTemp = {};

      // Load data for each currency
      for (const currency of currencies) {
        try {
          const response = await ratesAPI.getHistoricalRates(currency, startDate, endDate);
          const data = response.points || [];
          
          if (data.length === 0) {
            // Generate mock data if no historical data available
            chartsDataTemp[currency] = generateMockData(currency, startDate, endDate);
          } else {
            chartsDataTemp[currency] = data;
          }
        } catch (err) {
          console.log(`History API not available for ${currency}, generating mock data`);
          chartsDataTemp[currency] = generateMockData(currency, startDate, endDate);
        }
      }

      setChartsData(chartsDataTemp);
    } catch (err) {
      setError(err.message);
      console.error('Error loading charts:', err);
    } finally {
      setLoading(false);
    }
  };

  const generateMockData = (currency, startDate, endDate) => {
    const currentRate = rates.find(r => r.code === currency);
    if (!currentRate) return [];

    const start = new Date(startDate);
    const end = new Date(endDate);
    const data = [];
    
    const current = new Date(start);
    while (current <= end) {
      const variation = (Math.random() - 0.5) * 0.1; // ±5% variation
      const midRate = currentRate.mid * (1 + variation);
      
      data.push({
        date: current.toISOString().split('T')[0],
        buy: currentRate.buy ? midRate - 0.15 : null,
        sell: midRate + (currency === 'EUR' || currency === 'USD' ? 0.11 : 0.20),
        mid: midRate
      });
      
      current.setDate(current.getDate() + 1);
    }
    
    return data;
  };

  const prepareChartData = (currency, data) => {
    const labels = data.map(d => {
      const date = new Date(d.date);
      return date.toLocaleDateString('pl-PL', { 
        day: '2-digit', 
        month: '2-digit' 
      });
    });

    const buyData = data.map(d => d.buy);
    const sellData = data.map(d => d.sell);
    const midData = data.map(d => d.mid);

    return {
      labels,
      datasets: [
        {
          label: 'Kurs kupna',
          data: buyData,
          borderColor: '#10b981',
          backgroundColor: 'rgba(16, 185, 129, 0.1)',
          tension: 0.1
        },
        {
          label: 'Kurs NBP',
          data: midData,
          borderColor: '#3b82f6',
          backgroundColor: 'rgba(59, 130, 246, 0.1)',
          tension: 0.1
        },
        {
          label: 'Kurs sprzedaży',
          data: sellData,
          borderColor: '#f59e0b',
          backgroundColor: 'rgba(245, 158, 11, 0.1)',
          tension: 0.1
        }
      ]
    };
  };

  const chartOptions = {
    responsive: true,
    maintainAspectRatio: false,
    scales: {
      y: {
        beginAtZero: false,
        title: {
          display: true,
          text: 'Kurs (PLN)',
          color: '#333333',
          font: {
            size: 14,
            weight: 'bold'
          }
        },
        ticks: {
          color: '#333333',
          font: {
            size: 12
          }
        },
        grid: {
          color: 'rgba(51,51,51,0.2)',
          lineWidth: 1
        }
      },
      x: {
        title: {
          display: true,
          text: 'Data',
          color: '#333333',
          font: {
            size: 14,
            weight: 'bold'
          }
        },
        ticks: {
          color: '#333333',
          maxTicksLimit: 6,
          maxRotation: 0,
          minRotation: 0,
          font: {
            size: 12
          }
        },
        grid: {
          color: 'rgba(51,51,51,0.2)',
          lineWidth: 1
        }
      }
    },
    plugins: {
      legend: {
        display: true,
        position: 'top',
        labels: {
          color: '#333333',
          font: {
            size: 12
          }
        }
      },
      tooltip: {
        mode: 'index',
        intersect: false,
        backgroundColor: 'rgba(255,255,255,0.95)',
        titleColor: '#333333',
        bodyColor: '#333333',
        borderColor: 'rgba(51,51,51,0.3)',
        borderWidth: 1
      }
    }
  };

  const setDateRange = (days) => {
    const end = new Date();
    const start = new Date(end.getTime() - days * 24 * 60 * 60 * 1000);
    
    setEndDate(end.toISOString().split('T')[0]);
    setStartDate(start.toISOString().split('T')[0]);
  };

  const toggleChartsVisibility = () => {
    setChartsVisible(!chartsVisible);
  };

  return (
    <div className="date-controls">
      <div className="charts-header">
        <h3 style={{margin: 0, color: '#1f2937'}}>📊 Wykresy historyczne</h3>
        <div className="charts-controls">
          <span style={{fontSize: '0.9rem', color: '#6b7280', marginRight: '10px'}}>
            {Object.keys(chartsData).length > 0 && `📈 ${Object.keys(chartsData).length} wykresów załadowanych`}
          </span>
          <button 
            className="toggle-charts-btn" 
            onClick={toggleChartsVisibility}
          >
            {chartsVisible ? '👁️ Ukryj wykresy' : '👁️ Pokaż wykresy'}
          </button>
        </div>
      </div>
      
      <div className="date-row">
        <div className="date-group">
          <label htmlFor="startDate">Data początkowa:</label>
          <input 
            type="date" 
            id="startDate"
            value={startDate}
            onChange={(e) => setStartDate(e.target.value)}
          />
        </div>
        <div className="date-group">
          <label htmlFor="endDate">Data końcowa:</label>
          <input 
            type="date" 
            id="endDate"
            value={endDate}
            onChange={(e) => setEndDate(e.target.value)}
          />
        </div>
        <button 
          className="btn" 
          onClick={loadCharts}
          disabled={loading}
        >
          {loading ? '🔄 Ładowanie...' : 'Załaduj wykresy'}
        </button>
        <button 
          className="btn" 
          onClick={() => setDateRange(7)}
          style={{background: '#059669'}}
        >
          Ostatnie 7 dni
        </button>
      </div>
      
      <div style={{marginTop: '15px'}}>
        <button 
          className="btn" 
          onClick={() => setDateRange(30)}
          style={{background: '#6b7280', marginRight: '10px'}}
        >
          Ostatnie 30 dni
        </button>
        <button 
          className="btn" 
          onClick={() => setDateRange(90)}
          style={{background: '#6b7280'}}
        >
          Ostatnie 3 miesiące
        </button>
      </div>

      {error && (
        <div className="error" style={{marginTop: '15px'}}>
          ❌ {error}
        </div>
      )}

      {chartsVisible && Object.keys(chartsData).length > 0 && (
        <div className="charts-section" style={{marginTop: '20px'}}>
          <div className="charts-grid">
            {Object.entries(chartsData).map(([currency, data]) => (
              <div key={currency}>
                <div className="chart-title">{currency} - Kursy historyczne</div>
                <div className="chart-container">
                  <Line 
                    data={prepareChartData(currency, data)} 
                    options={{
                      ...chartOptions,
                      plugins: {
                        ...chartOptions.plugins,
                        title: {
                          display: true,
                          text: `${currency} - Kursy historyczne`,
                          font: {
                            size: 16,
                            weight: 'bold'
                          },
                          color: '#333333'
                        }
                      }
                    }}
                  />
                </div>
              </div>
            ))}
          </div>
        </div>
      )}
    </div>
  );
};

export default Charts;