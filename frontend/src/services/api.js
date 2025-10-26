import axios from 'axios';

const API_BASE = process.env.NODE_ENV === 'production' ? '' : 'http://localhost';

const api = axios.create({
  baseURL: API_BASE,
  timeout: 10000,
  headers: {
    'Content-Type': 'application/json',
  },
});

export const ratesAPI = {
  // Get current rates for all currencies
  getCurrentRates: async (date = null) => {
    try {
      const params = date ? { date } : {};
      const response = await api.get('/api/rates', { params });
      return response.data;
    } catch (error) {
      console.error('Error fetching current rates:', error);
      throw new Error('Nie udało się pobrać aktualnych kursów');
    }
  },

  // Get historical rates for specific currency
  getHistoricalRates: async (currency, startDate, endDate) => {
    try {
      const response = await api.get(`/api/rates/${currency}/history`, {
        params: { startDate, endDate }
      });
      return response.data;
    } catch (error) {
      console.error(`Error fetching historical rates for ${currency}:`, error);
      throw new Error(`Nie udało się pobrać historii dla ${currency}`);
    }
  },

  // Get rates for specific date
  getRatesForDate: async (date) => {
    try {
      const response = await api.get('/api/rates', {
        params: { date }
      });
      return response.data;
    } catch (error) {
      console.error('Error fetching rates for date:', error);
      throw new Error('Nie udało się pobrać kursów z wybranej daty');
    }
  }
};

export default api;