import React, { useState, useEffect } from 'react';
import './App.css';
import CurrencyCard from './components/CurrencyCard';
import HistoricalRates from './components/HistoricalRates';
import Charts from './components/Charts';
import { ratesAPI } from './services/api';

function App() {
  const [rates, setRates] = useState([]);
  const [loading, setLoading] = useState(true);
  const [error, setError] = useState(null);
  const [animationsActive, setAnimationsActive] = useState(true);

  // Load current rates on component mount
  useEffect(() => {
    loadCurrentRates();
  }, []);

  const loadCurrentRates = async () => {
    try {
      setLoading(true);
      setError(null);
      const data = await ratesAPI.getCurrentRates();
      setRates(data);
    } catch (err) {
      setError(err.message);
      console.error('Error loading rates:', err);
    } finally {
      setLoading(false);
    }
  };

  const toggleAnimations = () => {
    setAnimationsActive(!animationsActive);
  };

  const formatTime = () => {
    return new Date().toLocaleTimeString('pl-PL');
  };

  if (loading) {
    return (
      <div className="app">
        <div className="container">
          <div className="loading">
            🔄 Pobieranie aktualnych kursów z API NBP...
          </div>
        </div>
      </div>
    );
  }

  if (error) {
    return (
      <div className="app">
        <div className="container">
          <div className="error">
            ❌ {error}
            <br />
            <button onClick={loadCurrentRates} className="btn" style={{marginTop: '10px'}}>
              🔄 Spróbuj ponownie
            </button>
          </div>
        </div>
      </div>
    );
  }

  return (
    <div className={`app ${animationsActive ? '' : 'animations-paused'}`}>
      <button 
        className="toggle-animation-btn" 
        onClick={toggleAnimations}
        id="animationToggle"
      >
        {animationsActive ? '⏸️ Zatrzymaj animacje' : '▶️ Wznów animacje'}
      </button>
      
      <div className="container">
        <div className="header">
          <h1 className="title">Kantor Pro</h1>
          <p style={{color: '#6b7280', margin: 0}}>
            Profesjonalne kursy wymiany walut
          </p>
        </div>
        
        <div className="status success">
          ✅ Kursy walut załadowane pomyślnie! 
          <span style={{marginLeft: '10px', fontSize: '0.9rem', opacity: 0.8}}>
            Ostatnia aktualizacja: {formatTime()}
          </span>
        </div>
        
        {/* Current Rates */}
        <div className="rates-grid">
          {rates.map((rate) => (
            <CurrencyCard key={rate.code} rate={rate} />
          ))}
        </div>

        {/* Historical Rates Component */}
        <HistoricalRates />

        {/* Charts Component */}
        <Charts rates={rates} />

        <div className="footer">
          <p style={{color: '#6b7280', fontSize: '0.9rem', textAlign: 'center'}}>
            System Kantorowy • Dane z API NBP • Ostatnia aktualizacja: {formatTime()}
          </p>
        </div>
      </div>
    </div>
  );
}

export default App;