<template>
  <div>
    <div class="flex justify-between items-center mb-6">
      <h1 class="text-2xl font-bold text-gray-900">Billing</h1>
      <div class="flex space-x-2">
        <button class="form-button-secondary">
          Generate Invoices
        </button>
        <button class="form-button">
          Process Payments
        </button>
      </div>
    </div>
    
    <div class="grid grid-cols-1 lg:grid-cols-4 gap-6 mb-6">
      <div class="card">
        <div class="card-body">
          <h3 class="text-lg font-medium text-gray-900">Total Invoices</h3>
          <p class="text-3xl font-bold text-primary-600">{{ billingStats.totalInvoices }}</p>
        </div>
      </div>
      
      <div class="card">
        <div class="card-body">
          <h3 class="text-lg font-medium text-gray-900">Paid Invoices</h3>
          <p class="text-3xl font-bold text-green-600">{{ billingStats.paidInvoices }}</p>
        </div>
      </div>
      
      <div class="card">
        <div class="card-body">
          <h3 class="text-lg font-medium text-gray-900">Pending Invoices</h3>
          <p class="text-3xl font-bold text-yellow-600">{{ billingStats.pendingInvoices }}</p>
        </div>
      </div>
      
      <div class="card">
        <div class="card-body">
          <h3 class="text-lg font-medium text-gray-900">Overdue Invoices</h3>
          <p class="text-3xl font-bold text-red-600">{{ billingStats.overdueInvoices }}</p>
        </div>
      </div>
    </div>
    
    <div class="card mb-6">
      <div class="card-header">
        <h2 class="text-lg font-medium text-gray-900">Invoice List</h2>
      </div>
      <div class="card-body">
        <div class="grid grid-cols-1 md:grid-cols-4 gap-4 mb-4">
          <div>
            <label class="form-label">Search</label>
            <input type="text" class="form-input" placeholder="Search invoices..." v-model="searchQuery">
          </div>
          <div>
            <label class="form-label">Status</label>
            <select class="form-input" v-model="statusFilter">
              <option value="">All Statuses</option>
              <option value="paid">Paid</option>
              <option value="pending">Pending</option>
              <option value="overdue">Overdue</option>
              <option value="cancelled">Cancelled</option>
            </select>
          </div>
          <div>
            <label class="form-label">Date Range</label>
            <select class="form-input" v-model="dateRangeFilter">
              <option value="">All Dates</option>
              <option value="this_month">This Month</option>
              <option value="last_month">Last Month</option>
              <option value="this_year">This Year</option>
            </select>
          </div>
          <div class="flex items-end">
            <button class="form-button w-full" @click="applyFilters">
              Apply Filters
            </button>
          </div>
        </div>
        
        <div class="overflow-x-auto">
          <table class="table">
            <thead class="table-head">
              <tr>
                <th class="table-header-cell">Invoice #</th>
                <th class="table-header-cell">Customer</th>
                <th class="table-header-cell">Amount</th>
                <th class="table-header-cell">Status</th>
                <th class="table-header-cell">Billing Date</th>
                <th class="table-header-cell">Due Date</th>
                <th class="table-header-cell">Actions</th>
              </tr>
            </thead>
            <tbody class="table-body">
              <tr v-for="invoice in invoices" :key="invoice.id">
                <td class="table-cell">{{ invoice.invoice_number }}</td>
                <td class="table-cell">{{ invoice.customer?.name }}</td>
                <td class="table-cell">{{ formatCurrency(invoice.total_amount) }}</td>
                <td class="table-cell">
                  <span class="status-badge" :class="`status-${invoice.status}`">
                    {{ invoice.status }}
                  </span>
                </td>
                <td class="table-cell">{{ formatDate(invoice.billing_date) }}</td>
                <td class="table-cell">{{ formatDate(invoice.due_date) }}</td>
                <td class="table-cell">
                  <button class="text-primary-600 hover:text-primary-900 mr-2">
                    View
                  </button>
                  <button 
                    v-if="invoice.status === 'pending' || invoice.status === 'overdue'"
                    class="text-green-600 hover:text-green-900"
                    @click="markAsPaid(invoice)"
                  >
                    Mark Paid
                  </button>
                </td>
              </tr>
            </tbody>
          </table>
        </div>
        
        <div class="mt-4 flex items-center justify-between">
          <div class="text-sm text-gray-700">
            Showing {{ pagination.from }} to {{ pagination.to }} of {{ pagination.total }} results
          </div>
          <div class="flex space-x-2">
            <button 
              class="form-button-secondary" 
              :disabled="!pagination.prev_page_url"
              @click="fetchInvoices(pagination.current_page - 1)"
            >
              Previous
            </button>
            <button 
              class="form-button-secondary" 
              :disabled="!pagination.next_page_url"
              @click="fetchInvoices(pagination.current_page + 1)"
            >
              Next
            </button>
          </div>
        </div>
      </div>
    </div>
    
    <div class="card">
      <div class="card-header">
        <h2 class="text-lg font-medium text-gray-900">Payment History</h2>
      </div>
      <div class="card-body">
        <div class="overflow-x-auto">
          <table class="table">
            <thead class="table-head">
              <tr>
                <th class="table-header-cell">Payment ID</th>
                <th class="table-header-cell">Customer</th>
                <th class="table-header-cell">Amount</th>
                <th class="table-header-cell">Method</th>
                <th class="table-header-cell">Date</th>
                <th class="table-header-cell">Status</th>
              </tr>
            </thead>
            <tbody class="table-body">
              <tr v-for="payment in payments" :key="payment.id">
                <td class="table-cell">{{ payment.id }}</td>
                <td class="table-cell">{{ payment.customer?.name }}</td>
                <td class="table-cell">{{ formatCurrency(payment.amount) }}</td>
                <td class="table-cell capitalize">{{ payment.payment_method }}</td>
                <td class="table-cell">{{ formatDate(payment.payment_date) }}</td>
                <td class="table-cell">
                  <span class="status-badge" :class="`status-${payment.status}`">
                    {{ payment.status }}
                  </span>
                </td>
              </tr>
            </tbody>
          </table>
        </div>
      </div>
    </div>
  </div>
