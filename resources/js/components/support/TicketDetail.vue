<template>
  <div>
    <div class="flex justify-between items-center mb-6">
      <h1 class="text-2xl font-bold text-gray-900">Ticket #{{ ticket.id }}</h1>
      <div class="flex space-x-2">
        <button class="form-button-secondary">
          Assign
        </button>
        <button class="form-button">
          Close Ticket
        </button>
      </div>
    </div>
    
    <div class="grid grid-cols-1 lg:grid-cols-3 gap-6">
      <div class="lg:col-span-2">
        <div class="card mb-6">
          <div class="card-header">
            <h2 class="text-lg font-medium text-gray-900">Ticket Details</h2>
          </div>
          <div class="card-body">
            <div class="grid grid-cols-1 md:grid-cols-2 gap-4 mb-6">
              <div>
                <label class="form-label">Subject</label>
                <p class="text-gray-900">{{ ticket.subject }}</p>
              </div>
              <div>
                <label class="form-label">Customer</label>
                <p class="text-gray-900">{{ ticket.customer?.name }}</p>
              </div>
              <div>
                <label class="form-label">Status</label>
                <p>
                  <span class="status-badge" :class="`status-${ticket.status}`">
                    {{ ticket.status }}
                  </span>
                </p>
              </div>
              <div>
                <label class="form-label">Priority</label>
                <p>
                  <span class="status-badge" :class="`status-${ticket.priority}`">
                    {{ ticket.priority }}
                  </span>
                </p>
              </div>
              <div>
                <label class="form-label">Created</label>
                <p class="text-gray-900">{{ formatDate(ticket.created_at) }}</p>
              </div>
              <div>
                <label class="form-label">Assigned To</label>
                <p class="text-gray-900">{{ ticket.assigned_to?.name || 'Unassigned' }}</p>
              </div>
            </div>
            
            <div>
              <label class="form-label">Description</label>
              <div class="bg-gray-50 p-4 rounded-lg">
                <p class="text-gray-700">{{ ticket.description }}</p>
              </div>
            </div>
          </div>
        </div>
        
        <div class="card">
          <div class="card-header">
            <h2 class="text-lg font-medium text-gray-900">Comments</h2>
          </div>
          <div class="card-body">
            <div class="space-y-4 mb-6">
              <div 
                v-for="comment in comments" 
                :key="comment.id"
                class="border border-gray-200 rounded-lg p-4"
              >
                <div class="flex justify-between items-center mb-2">
                  <span class="font-medium">{{ comment.user?.name }}</span>
                  <span class="text-sm text-gray-500">{{ formatDate(comment.created_at) }}</span>
                </div>
                <p class="text-gray-700">{{ comment.message }}</p>
              </div>
            </div>
            
            <div>
              <label class="form-label">Add Comment</label>
              <textarea 
                class="form-input" 
                rows="3" 
                placeholder="Enter your comment..."
                v-model="newComment"
              ></textarea>
              <div class="mt-2">
                <button class="form-button">
                  Add Comment
                </button>
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
              Change Status
            </button>
            <button class="form-button-secondary w-full justify-center">
              Change Priority
            </button>
            <button class="form-button-secondary w-full justify-center">
              Assign Ticket
            </button>
            <button class="form-button-secondary w-full justify-center">
              Add Attachment
            </button>
          </div>
        </div>
        
        <div class="card">
          <div class="card-header">
            <h2 class="text-lg font-medium text-gray-900">Ticket History</h2>
          </div>
          <div class="card-body">
            <div class="space-y-3">
              <div 
                v-for="log in ticketLogs" 
                :key="log.id"
                class="border-l-4 border-primary-500 pl-3 py-1"
              >
                <p class="text-sm font-medium">{{ log.action }}</p>
                <p class="text-xs text-gray-500">{{ formatDate(log.created_at) }}</p>
                <p class="text-xs text-gray-600 mt-1">{{ log.description }}</p>
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
import { formatDate } from '../../utils/helpers';

export default {
  name: 'TicketDetail',
  props: {
    id: {
      type: String,
      required: true
    }
  },
  setup(props) {
    const route = useRoute();
    
    const ticket = ref({
      id: 1,
      subject: 'Internet connection is slow',
      customer: { name: 'John Doe' },
      status: 'open',
      priority: 'medium',
      created_at: '2025-08-01 10:30:00',
      assigned_to: { name: 'Support Agent' },
      description: 'Customer is experiencing slow internet speeds, especially during peak hours. Speed test shows 2Mbps down when package is 10Mbps.'
    });
    
    const comments = ref([
      {
        id: 1,
        user: { name: 'Support Agent' },
        message: 'I\'m looking into this issue now. Can you confirm if this is happening on all devices?',
        created_at: '2025-08-01 11:15:00'
      },
      {
        id: 2,
        user: { name: 'John Doe' },
        message: 'Yes, it\'s happening on all devices. I\'ve tried restarting the router but no improvement.',
        created_at: '2025-08-01 12:30:00'
      }
    ]);
    
    const ticketLogs = ref([
      {
        id: 1,
        action: 'Ticket Created',
        description: 'Ticket created by customer',
        created_at: '2025-08-01 10:30:00'
      },
      {
        id: 2,
        action: 'Assigned',
        description: 'Assigned to Support Agent',
        created_at: '2025-08-01 10:35:00'
      }
    ]);
    
    const newComment = ref('');
    
    const fetchTicket = async () => {
      try {
        // In a real application, you would fetch this data from your API
        // For now, we'll use the mock data
        
        console.log(`Fetching ticket with ID: ${props.id}`);
      } catch (error) {
        console.error('Error fetching ticket:', error);
      }
    };
    
    onMounted(() => {
      fetchTicket();
    });
    
    return {
      ticket,
      comments,
      ticketLogs,
      newComment,
      formatDate
    };
  }
};
</script>