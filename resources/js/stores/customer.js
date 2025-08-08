import { defineStore } from 'pinia';
import axios from 'axios';

export const useCustomerStore = defineStore('customer', {
  state: () => ({
    customers: [],
    customer: null,
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
    getCustomers: (state) => state.customers,
    getCustomer: (state) => state.customer,
    getLoading: (state) => state.loading,
    getError: (state) => state.error,
    getPagination: (state) => state.pagination
  },

  actions: {
    async fetchCustomers(params = {}) {
      this.loading = true;
      this.error = null;
      
      try {
        const response = await axios.get('/api/customers', { params });
        
        this.customers = response.data.data;
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
        this.error = error.response?.data?.message || 'Failed to fetch customers';
        throw error;
      } finally {
        this.loading = false;
      }
    },

    async fetchCustomer(id) {
      this.loading = true;
      this.error = null;
      
      try {
        const response = await axios.get(`/api/customers/${id}`);
        
        this.customer = response.data.data;
        
        return response.data;
      } catch (error) {
        this.error = error.response?.data?.message || 'Failed to fetch customer';
        throw error;
      } finally {
        this.loading = false;
      }
    },

    async createCustomer(customerData) {
      this.loading = true;
      this.error = null;
      
      try {
        const response = await axios.post('/api/customers', customerData);
        
        // Add the new customer to the list
        this.customers.unshift(response.data.data);
        
        return response.data;
      } catch (error) {
        this.error = error.response?.data?.message || 'Failed to create customer';
        throw error;
      } finally {
        this.loading = false;
      }
    },

    async updateCustomer(id, customerData) {
      this.loading = true;
      this.error = null;
      
      try {
        const response = await axios.put(`/api/customers/${id}`, customerData);
        
        // Update the customer in the list
        const index = this.customers.findIndex(customer => customer.id === id);
        if (index !== -1) {
          this.customers[index] = response.data.data;
        }
        
        // If we're currently viewing this customer, update it
        if (this.customer && this.customer.id === id) {
          this.customer = response.data.data;
        }
        
        return response.data;
      } catch (error) {
        this.error = error.response?.data?.message || 'Failed to update customer';
        throw error;
      } finally {
        this.loading = false;
      }
    },

    async deleteCustomer(id) {
      this.loading = true;
      this.error = null;
      
      try {
        await axios.delete(`/api/customers/${id}`);
        
        // Remove the customer from the list
        this.customers = this.customers.filter(customer => customer.id !== id);
        
        return true;
      } catch (error) {
        this.error = error.response?.data?.message || 'Failed to delete customer';
        throw error;
      } finally {
        this.loading = false;
      }
    },

    async rechargeCustomer(id, rechargeData) {
      this.loading = true;
      this.error = null;
      
      try {
        const response = await axios.post(`/api/customers/${id}/recharge`, rechargeData);
        
        // Update the customer if we're currently viewing it
        if (this.customer && this.customer.id === id) {
          this.customer = response.data.data;
        }
        
        return response.data;
      } catch (error) {
        this.error = error.response?.data?.message || 'Failed to recharge customer';
        throw error;
      } finally {
        this.loading = false;
      }
    },

    async suspendCustomer(id) {
      this.loading = true;
      this.error = null;
      
      try {
        const response = await axios.post(`/api/customers/${id}/suspend`);
        
        // Update the customer in the list
        const index = this.customers.findIndex(customer => customer.id === id);
        if (index !== -1) {
          this.customers[index] = response.data.data;
        }
        
        // If we're currently viewing this customer, update it
        if (this.customer && this.customer.id === id) {
          this.customer = response.data.data;
        }
        
        return response.data;
      } catch (error) {
        this.error = error.response?.data?.message || 'Failed to suspend customer';
        throw error;
      } finally {
        this.loading = false;
      }
    },

    async enableCustomer(id) {
      this.loading = true;
      this.error = null;
      
      try {
        const response = await axios.post(`/api/customers/${id}/enable`);
        
        // Update the customer in the list
        const index = this.customers.findIndex(customer => customer.id === id);
        if (index !== -1) {
          this.customers[index] = response.data.data;
        }
        
        // If we're currently viewing this customer, update it
        if (this.customer && this.customer.id === id) {
          this.customer = response.data.data;
        }
        
        return response.data;
      } catch (error) {
        this.error = error.response?.data?.message || 'Failed to enable customer';
        throw error;
      } finally {
        this.loading = false;
      }
    }
  }
});