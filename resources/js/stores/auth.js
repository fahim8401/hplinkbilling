import { defineStore } from 'pinia';
import axios from 'axios';

export const useAuthStore = defineStore('auth', {
  state: () => ({
    user: null,
    token: localStorage.getItem('token') || null,
    isAuthenticated: false
  }),

  getters: {
    getUser: (state) => state.user,
    getToken: (state) => state.token,
    getIsAuthenticated: (state) => state.isAuthenticated
  },

  actions: {
    async login(credentials) {
      try {
        const response = await axios.post('/api/auth/login', credentials);
        
        this.token = response.data.token;
        this.user = response.data.user;
        this.isAuthenticated = true;
        
        // Store token in localStorage
        localStorage.setItem('token', this.token);
        
        // Set default authorization header
        axios.defaults.headers.common['Authorization'] = `Bearer ${this.token}`;
        
        return response.data;
      } catch (error) {
        throw error.response.data;
      }
    },

    async logout() {
      try {
        await axios.post('/api/auth/logout');
      } catch (error) {
        // Even if logout fails, we still want to clear local state
        console.error('Logout error:', error);
      }
      
      this.user = null;
      this.token = null;
      this.isAuthenticated = false;
      
      // Remove token from localStorage
      localStorage.removeItem('token');
      
      // Remove default authorization header
      delete axios.defaults.headers.common['Authorization'];
    },

    async fetchUser() {
      if (!this.token) {
        return;
      }

      try {
        const response = await axios.get('/api/auth/user');
        this.user = response.data;
        this.isAuthenticated = true;
      } catch (error) {
        // If fetching user fails, clear auth state
        this.logout();
      }
    },

    initialize() {
      if (this.token) {
        axios.defaults.headers.common['Authorization'] = `Bearer ${this.token}`;
        this.fetchUser();
      }
    }
  }
});