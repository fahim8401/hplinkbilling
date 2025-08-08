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
                <label class="form-label">NID</label>
                <p class="text-gray-900">{{ customer.nid }}</p>
              </div>
              <div>
                <label class="form-label">Customer Type</label>
                <p class="text-gray-900 capitalize">{{ customer.customer_type }}</p>
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
              <div>
                <label class="form-label">Package</label>
                <p class="text-gray-900">{{ customer.package?.name }}</p>
              </div>
              <div>
                <label class="form-label">POP</label>
                <p class="text-gray-900">{{ customer.pop?.name }}</p>
              </div>
              <div>
                <label class="form-label">Router</label>
                <p class="text-gray-900">{{ customer.router?.name }}</p>
              </div>
              <div>
                <label class="form-label">Reseller</label>
                <p class="text-gray-900">{{ customer.reseller?.name }}</p>
              </div>
            </div>
          </div>
        </div>
        
        <div class="card mb-6">
          <div class="card-header">
            <h2 class="text-lg font-medium text-gray-900">Usage Statistics</h2>
          </div>
          <div class="card-body">
            <div class="grid grid-cols-1 md:grid-cols-4 gap-4 mb-4">
              <div class="text-center p-4 bg-blue-50 rounded-lg">
                <p class="text-2xl font-bold text-blue-600">{{ usageStats.currentDownload }}</p>
                <p class="text-sm text-gray-600">Current Download</p>
              </div>
              <div class="text-center p-4 bg-green-50 rounded-lg">
                <p class="text-2xl font-bold text-green-600">{{ usageStats.currentUpload }}</p>
                <p class="text-sm text-gray-600">Current Upload</p>
              </div>
              <div class="text-center p-4 bg-purple-50 rounded-lg">
                <p class="text-2xl font-bold text-purple-600">{{ usageStats.monthlyDownload }}</p>
                <p class="text-sm text-gray-600">Monthly Download</p>
              </div>
              <div class="text-center p-4 bg-orange-50 rounded-lg">
                <p class="text-2xl font-bold text-orange-600">{{ usageStats.monthlyUpload }}</p>
                <p class="text-sm text-gray-600">Monthly Upload</p>
              </div>
            </div>
            
            <div class="h-64">
              <!-- Chart would go here in a real implementation -->
              <div class="flex items-center justify-center h-full bg-gray-50 rounded-lg">
                <p class="text-gray-500">Usage Chart</p>
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
            <button class="form-button w-full justify-center">
              Extend Expiry
            </button>
            <button class="form-button-secondary w-full justify-center">
              Change Package
            </button>
            <button 
              class="form-button-secondary w-full justify-center"
              :class="{
                'bg-yellow-500 hover:bg-yellow-600 text-white': customer.status === 'suspended',
                'bg-orange-500 hover:bg-orange-600 text-white': customer.status !== 'suspended'
              }"
              @click="toggleSuspend"
            >
              {{ customer.status === 'suspended' ? 'Enable' : 'Suspend' }}
            </button>
            <button class="form-button-secondary w-full justify-center">
              Reset Password
            </button>
            <button class="form-button-secondary w-full justify-center">
              Move Line
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
                  {{ formatDate(invoice.billing_date) }} - {{ formatDate(invoice.due_date) }}
                </div>
              </div>
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
import axios from 'axios';

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
      nid: '12345678901234567',
      customer_type: 'home',
      status: 'active',
      activation_date: '2025-01-15',
      expiry_date: '2025-09-15',
      package: { name: '5Mbps Unlimited' },
      pop: { name: 'Main POP' },
      router: { name: 'Router 1' },
      reseller: { name: 'Reseller A' }
    });
    
    const usageStats = ref({
      currentDownload: '2.5 Mbps',
      currentUpload: '1.2 Mbps',
      monthlyDownload: '120 GB',
      monthlyUpload: '60 GB'
    });
    
    const recentInvoices = ref([
      {
        id: 1,
        total_amount: 500,
        status: 'paid',
        billing_date: '2025-08-10',
        due_date: '2025-08-25'
      },
      {
        id: 2,
        total_amount: 500,
        status: 'pending',
        billing_date: '2025-07-10',
        due_date: '2025-07-25'
      },
      {
        id: 3,
        total_amount: 500,
        status: 'paid',
        billing_date: '2025-06-10',
        due_date: '2025-06-25'
      }
    ]);
    
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
    
    const toggleSuspend = () => {
      // In a real application, you would make an API call to suspend/enable the customer
      console.log(`Toggling suspend status for customer ${customer.value.id}`);
      
      // For now, just toggle the status locally
      customer.value.status = customer.value.status === 'suspended' ? 'active' : 'suspended';
    };
    
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
      usageStats,
      recentInvoices,
      formatDate,
      formatCurrency,
      toggleSuspend
    };
  }
};
</script>