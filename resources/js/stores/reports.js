import { defineStore } from 'pinia';
import axios from 'axios';

export const useReportsStore = defineStore('reports', {
  state: () => ({
    revenueStats: null,
    customerStats: null,
    ticketStats: null,
    loading: false,
    error: null
  }),

  getters: {
    getRevenueStats: (state) => state.revenueStats,
    getCustomerStats: (state) => state.customerStats,
    getTicketStats: (state) => state.ticketStats,
    getLoading: (state) => state.loading,
    getError: (state) => state.error
  },

  actions: {
    async fetchRevenueStats(params = {}) {
      this.loading = true;
      this.error = null;
      
      try {
        const response = await axios.get('/api/reports/revenue', { params });
        
        this.revenueStats = response.data.data;
        
        return response.data;
      } catch (error) {
        this.error = error.response?.data?.message || 'Failed to fetch revenue stats';
        throw error;
      } finally {
        this.loading = false;
      }
    },

    async fetchCustomerStats(params = {}) {
      this.loading = true;
      this.error = null;
      
      try {
        const response = await axios.get('/api/reports/customers', { params });
        
        this.customerStats = response.data.data;
        
        return response.data;
      } catch (error) {
        this.error = error.response?.data?.message || 'Failed to fetch customer stats';
        throw error;
      } finally {
        this.loading = false;
      }
    },

    async fetchTicketStats(params = {}) {
      this.loading = true;
      this.error = null;
      
      try {
        const response = await axios.get('/api/reports/tickets', { params });
        
        this.ticketStats = response.data.data;
        
        return response.data;
      } catch (error) {
        this.error = error.response?.data?.message || 'Failed to fetch ticket stats';
        throw error;
      } finally {
        this.loading = false;
      }
    },

    async generateRevenueReport(params = {}) {
      this.loading = true;
      this.error = null;
      
      try {
        const response = await axios.get('/api/reports/revenue/export', { params, responseType: 'blob' });
        
        // Create a download link for the report
        const url = window.URL.createObjectURL(new Blob([response.data]));
        const link = document.createElement('a');
        link.href = url;
        link.setAttribute('download', 'revenue-report.xlsx');
        document.body.appendChild(link);
        link.click();
        link.remove();
        
        return response.data;
      } catch (error) {
        this.error = error.response?.data?.message || 'Failed to generate revenue report';
        throw error;
      } finally {
        this.loading = false;
      }
    },

    async generateCustomerReport(params = {}) {
      this.loading = true;
      this.error = null;
      
      try {
        const response = await axios.get('/api/reports/customers/export', { params, responseType: 'blob' });
        
        // Create a download link for the report
        const url = window.URL.createObjectURL(new Blob([response.data]));
        const link = document.createElement('a');
        link.href = url;
        link.setAttribute('download', 'customer-report.xlsx');
        document.body.appendChild(link);
        link.click();
        link.remove();
        
        return response.data;
      } catch (error) {
        this.error = error.response?.data?.message || 'Failed to generate customer report';
        throw error;
      } finally {
        this.loading = false;
      }
    },

    async generateTicketReport(params = {}) {
      this.loading = true;
      this.error = null;
      
      try {
        const response = await axios.get('/api/reports/tickets/export', { params, responseType: 'blob' });
        
        // Create a download link for the report
        const url = window.URL.createObjectURL(new Blob([response.data]));
        const link = document.createElement('a');
        link.href = url;
        link.setAttribute('download', 'ticket-report.xlsx');
        document.body.appendChild(link);
        link.click();
        link.remove();
        
        return response.data;
      } catch (error) {
        this.error = error.response?.data?.message || 'Failed to generate ticket report';
        throw error;
      } finally {
        this.loading = false;
      }
    }
  }
});