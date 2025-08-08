import { defineStore } from 'pinia';
import axios from 'axios';

export const useBillingStore = defineStore('billing', {
  state: () => ({
    invoices: [],
    invoice: null,
    payments: [],
    loading: false,
    error: null,
    pagination: {
      current_page: 1,
      last_page: 1,
      per_page: 10,
      total: 0,
      from: 1,
      to: 10
    }
  }),

  getters: {
    getInvoices: (state) => state.invoices,
    getInvoice: (state) => state.invoice,
    getPayments: (state) => state.payments,
    getLoading: (state) => state.loading,
    getError: (state) => state.error,
    getPagination: (state) => state.pagination
  },

  actions: {
    async fetchInvoices(params = {}) {
      this.loading = true;
      this.error = null;
      
      try {
        const response = await axios.get('/api/invoices', { params });
        
        this.invoices = response.data.data;
        this.pagination = {
          current_page: response.data.current_page,
          last_page: response.data.last_page,
          per_page: response.data.per_page,
          total: response.data.total,
          from: response.data.from,
          to: response.data.to
        };
        
        return response.data;
      } catch (error) {
        this.error = error.response?.data?.message || 'Failed to fetch invoices';
        throw error;
      } finally {
        this.loading = false;
      }
    },

    async fetchInvoice(id) {
      this.loading = true;
      this.error = null;
      
      try {
        const response = await axios.get(`/api/invoices/${id}`);
        
        this.invoice = response.data.data;
        
        return response.data;
      } catch (error) {
        this.error = error.response?.data?.message || 'Failed to fetch invoice';
        throw error;
      } finally {
        this.loading = false;
      }
    },

    async fetchPayments(params = {}) {
      this.loading = true;
      this.error = null;
      
      try {
        const response = await axios.get('/api/payments', { params });
        
        this.payments = response.data.data;
        
        return response.data;
      } catch (error) {
        this.error = error.response?.data?.message || 'Failed to fetch payments';
        throw error;
      } finally {
        this.loading = false;
      }
    },

    async createInvoice(invoiceData) {
      this.loading = true;
      this.error = null;
      
      try {
        const response = await axios.post('/api/invoices', invoiceData);
        
        // Add the new invoice to the list
        this.invoices.unshift(response.data.data);
        
        return response.data;
      } catch (error) {
        this.error = error.response?.data?.message || 'Failed to create invoice';
        throw error;
      } finally {
        this.loading = false;
      }
    },

    async updateInvoice(id, invoiceData) {
      this.loading = true;
      this.error = null;
      
      try {
        const response = await axios.put(`/api/invoices/${id}`, invoiceData);
        
        // Update the invoice in the list
        const index = this.invoices.findIndex(invoice => invoice.id === id);
        if (index !== -1) {
          this.invoices[index] = response.data.data;
        }
        
        // If we're currently viewing this invoice, update it
        if (this.invoice && this.invoice.id === id) {
          this.invoice = response.data.data;
        }
        
        return response.data;
      } catch (error) {
        this.error = error.response?.data?.message || 'Failed to update invoice';
        throw error;
      } finally {
        this.loading = false;
      }
    },

    async deleteInvoice(id) {
      this.loading = true;
      this.error = null;
      
      try {
        await axios.delete(`/api/invoices/${id}`);
        
        // Remove the invoice from the list
        this.invoices = this.invoices.filter(invoice => invoice.id !== id);
        
        return true;
      } catch (error) {
        this.error = error.response?.data?.message || 'Failed to delete invoice';
        throw error;
      } finally {
        this.loading = false;
      }
    },

    async markAsPaid(id) {
      this.loading = true;
      this.error = null;
      
      try {
        const response = await axios.post(`/api/invoices/${id}/mark-as-paid`);
        
        // Update the invoice in the list
        const index = this.invoices.findIndex(invoice => invoice.id === id);
        if (index !== -1) {
          this.invoices[index] = response.data.data;
        }
        
        // If we're currently viewing this invoice, update it
        if (this.invoice && this.invoice.id === id) {
          this.invoice = response.data.data;
        }
        
        return response.data;
      } catch (error) {
        this.error = error.response?.data?.message || 'Failed to mark invoice as paid';
        throw error;
      } finally {
        this.loading = false;
      }
    },

    async processPayment(paymentData) {
      this.loading = true;
      this.error = null;
      
      try {
        const response = await axios.post('/api/payments', paymentData);
        
        // Add the new payment to the list
        this.payments.unshift(response.data.data);
        
        // Update the corresponding invoice if it exists in the list
        const invoiceIndex = this.invoices.findIndex(invoice => invoice.id === response.data.data.invoice_id);
        if (invoiceIndex !== -1) {
          this.invoices[invoiceIndex] = response.data.data.invoice;
        }
        
        // If we're currently viewing this invoice, update it
        if (this.invoice && this.invoice.id === response.data.data.invoice_id) {
          this.invoice = response.data.data.invoice;
        }
        
        return response.data;
      } catch (error) {
        this.error = error.response?.data?.message || 'Failed to process payment';
        throw error;
      } finally {
        this.loading = false;
      }
    }
  }
});