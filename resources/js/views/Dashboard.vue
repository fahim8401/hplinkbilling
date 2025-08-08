<template>
  <div>
    <h1 class="text-2xl font-bold text-gray-900 mb-6">Dashboard</h1>
    
    <div class="grid grid-cols-1 md:grid-cols-2 lg:grid-cols-4 gap-6 mb-6">
      <div class="card">
        <div class="card-body">
          <h3 class="text-lg font-medium text-gray-900">Total Customers</h3>
          <p class="text-3xl font-bold text-primary-600">{{ stats.totalCustomers }}</p>
        </div>
      </div>
      
      <div class="card">
        <div class="card-body">
          <h3 class="text-lg font-medium text-gray-900">Active Customers</h3>
          <p class="text-3xl font-bold text-green-600">{{ stats.activeCustomers }}</p>
        </div>
      </div>
      
      <div class="card">
        <div class="card-body">
          <h3 class="text-lg font-medium text-gray-900">Monthly Revenue</h3>
          <p class="text-3xl font-bold text-blue-600">{{ formatCurrency(stats.monthlyRevenue) }}</p>
        </div>
      </div>
      
      <div class="card">
        <div class="card-body">
          <h3 class="text-lg font-medium text-gray-900">Pending Invoices</h3>
          <p class="text-3xl font-bold text-yellow-600">{{ stats.pendingInvoices }}</p>
        </div>
      </div>
    </div>
    
    <div class="grid grid-cols-1 lg:grid-cols-2 gap-6">
      <div class="card">
        <div class="card-header">
          <h2 class="text-lg font-medium text-gray-900">Recent Customers</h2>
        </div>
        <div class="card-body">
          <div class="overflow-x-auto">
            <table class="table">
              <thead class="table-head">
                <tr>
                  <th class="table-header-cell">Name</th>
                  <th class="table-header-cell">Package</th>
                  <th class="table-header-cell">Status</th>
                  <th class="table-header-cell">Expiry Date</th>
                </tr>
              </thead>
              <tbody class="table-body">
                <tr v-for="customer in recentCustomers" :key="customer.id">
                  <td class="table-cell">{{ customer.name }}</td>
                  <td class="table-cell">{{ customer.package?.name }}</td>
                  <td class="table-cell">
                    <span class="status-badge" :class="`status-${customer.status}`">
                      {{ customer.status }}
                    </span>
                  </td>
                  <td class="table-cell">{{ formatDate(customer.expiry_date) }}</td>
                </tr>
              </tbody>
            </table>
          </div>
        </div>
      </div>
      
      <div class="card">
        <div class="card-header">
          <h2 class="text-lg font-medium text-gray-900">Recent Invoices</h2>
        </div>
        <div class="card-body">
          <div class="overflow-x-auto">
            <table class="table">
              <thead class="table-head">
                <tr>
                  <th class="table-header-cell">Customer</th>
                  <th class="table-header-cell">Amount</th>
                  <th class="table-header-cell">Status</th>
                  <th class="table-header-cell">Due Date</th>
                </tr>
              </thead>
              <tbody class="table-body">
                <tr v-for="invoice in recentInvoices" :key="invoice.id">
                  <td class="table-cell">{{ invoice.customer?.name }}</td>
                  <td class="table-cell">{{ formatCurrency(invoice.total_amount) }}</td>
                  <td class="table-cell">
                    <span class="status-badge" :class="`status-${invoice.status}`">
                      {{ invoice.status }}
                    </span>
                  </td>
                  <td class="table-cell">{{ formatDate(invoice.due_date) }}</td>
                </tr>
              </tbody>
            </table>
          </div>
        </div>
      </div>
    </div>
  </div>
</template>

<script>
import { ref, onMounted } from 'vue';
import axios from 'axios';

export default {
  name: 'Dashboard',
  setup() {
    const stats = ref({
      totalCustomers: 0,
      activeCustomers: 0,
      monthlyRevenue: 0,
      pendingInvoices: 0
    });
    
    const recentCustomers = ref([]);
    const recentInvoices = ref([]);
    
    const formatCurrency = (amount) => {
      return new Intl.NumberFormat('en-US', {
        style: 'currency',
        currency: 'BDT'
      }).format(amount);
    };
    
    const formatDate = (dateString) => {
      if (!dateString) return '';
      return new Date(dateString).toLocaleDateString();
    };
    
    const fetchDashboardData = async () => {
      try {
        // In a real application, you would fetch this data from your API
        // For now, we'll use mock data
        
        stats.value = {
          totalCustomers: 1240,
          activeCustomers: 1180,
          monthlyRevenue: 125000,
          pendingInvoices: 42
        };
        
        recentCustomers.value = [
          {
            id: 1,
            name: 'John Doe',
            package: { name: '5Mbps Unlimited' },
            status: 'active',
            expiry_date: '2025-09-15'
          },
          {
            id: 2,
            name: 'Jane Smith',
            package: { name: '10Mbps Unlimited' },
            status: 'active',
            expiry_date: '2025-09-20'
          },
          {
            id: 3,
            name: 'Robert Johnson',
            package: { name: '20Mbps Unlimited' },
            status: 'expired',
            expiry_date: '2025-08-01'
          }
        ];
        
        recentInvoices.value = [
          {
            id: 1,
            customer: { name: 'John Doe' },
            total_amount: 500,
            status: 'paid',
            due_date: '2025-08-10'
          },
          {
            id: 2,
            customer: { name: 'Jane Smith' },
            total_amount: 800,
            status: 'pending',
            due_date: '2025-08-15'
          },
          {
            id: 3,
            customer: { name: 'Robert Johnson' },
            total_amount: 1200,
            status: 'overdue',
            due_date: '2025-08-01'
          }
        ];
      } catch (error) {
        console.error('Error fetching dashboard data:', error);
      }
    };
    
    onMounted(() => {
      fetchDashboardData();
    });
    
    return {
      stats,
      recentCustomers,
      recentInvoices,
      formatCurrency,
      formatDate
    };
  }
};
</script>