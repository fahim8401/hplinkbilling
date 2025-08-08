<template>
  <div>
    <div class="flex justify-between items-center mb-6">
      <h1 class="text-2xl font-bold text-gray-900">Customer Details</h1>
      <div class="flex space-x-2">
        <button class="form-button-secondary">
          Edit Customer
        </button>
        <button class="form-button">
          Recharge
        </button>
      </div>
    </div>
    
    <div class="grid grid-cols-1 lg:grid-cols-3 gap-6">
      <div class="lg:col-span-2">
        <div class="card mb-6">
          <div class="card-header">
            <h2 class="text-lg font-medium text-gray-900">Customer Information</h2>
          </div>
          <div class="card-body">
            <div class="grid grid-cols-1 md:grid-cols-2 gap-4">
              <div>
                <label class="form-label">Name</label>
                <p class="text-gray-900">{{ customer.name }}</p>
              </div>
              <div>
                <label class="form-label">Username</label>
                <p class="text-gray-900">{{ customer.username }}</p>
              </div>
              <div>
                <label class="form-label">Email</label>
                <p class="text-gray-900">{{ customer.email }}</p>
              </div>
              <div>
                <label class="form-label">Phone</label>
                <p class="text-gray-900">{{ customer.phone }}</p>
              </div>
              <div>
                <label class="form-label">Package</label>
                <p class="text-gray-900">{{ customer.package?.name }}</p>
              </div>
              <div>
                <label class="form-label">Status</label>
                <p>
                  <span class="status-badge" :class="`status-${customer.status}`">
                    {{ customer.status }}
                  </span>
                </p>
              </div>
              <div>
                <label class="form-label">Activation Date</label>
                <p class="text-gray-900">{{ formatDate(customer.activation_date) }}</p>
              </div>
              <div>
                <label class="form-label">Expiry Date</label>
                <p class="text-gray-900">{{ formatDate(customer.expiry_date) }}</p>
              </div>
            </div>
          </div>
        </div>
        
        <div class="card">
          <div class="card-header">
            <h2 class="text-lg font-medium text-gray-900">Usage Statistics</h2>
          </div>
          <div class="card-body">
            <div class="grid grid-cols-1 md:grid-cols-3 gap-4 mb-6">
              <div class="bg-blue-50 p-4 rounded-lg">
                <h3 class="text-sm font-medium text-blue-800">Today's Usage</h3>
                <p class="text-2xl font-bold text-blue-600">1.2 GB</p>
              </div>
              <div class="bg-green-50 p-4 rounded-lg">
                <h3 class="text-sm font-medium text-green-800">This Month</h3>
                <p class="text-2xl font-bold text-green-600">24.5 GB</p>
              </div>
              <div class="bg-purple-50 p-4 rounded-lg">
                <h3 class="text-sm font-medium text-purple-800">Speed</h3>
                <p class="text-2xl font-bold text-purple-600">5 Mbps</p>
              </div>
            </div>
            
            <h3 class="text-lg font-medium text-gray-900 mb-4">Daily Usage</h3>
            <div class="h-64">
              <!-- Chart would go here in a real implementation -->
              <div class="flex items-center justify-center h-full bg-gray-50 rounded-lg">
                <p class="text-gray-500">Usage chart visualization</p>
              </div>
            </div>
          </div>
        </div>
      </div>
      
      <div>
        <div class="card mb-6">
          <div class="card-header">
            <h2 class="text-lg font-medium text-gray-900">Actions</h2>
          </div>
          <div class="card-body space-y-3">
            <button class="form-button-secondary w-full justify-center">
              Suspend Customer
            </button>
            <button class="form-button-secondary w-full justify-center">
              Reset Password
            </button>
            <button class="form-button-secondary w-full justify-center">
              Change Package
            </button>
            <button class="form-button-secondary w-full justify-center">
              Extend Expiry
            </button>
          </div>
        </div>
        
        <div class="card">
          <div class="card-header">
            <h2 class="text-lg font-medium text-gray-900">Recent Invoices</h2>
          </div>
          <div class="card-body">
            <div class="space-y-3">
              <div 
                v-for="invoice in recentInvoices" 
                :key="invoice.id"
                class="border border-gray-200 rounded-lg p-3"
              >
                <div class="flex justify-between items-center">
                  <span class="font-medium">{{ formatCurrency(invoice.total_amount) }}</span>
                  <span class="status-badge" :class="`status-${invoice.status}`">
                    {{ invoice.status }}
                  </span>
                </div>
                <div class="text-sm text-gray-500 mt-1">
                  Due: {{ formatDate(invoice.due_date) }}
                </div>
              </div>
            </div>
            
            <div class="mt-4">
              <button class="form-button-secondary w-full justify-center">
                View All Invoices
              </button>
            </div>
          </div>
        </div>
      </div>
    </div>
  </div>
</template>

<script>
import { ref, onMounted } from 'vue';
import { useRoute } from 'vue-router';
import { formatCurrency, formatDate } from '../../utils/helpers';

export default {
  name: 'CustomerDetail',
  props: {
    id: {
      type: String,
      required: true
    }
  },
  setup(props) {
    const route = useRoute();
    
    const customer = ref({
      id: 1,
      name: 'John Doe',
      username: 'johndoe',
      email: 'john@example.com',
      phone: '+1234567890',
      package: { name: '5Mbps Unlimited' },
      status: 'active',
      activation_date: '2025-01-15',
      expiry_date: '2025-09-15'
    });
    
    const recentInvoices = ref([
      {
        id: 1,
        total_amount: 500,
        status: 'paid',
        due_date: '2025-08-10'
      },
      {
        id: 2,
        total_amount: 500,
        status: 'pending',
        due_date: '2025-09-10'
      }
    ]);
    
    const fetchCustomer = async () => {
      try {
        // In a real application, you would fetch this data from your API
        // For now, we'll use the mock data
        
        console.log(`Fetching customer with ID: ${props.id}`);
      } catch (error) {
        console.error('Error fetching customer:', error);
      }
    };
    
    onMounted(() => {
      fetchCustomer();
    });
    
    return {
      customer,
      recentInvoices,
      formatCurrency,
      formatDate
    };
  }
};
</script>