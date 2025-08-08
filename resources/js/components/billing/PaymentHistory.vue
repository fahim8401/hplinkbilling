<template>
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
</template>

<script>
import { ref, onMounted } from 'vue';

export default {
  name: 'PaymentHistory',
  setup() {
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
    
    return {
      payments,
      formatDate,
      formatCurrency
    };
  }
};
</script>