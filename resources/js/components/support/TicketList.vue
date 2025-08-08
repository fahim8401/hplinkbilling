<template>
  <div>
    <div class="flex justify-between items-center mb-6">
      <h1 class="text-2xl font-bold text-gray-900">Support Tickets</h1>
      <button class="form-button">
        Create Ticket
      </button>
    </div>
    
    <div class="card mb-6">
      <div class="card-body">
        <div class="grid grid-cols-1 md:grid-cols-4 gap-4">
          <div>
            <label class="form-label">Search</label>
            <input type="text" class="form-input" placeholder="Search tickets..." v-model="searchQuery">
          </div>
          <div>
            <label class="form-label">Status</label>
            <select class="form-input" v-model="statusFilter">
              <option value="">All Statuses</option>
              <option value="open">Open</option>
              <option value="in_progress">In Progress</option>
              <option value="resolved">Resolved</option>
              <option value="closed">Closed</option>
            </select>
          </div>
          <div>
            <label class="form-label">Priority</label>
            <select class="form-input" v-model="priorityFilter">
              <option value="">All Priorities</option>
              <option value="low">Low</option>
              <option value="medium">Medium</option>
              <option value="high">High</option>
              <option value="urgent">Urgent</option>
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
        <h2 class="text-lg font-medium text-gray-900">Ticket List</h2>
      </div>
      <div class="card-body">
        <div class="overflow-x-auto">
          <table class="table">
            <thead class="table-head">
              <tr>
                <th class="table-header-cell">Ticket #</th>
                <th class="table-header-cell">Subject</th>
                <th class="table-header-cell">Customer</th>
                <th class="table-header-cell">Status</th>
                <th class="table-header-cell">Priority</th>
                <th class="table-header-cell">Created</th>
                <th class="table-header-cell">Actions</th>
              </tr>
            </thead>
            <tbody class="table-body">
              <tr v-for="ticket in tickets" :key="ticket.id">
                <td class="table-cell">{{ ticket.id }}</td>
                <td class="table-cell">{{ ticket.subject }}</td>
                <td class="table-cell">{{ ticket.customer?.name }}</td>
                <td class="table-cell">
                  <span class="status-badge" :class="`status-${ticket.status}`">
                    {{ ticket.status }}
                  </span>
                </td>
                <td class="table-cell">
                  <span class="status-badge" :class="`status-${ticket.priority}`">
                    {{ ticket.priority }}
                  </span>
                </td>
                <td class="table-cell">{{ formatDate(ticket.created_at) }}</td>
                <td class="table-cell">
                  <button class="text-primary-600 hover:text-primary-900">
                    View
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
              @click="fetchTickets(pagination.current_page - 1)"
            >
              Previous
            </button>
            <button 
              class="form-button-secondary" 
              :disabled="!pagination.next_page_url"
              @click="fetchTickets(pagination.current_page + 1)"
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
  name: 'TicketList',
  setup() {
    const tickets = ref([
      {
        id: 1,
        subject: 'Internet connection is slow',
        customer: { name: 'John Doe' },
        status: 'open',
        priority: 'medium',
        created_at: '2025-08-01 10:30:00'
      },
      {
        id: 2,
        subject: 'Unable to connect to PPPoE',
        customer: { name: 'Jane Smith' },
        status: 'in_progress',
        priority: 'high',
        created_at: '2025-08-02 14:15:00'
      },
      {
        id: 3,
        subject: 'Billing inquiry',
        customer: { name: 'Robert Johnson' },
        status: 'resolved',
        priority: 'low',
        created_at: '2025-08-03 09:45:00'
      }
    ]);
    
    const searchQuery = ref('');
    const statusFilter = ref('');
    const priorityFilter = ref('');
    
    const pagination = ref({
      current_page: 1,
      last_page: 2,
      per_page: 10,
      total: 30,
      from: 1,
      to: 10,
      prev_page_url: null,
      next_page_url: '/api/tickets?page=2'
    });
    
    const fetchTickets = async (page = 1) => {
      try {
        // In a real application, you would fetch this data from your API
        // For now, we'll use mock data
        
        console.log(`Fetching tickets for page: ${page}`);
      } catch (error) {
        console.error('Error fetching tickets:', error);
      }
    };
    
    const applyFilters = () => {
      // In a real application, you would apply filters to the API request
      console.log('Applying filters:', {
        search: searchQuery.value,
        status: statusFilter.value,
        priority: priorityFilter.value
      });
      
      // For now, just refetch tickets
      fetchTickets();
    };
    
    onMounted(() => {
      fetchTickets();
    });
    
    return {
      tickets,
      searchQuery,
      statusFilter,
      priorityFilter,
      pagination,
      formatDate,
      fetchTickets,
      applyFilters
    };
  }
};
</script>