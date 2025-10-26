import React from 'react';

const CURRENCY_INFO = {
  'EUR': { name: 'Euro', namepl: 'Euro', flag: '🇪🇺', flagAlt: 'EU' },
  'USD': { name: 'US Dollar', namepl: 'Dolar amerykański', flag: '🇺🇸', flagAlt: 'US' },
  'CZK': { name: 'Czech Koruna', namepl: 'Korona czeska', flag: '🇨🇿', flagAlt: 'CZ' },
  'IDR': { name: 'Indonesian Rupiah', namepl: 'Rupia indonezyjska', flag: '🇮🇩', flagAlt: 'ID' },
  'BRL': { name: 'Brazilian Real', namepl: 'Real brazylijski', flag: '🇧🇷', flagAlt: 'BR' }
};

const CurrencyCard = ({ rate }) => {
  const currencyInfo = CURRENCY_INFO[rate.code] || { 
    name: rate.code, 
    namepl: rate.code, 
    flag: '🌍', 
    flagAlt: rate.code 
  };

  const formatRate = (value) => {
    if (value === null || value === undefined) return 'N/A';
    return Number(value).toFixed(4);
  };

  const getBuyClass = (value) => {
    return value === null ? 'rate-value-na' : 'rate-value';
  };

  return (
    <div className="rate-card">
      <div className="currency-header">
        <div className="currency-flag">
          <span className="flag">{currencyInfo.flag}</span>
          <span className="flag-alt">{currencyInfo.flagAlt}</span>
        </div>
        <div className="currency-info">
          <div className="currency-code">{rate.code}</div>
          <div className="currency-name">{currencyInfo.namepl}</div>
        </div>
      </div>
      
      <div className="rates-row">
        <div className="rate-item buy">
          <div className="rate-label">KUPNO</div>
          <div className={getBuyClass(rate.buy)}>
            {formatRate(rate.buy)}
          </div>
        </div>
        
        <div className="rate-item mid">
          <div className="rate-label">NBP</div>
          <div className="rate-value">
            {formatRate(rate.mid)}
          </div>
        </div>
        
        <div className="rate-item sell">
          <div className="rate-label">SPRZEDAŻ</div>
          <div className="rate-value">
            {formatRate(rate.sell)}
          </div>
        </div>
      </div>
      
      {rate.date && (
        <div className="rate-date">
          Kurs z dnia: {rate.date}
        </div>
      )}
    </div>
  );
};

export default CurrencyCard;