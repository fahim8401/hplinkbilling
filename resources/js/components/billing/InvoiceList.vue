<template>
  <div>
    <div class="flex justify-between items-center mb-6">
      <h1 class="text-2xl font-bold text-gray-900">Invoices</h1>
      <button class="form-button">
        Generate Invoices
      </button>
    </div>
    
    <div class="card mb-6">
      <div class="card-body">
        <div class="grid grid-cols-1 md:grid-cols-4 gap-4">
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
            </select>
          </div>
          <div>
            <label class="form-label">Date Range</label>
            <input type="date" class="form-input" v-model="startDate">
          </div>
          <div class="flex items-end">
            <button class="form-button w-full" @click="applyFilters">
              Apply Filters
            </button>
          </div>
        </div>
      </div>
    </div>
    
    <div class="card">
      <div class="card-header">
        <h2 class="text-lg font-medium text-gray-900">Invoice List</h2>
      </div>
      <div class="card-body">
        <div class="overflow-x-auto">
          <table class="table">
            <thead class="table-head">
              <tr>
                <th class="table-header-cell">Invoice #</th>
                <th class="table-header-cell">Customer</th>
                <th class="table-header-cell">Amount</th>
                <th class="table-header-cell">Status</th>
                <th class="table-header-cell">Issue Date</th>
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
                  <button class="text-green-600 hover:text-green-900">
                    Mark as Paid
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
  </div>
</template>

<script>
import { ref, onMounted } from 'vue';
import { formatCurrency, formatDate } from '../../utils/helpers';

export default {
  name: 'InvoiceList',
  setup() {
    const invoices = ref([
      {
        id: 1,
        invoice_number: 'INV-001',
        customer: { name: 'John Doe' },
        total_amount: 500,
        status: 'paid',
        billing_date: '2025-07-10',
        due_date: '2025-08-10'
      },
      {
        id: 2,
        invoice_number: 'INV-002',
        customer: { name: 'Jane Smith' },
        total_amount: 800,
        status: 'pending',
        billing_date: '2025-07-15',
        due_date: '2025-08-15'
      },
      {
        id: 3,
        invoice_number: 'INV-003',
        customer: { name: 'Robert Johnson' },
        total_amount: 1200,
        status: 'overdue',
        billing_date: '2025-06-01',
        due_date: '2025-07-01'
      }
    ]);
    
    const searchQuery = ref('');
    const statusFilter = ref('');
    const startDate = ref('');
    
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
        start_date: startDate.value
      });
      
      // For now, just refetch invoices
      fetchInvoices();
    };
    
    onMounted(() => {
      fetchInvoices();
    });
    
    return {
      invoices,
      searchQuery,
      statusFilter,
      startDate,
      pagination,
      formatCurrency,
      formatDate,
      fetchInvoices,
      applyFilters
    };
  }
};
</script>