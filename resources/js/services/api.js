import axios from 'axios';

// Create an axios instance
const api = axios.create({
  baseURL: '/api',
  headers: {
    'Content-Type': 'application/json',
    'Accept': 'application/json'
  }
});

// Request interceptor to add auth token
api.interceptors.request.use(
  (config) => {
    const token = localStorage.getItem('token');
    if (token) {
      config.headers.Authorization = `Bearer ${token}`;
    }
    return config;
  },
  (error) => {
    return Promise.reject(error);
  }
);

// Response interceptor to handle errors
api.interceptors.response.use(
  (response) => {
    return response;
  },
  (error) => {
    if (error.response?.status === 401) {
      // Unauthorized, redirect to login
      localStorage.removeItem('token');
      window.location.href = '/login';
    }
    
    return Promise.reject(error);
  }
);

// Auth endpoints
export const auth = {
  login: (credentials) => api.post('/auth/login', credentials),
  logout: () => api.post('/auth/logout'),
  user: () => api.get('/auth/user')
};

// Customer endpoints
export const customers = {
  list: (params) => api.get('/customers', { params }),
  show: (id) => api.get(`/customers/${id}`),
  create: (data) => api.post('/customers', data),
  update: (id, data) => api.put(`/customers/${id}`, data),
  delete: (id) => api.delete(`/customers/${id}`),
  recharge: (id, data) => api.post(`/customers/${id}/recharge`, data),
  suspend: (id) => api.post(`/customers/${id}/suspend`),
  enable: (id) => api.post(`/customers/${id}/enable`)
};

// Invoice endpoints
export const invoices = {
  list: (params) => api.get('/invoices', { params }),
  show: (id) => api.get(`/invoices/${id}`),
  create: (data) => api.post('/invoices', data),
  update: (id, data) => api.put(`/invoices/${id}`, data),
  delete: (id) => api.delete(`/invoices/${id}`),
  markAsPaid: (id) => api.post(`/invoices/${id}/mark-as-paid`)
};

// Payment endpoints
export const payments = {
  list: (params) => api.get('/payments', { params }),
  process: (data) => api.post('/payments', data)
};

// Ticket endpoints
export const tickets = {
  list: (params) => api.get('/tickets', { params }),
  show: (id) => api.get(`/tickets/${id}`),
  create: (data) => api.post('/tickets', data),
  update: (id, data) => api.put(`/tickets/${id}`, data),
  delete: (id) => api.delete(`/tickets/${id}`),
  addComment: (id, data) => api.post(`/tickets/${id}/comments`, data),
  assign: (id, data) => api.post(`/tickets/${id}/assign`, data),
  updateStatus: (id, data) => api.post(`/tickets/${id}/status`, data)
};

// Report endpoints
export const reports = {
  revenue: (params) => api.get('/reports/revenue', { params }),
  customers: (params) => api.get('/reports/customers', { params }),
  tickets: (params) => api.get('/reports/tickets', { params }),
  exportRevenue: (params) => api.get('/reports/revenue/export', { params, responseType: 'blob' }),
  exportCustomers: (params) => api.get('/reports/customers/export', { params, responseType: 'blob' }),
  exportTickets: (params) => api.get('/reports/tickets/export', { params, responseType: 'blob' })
};

export default api;