</template>

<script>
import { ref, onMounted } from 'vue';
import axios from 'axios';

export default {
  name: 'Billing',
  setup() {
    const billingStats = ref({
      totalInvoices: 1240,
      paidInvoices: 1180,
      pendingInvoices: 42,
      overdueInvoices: 18
    });
    
    const invoices = ref([
      {
        id: 1,
        invoice_number: 'INV-2025-001',
        customer: { name: 'John Doe' },
        total_amount: 500,
        status: 'paid',
        billing_date: '2025-08-10',
        due_date: '2025-08-25'
      },
      {
        id: 2,
        invoice_number: 'INV-2025-002',
        customer: { name: 'Jane Smith' },
        total_amount: 800,
        status: 'pending',
        billing_date: '2025-08-10',
        due_date: '2025-08-25'
      },
      {
        id: 3,
        invoice_number: 'INV-2025-003',
        customer: { name: 'Robert Johnson' },
        total_amount: 1200,
        status: 'overdue',
        billing_date: '2025-07-10',
        due_date: '2025-07-25'
      }
    ]);
    
    const payments = ref([
      {
        id: 1,
        customer: { name: 'John Doe' },
        amount: 500,
        payment_method: 'online',
        payment_date: '2025-08-15',
        status: 'completed'
      },
      {
        id: 2,
        customer: { name: 'Jane Smith' },
        amount: 800,
        payment_method: 'cash',
        payment_date: '2025-08-14',
        status: 'completed'
      },
      {
        id: 3,
        customer: { name: 'Robert Johnson' },
        amount: 1200,
        payment_method: 'bank_transfer',
        payment_date: '2025-07-20',
        status: 'completed'
      }
    ]);
    
    const searchQuery = ref('');
    const statusFilter = ref('');
    const dateRangeFilter = ref('');
    
    const pagination = ref({
      current_page: 1,
      last_page: 2,
      per_page: 10,
      total: 30,
      from: 1,
      to: 10,
      prev_page_url: null,
      next_page_url: '/api/invoices?page=2'
    });
    
    const formatDate = (dateString) => {
      if (!dateString) return '';
      return new Date(dateString).toLocaleDateString();
    };
    
    const formatCurrency = (amount) => {
      return new Intl.NumberFormat('en-US', {
        style: 'currency',
        currency: 'BDT'
      }).format(amount);
    };
    
    const fetchInvoices = async (page = 1) => {
      try {
        // In a real application, you would fetch this data from your API
        // For now, we'll use mock data
        
        console.log(`Fetching invoices for page: ${page}`);
      } catch (error) {
        console.error('Error fetching invoices:', error);
      }
    };
    
    const applyFilters = () => {
      // In a real application, you would apply filters to the API request
      console.log('Applying filters:', {
        search: searchQuery.value,
        status: statusFilter.value,
        dateRange: dateRangeFilter.value
      });
      
      // For now, just refetch invoices
      fetchInvoices();
    };
    
    const markAsPaid = (invoice) => {
      // In a real application, you would make an API call to mark the invoice as paid
      console.log(`Marking invoice ${invoice.id} as paid`);
      
      // For now, just update the status locally
      invoice.status = 'paid';
    };
    
    onMounted(() => {
      fetchInvoices();
    });
    
    return {
      billingStats,
      invoices,
      payments,
      searchQuery,
      statusFilter,
      dateRangeFilter,
      pagination,
      formatDate,
      formatCurrency,
      fetchInvoices,
      applyFilters,
      markAsPaid
    };
  }
};
</script>