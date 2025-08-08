import { defineStore } from 'pinia';
import axios from 'axios';

export const useSupportStore = defineStore('support', {
  state: () => ({
    tickets: [],
    ticket: null,
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
    getTickets: (state) => state.tickets,
    getTicket: (state) => state.ticket,
    getLoading: (state) => state.loading,
    getError: (state) => state.error,
    getPagination: (state) => state.pagination
  },

  actions: {
    async fetchTickets(params = {}) {
      this.loading = true;
      this.error = null;
      
      try {
        const response = await axios.get('/api/tickets', { params });
        
        this.tickets = response.data.data;
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
        this.error = error.response?.data?.message || 'Failed to fetch tickets';
        throw error;
      } finally {
        this.loading = false;
      }
    },

    async fetchTicket(id) {
      this.loading = true;
      this.error = null;
      
      try {
        const response = await axios.get(`/api/tickets/${id}`);
        
        this.ticket = response.data.data;
        
        return response.data;
      } catch (error) {
        this.error = error.response?.data?.message || 'Failed to fetch ticket';
        throw error;
      } finally {
        this.loading = false;
      }
    },

    async createTicket(ticketData) {
      this.loading = true;
      this.error = null;
      
      try {
        const response = await axios.post('/api/tickets', ticketData);
        
        // Add the new ticket to the list
        this.tickets.unshift(response.data.data);
        
        return response.data;
      } catch (error) {
        this.error = error.response?.data?.message || 'Failed to create ticket';
        throw error;
      } finally {
        this.loading = false;
      }
    },

    async updateTicket(id, ticketData) {
      this.loading = true;
      this.error = null;
      
      try {
        const response = await axios.put(`/api/tickets/${id}`, ticketData);
        
        // Update the ticket in the list
        const index = this.tickets.findIndex(ticket => ticket.id === id);
        if (index !== -1) {
          this.tickets[index] = response.data.data;
        }
        
        // If we're currently viewing this ticket, update it
        if (this.ticket && this.ticket.id === id) {
          this.ticket = response.data.data;
        }
        
        return response.data;
      } catch (error) {
        this.error = error.response?.data?.message || 'Failed to update ticket';
        throw error;
      } finally {
        this.loading = false;
      }
    },

    async deleteTicket(id) {
      this.loading = true;
      this.error = null;
      
      try {
        await axios.delete(`/api/tickets/${id}`);
        
        // Remove the ticket from the list
        this.tickets = this.tickets.filter(ticket => ticket.id !== id);
        
        return true;
      } catch (error) {
        this.error = error.response?.data?.message || 'Failed to delete ticket';
        throw error;
      } finally {
        this.loading = false;
      }
    },

    async addComment(ticketId, commentData) {
      this.loading = true;
      this.error = null;
      
      try {
        const response = await axios.post(`/api/tickets/${ticketId}/comments`, commentData);
        
        // Update the ticket if we're currently viewing it
        if (this.ticket && this.ticket.id === ticketId) {
          // Add the new comment to the ticket's comments
          this.ticket.comments = this.ticket.comments || [];
          this.ticket.comments.push(response.data.data);
        }
        
        return response.data;
      } catch (error) {
        this.error = error.response?.data?.message || 'Failed to add comment';
        throw error;
      } finally {
        this.loading = false;
      }
    },

    async assignTicket(ticketId, assignData) {
      this.loading = true;
      this.error = null;
      
      try {
        const response = await axios.post(`/api/tickets/${ticketId}/assign`, assignData);
        
        // Update the ticket in the list
        const index = this.tickets.findIndex(ticket => ticket.id === ticketId);
        if (index !== -1) {
          this.tickets[index] = response.data.data;
        }
        
        // If we're currently viewing this ticket, update it
        if (this.ticket && this.ticket.id === ticketId) {
          this.ticket = response.data.data;
        }
        
        return response.data;
      } catch (error) {
        this.error = error.response?.data?.message || 'Failed to assign ticket';
        throw error;
      } finally {
        this.loading = false;
      }
    },

    async updateTicketStatus(ticketId, statusData) {
      this.loading = true;
      this.error = null;
      
      try {
        const response = await axios.post(`/api/tickets/${ticketId}/status`, statusData);
        
        // Update the ticket in the list
        const index = this.tickets.findIndex(ticket => ticket.id === ticketId);
        if (index !== -1) {
          this.tickets[index] = response.data.data;
        }
        
        // If we're currently viewing this ticket, update it
        if (this.ticket && this.ticket.id === ticketId) {
          this.ticket = response.data.data;
        }
        
        return response.data;
      } catch (error) {
        this.error = error.response?.data?.message || 'Failed to update ticket status';
        throw error;
      } finally {
        this.loading = false;
      }
    }
  }
});