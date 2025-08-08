<template>
  <div>
    <h1 class="text-2xl font-bold text-gray-900 mb-6">Reports</h1>
    
    <div class="grid grid-cols-1 lg:grid-cols-3 gap-6 mb-6">
      <div class="card">
        <div class="card-header">
          <h2 class="text-lg font-medium text-gray-900">Financial Reports</h2>
        </div>
        <div class="card-body space-y-3">
          <button class="form-button-secondary w-full justify-center">
            Revenue Report
          </button>
          <button class="form-button-secondary w-full justify-center">
            Invoice Report
          </button>
          <button class="form-button-secondary w-full justify-center">
            Payment Report
          </button>
          <button class="form-button-secondary w-full justify-center">
            Commission Report
          </button>
        </div>
      </div>
      
      <div class="card">
        <div class="card-header">
          <h2 class="text-lg font-medium text-gray-900">Customer Reports</h2>
        </div>
        <div class="card-body space-y-3">
          <button class="form-button-secondary w-full justify-center">
            Active Customers
          </button>
          <button class="form-button-secondary w-full justify-center">
            Churn Report
          </button>
          <button class="form-button-secondary w-full justify-center">
            Usage Report
          </button>
          <button class="form-button-secondary w-full justify-center">
            Package Distribution
          </button>
        </div>
      </div>
      
      <div class="card">
        <div class="card-header">
          <h2 class="text-lg font-medium text-gray-900">System Reports</h2>
        </div>
        <div class="card-body space-y-3">
          <button class="form-button-secondary w-full justify-center">
            POP Status
          </button>
          <button class="form-button-secondary w-full justify-center">
            Router Performance
          </button>
          <button class="form-button-secondary w-full justify-center">
            Support Tickets
          </button>
          <button class="form-button-secondary w-full justify-center">
            SMS Logs
          </button>
        </div>
      </div>
    </div>
    
    <div class="card">
      <div class="card-header">
        <h2 class="text-lg font-medium text-gray-900">Revenue Overview</h2>
      </div>
      <div class="card-body">
        <div class="grid grid-cols-1 md:grid-cols-4 gap-4 mb-6">
          <div class="text-center p-4 bg-blue-50 rounded-lg">
            <p class="text-2xl font-bold text-blue-600">{{ formatCurrency(revenueStats.monthlyRevenue) }}</p>
            <p class="text-sm text-gray-600">Monthly Revenue</p>
          </div>
          <div class="text-center p-4 bg-green-50 rounded-lg">
            <p class="text-2xl font-bold text-green-600">{{ formatCurrency(revenueStats.quarterlyRevenue) }}</p>
            <p class="text-sm text-gray-600">Quarterly Revenue</p>
          </div>
          <div class="text-center p-4 bg-purple-50 rounded-lg">
            <p class="text-2xl font-bold text-purple-600">{{ formatCurrency(revenueStats.yearlyRevenue) }}</p>
            <p class="text-sm text-gray-600">Yearly Revenue</p>
          </div>
          <div class="text-center p-4 bg-orange-50 rounded-lg">
            <p class="text-2xl font-bold text-orange-600">{{ revenueStats.growthRate }}%</p>
            <p class="text-sm text-gray-600">Growth Rate</p>
          </div>
        </div>
        
        <div class="h-64">
          <!-- Chart would go here in a real implementation -->
          <div class="flex items-center justify-center h-full bg-gray-50 rounded-lg">
            <p class="text-gray-500">Revenue Chart</p>
          </div>
        </div>
      </div>
    </div>
  </div>
</template>

<script>
import { ref, onMounted } from 'vue';

export default {
  name: 'Reports',
  setup() {
    const revenueStats = ref({
      monthlyRevenue: 125000,
      quarterlyRevenue: 375000,
      yearlyRevenue: 1500000,
      growthRate: 12.5
    });
    
    const formatCurrency = (amount) => {
      return new Intl.NumberFormat('en-US', {
        style: 'currency',
        currency: 'BDT'
      }).format(amount);
    };
    
    return {
      revenueStats,
      formatCurrency
    };
  }
};
</script>