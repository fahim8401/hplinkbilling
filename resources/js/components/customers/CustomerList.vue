<template>
  <div>
    <div class="flex justify-between items-center mb-6">
      <h1 class="text-2xl font-bold text-gray-900">Customers</h1>
      <button class="form-button">
        Add Customer
      </button>
    </div>
    
    <div class="card mb-6">
      <div class="card-body">
        <div class="grid grid-cols-1 md:grid-cols-4 gap-4">
          <div>
            <label class="form-label">Search</label>
            <input type="text" class="form-input" placeholder="Search customers..." v-model="searchQuery">
          </div>
          <div>
            <label class="form-label">Status</label>
            <select class="form-input" v-model="statusFilter">
              <option value="">All Statuses</option>
              <option value="active">Active</option>
              <option value="inactive">Inactive</option>
              <option value="suspended">Suspended</option>
              <option value="expired">Expired</option>
            </select>
          </div>
          <div>
            <label class="form-label">Package</label>
            <select class="form-input" v-model="packageFilter">
              <option value="">All Packages</option>
              <option value="1">5Mbps Unlimited</option>
              <option value="2">10Mbps Unlimited</option>
              <option value="3">20Mbps Unlimited</option>
            </select>
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
        <h2 class="text-lg font-medium text-gray-900">Customer List</h2>
      </div>
      <div class="card-body">
        <div class="overflow-x-auto">
          <table class="table">
            <thead class="table-head">
              <tr>
                <th class="table-header-cell">Name</th>
                <th class="table-header-cell">Username</th>
                <th class="table-header-cell">Package</th>
                <th class="table-header-cell">Status</th>
                <th class="table-header-cell">Expiry Date</th>
                <th class="table-header-cell">Actions</th>
              </tr>
            </thead>
            <tbody class="table-body">
              <tr v-for="customer in customers" :key="customer.id">
                <td class="table-cell">{{ customer.name }}</td>
                <td class="table-cell">{{ customer.username }}</td>
                <td class="table-cell">{{ customer.package?.name }}</td>
                <td class="table-cell">
                  <span class="status-badge" :class="`status-${customer.status}`">
                    {{ customer.status }}
                  </span>
                </td>
                <td class="table-cell">{{ formatDate(customer.expiry_date) }}</td>
                <td class="table-cell">
                  <router-link :to="`/customers/${customer.id}`" class="text-primary-600 hover:text-primary-900">
                    View
                  </router-link>
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
              @click="fetchCustomers(pagination.current_page - 1)"
            >
              Previous
            </button>
            <button 
              class="form-button-secondary" 
              :disabled="!pagination.next_page_url"
              @click="fetchCustomers(pagination.current_page + 1)"
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
import { formatDate } from '../../utils/helpers';

export default {
  name: 'CustomerList',
  setup() {
    const customers = ref([
      {
        id: 1,
        name: 'John Doe',
        username: 'johndoe',
        package: { name: '5Mbps Unlimited' },
        status: 'active',
        expiry_date: '2025-09-15'
      },
      {
        id: 2,
        name: 'Jane Smith',
        username: 'janesmith',
        package: { name: '10Mbps Unlimited' },
        status: 'active',
        expiry_date: '2025-09-20'
      },
      {
        id: 3,
        name: 'Robert Johnson',
        username: 'robertj',
        package: { name: '20Mbps Unlimited' },
        status: 'expired',
        expiry_date: '2025-08-01'
      }
    ]);
    
    const searchQuery = ref('');
    const statusFilter = ref('');
    const packageFilter = ref('');
    
    const pagination = ref({
      current_page: 1,
      last_page: 2,
      per_page: 10,
      total: 30,
      from: 1,
      to: 10,
      prev_page_url: null,
      next_page_url: '/api/customers?page=2'
    });
    
    const fetchCustomers = async (page = 1) => {
      try {
        // In a real application, you would fetch this data from your API
        // For now, we'll use mock data
        
        console.log(`Fetching customers for page: ${page}`);
      } catch (error) {
        console.error('Error fetching customers:', error);
      }
    };
    
    const applyFilters = () => {
      // In a real application, you would apply filters to the API request
      console.log('Applying filters:', {
        search: searchQuery.value,
        status: statusFilter.value,
        package: packageFilter.value
      });
      
      // For now, just refetch customers
      fetchCustomers();
    };
    
    onMounted(() => {
      fetchCustomers();
    });
    
    return {
      customers,
      searchQuery,
      statusFilter,
      packageFilter,
      pagination,
      formatDate,
      fetchCustomers,
      applyFilters
    };
  }
};
</script>