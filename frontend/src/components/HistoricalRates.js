import React, { useState } from 'react';
import { ratesAPI } from '../services/api';
import CurrencyCard from './CurrencyCard';

const HistoricalRates = () => {
  const [historicalRates, setHistoricalRates] = useState([]);
  const [selectedDate, setSelectedDate] = useState('');
  const [loading, setLoading] = useState(false);
  const [error, setError] = useState(null);

  // Initialize with yesterday's date
  React.useEffect(() => {
    const yesterday = new Date();
    yesterday.setDate(yesterday.getDate() - 1);
    setSelectedDate(yesterday.toISOString().split('T')[0]);
  }, []);

  const loadHistoricalRates = async () => {
    if (!selectedDate) {
      alert('Proszę wybrać datę');
      return;
    }

    try {
      setLoading(true);
      setError(null);
      const data = await ratesAPI.getRatesForDate(selectedDate);
      setHistoricalRates(data);
    } catch (err) {
      setError(err.message);
      console.error('Error loading historical rates:', err);
    } finally {
      setLoading(false);
    }
  };

  const setHistoricalDate = (daysBack) => {
    const date = new Date();
    date.setDate(date.getDate() - daysBack);
    const dateString = date.toISOString().split('T')[0];
    setSelectedDate(dateString);
  };

  const formatDisplayDate = (dateString) => {
    if (!dateString) return '';
    const date = new Date(dateString);
    return date.toLocaleDateString('pl-PL', {
      weekday: 'long',
      year: 'numeric',
      month: 'long',
      day: 'numeric'
    });
  };

  return (
    <div className="historical-rates">
      <h3 style={{margin: '0 0 15px 0', color: '#1f2937'}}>
        📅 Kursy z wybranego dnia
      </h3>
      
      <div className="date-row">
        <div className="date-group">
          <label htmlFor="historicalDate">Wybierz datę:</label>
          <input 
            type="date" 
            id="historicalDate"
            value={selectedDate}
            onChange={(e) => setSelectedDate(e.target.value)}
          />
        </div>
        <button 
          className="btn" 
          onClick={loadHistoricalRates}
          disabled={loading}
          style={{alignSelf: 'end'}}
        >
          {loading ? '🔄 Ładowanie...' : '📊 Pokaż kursy z tego dnia'}
        </button>
      </div>
      
      <div style={{marginTop: '15px'}}>
        <button 
          className="btn" 
          onClick={() => setHistoricalDate(1)}
          style={{background: '#6b7280', marginRight: '10px', padding: '6px 12px', fontSize: '0.9rem'}}
        >
          Wczoraj
        </button>
        <button 
          className="btn" 
          onClick={() => setHistoricalDate(7)}
          style={{background: '#6b7280', marginRight: '10px', padding: '6px 12px', fontSize: '0.9rem'}}
        >
          Tydzień temu
        </button>
        <button 
          className="btn" 
          onClick={() => setHistoricalDate(30)}
          style={{background: '#6b7280', padding: '6px 12px', fontSize: '0.9rem'}}
        >
          Miesiąc temu
        </button>
      </div>

      {error && (
        <div className="error" style={{marginTop: '15px'}}>
          ❌ {error}
        </div>
      )}

      {historicalRates.length > 0 && (
        <div style={{marginTop: '20px'}}>
          <h4 style={{color: '#1f2937', marginBottom: '15px'}}>
            Kursy z dnia: {formatDisplayDate(selectedDate)}
          </h4>
          <div className="rates-grid">
            {historicalRates.map((rate) => (
              <CurrencyCard key={rate.code} rate={rate} />
            ))}
          </div>
        </div>
      )}
    </div>
  );
};

export default HistoricalRates;