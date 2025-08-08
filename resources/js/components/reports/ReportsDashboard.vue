<template>
  <div>
    <h1 class="text-2xl font-bold text-gray-900 mb-6">Reports</h1>
    
    <div class="grid grid-cols-1 md:grid-cols-2 lg:grid-cols-3 gap-6 mb-6">
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
            Package Usage
          </button>
          <button class="form-button-secondary w-full justify-center">
            BTRC Export
          </button>
        </div>
      </div>
      
      <div class="card">
        <div class="card-header">
          <h2 class="text-lg font-medium text-gray-900">System Reports</h2>
        </div>
        <div class="card-body space-y-3">
          <button class="form-button-secondary w-full justify-center">
            Network Usage
          </button>
          <button class="form-button-secondary w-full justify-center">
            Support Tickets
          </button>
          <button class="form-button-secondary w-full justify-center">
            Reseller Performance
          </button>
          <button class="form-button-secondary w-full justify-center">
            POP Status
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
          <div class="bg-green-50 p-4 rounded-lg">
            <h3 class="text-sm font-medium text-green-800">Today's Revenue</h3>
            <p class="text-2xl font-bold text-green-600">{{ formatCurrency(12500) }}</p>
          </div>
          <div class="bg-blue-50 p-4 rounded-lg">
            <h3 class="text-sm font-medium text-blue-800">This Week</h3>
            <p class="text-2xl font-bold text-blue-600">{{ formatCurrency(87500) }}</p>
          </div>
          <div class="bg-purple-50 p-4 rounded-lg">
            <h3 class="text-sm font-medium text-purple-800">This Month</h3>
            <p class="text-2xl font-bold text-purple-600">{{ formatCurrency(375000) }}</p>
          </div>
          <div class="bg-yellow-50 p-4 rounded-lg">
            <h3 class="text-sm font-medium text-yellow-800">This Year</h3>
            <p class="text-2xl font-bold text-yellow-600">{{ formatCurrency(4500000) }}</p>
          </div>
        </div>
        
        <h3 class="text-lg font-medium text-gray-900 mb-4">Monthly Revenue Trend</h3>
        <div class="h-64">
          <!-- Chart would go here in a real implementation -->
          <div class="flex items-center justify-center h-full bg-gray-50 rounded-lg">
            <p class="text-gray-500">Revenue trend chart visualization</p>
          </div>
        </div>
      </div>
    </div>
  </div>
</template>

<script>
import { formatCurrency } from '../../utils/helpers';

export default {
  name: 'ReportsDashboard',
  setup() {
    return {
      formatCurrency
    };
  }
};
</script>