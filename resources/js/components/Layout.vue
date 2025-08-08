<template>
  <div class="min-h-screen bg-gray-100">
    <!-- Navigation -->
    <nav class="bg-white shadow">
      <div class="max-w-7xl mx-auto px-4 sm:px-6 lg:px-8">
        <div class="flex justify-between h-16">
          <div class="flex">
            <div class="flex-shrink-0 flex items-center">
              <h1 class="text-xl font-bold text-primary-600">ISP Billing & CRM</h1>
            </div>
            <div class="hidden sm:ml-6 sm:flex sm:space-x-8">
              <router-link 
                to="/" 
                class="nav-link" 
                :class="{ 'nav-link-active': $route.name === 'Dashboard' }"
              >
                Dashboard
              </router-link>
              <router-link 
                to="/customers" 
                class="nav-link" 
                :class="{ 'nav-link-active': $route.name === 'Customers' }"
              >
                Customers
              </router-link>
              <router-link 
                to="/billing" 
                class="nav-link" 
                :class="{ 'nav-link-active': $route.name === 'Billing' }"
              >
                Billing
              </router-link>
              <router-link 
                to="/reports" 
                class="nav-link" 
                :class="{ 'nav-link-active': $route.name === 'Reports' }"
              >
                Reports
              </router-link>
            </div>
          </div>
          <div class="hidden sm:ml-6 sm:flex sm:items-center">
            <button class="bg-white p-1 rounded-full text-gray-400 hover:text-gray-500 focus:outline-none">
              <span class="sr-only">View notifications</span>
              <svg class="h-6 w-6" xmlns="http://www.w3.org/2000/svg" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M15 17h5l-1.405-1.405A2.032 2.032 0 0118 14.158V11a6.002 6.002 0 00-4-5.659V5a2 2 0 10-4 0v.341C7.67 6.165 6 8.388 6 11v3.159c0 .538-.214 1.055-.595 1.436L4 17h5m6 0v1a3 3 0 11-6 0v-1m6 0H9" />
              </svg>
            </button>

            <!-- Profile dropdown -->
            <div class="ml-3 relative">
              <div>
                <button class="flex text-sm rounded-full focus:outline-none">
                  <span class="sr-only">Open user menu</span>
                  <div class="h-8 w-8 rounded-full bg-primary-500 flex items-center justify-center text-white font-medium">
                    {{ userInitials }}
                  </div>
                </button>
              </div>
            </div>
          </div>
        </div>
      </div>
    </nav>

    <!-- Main content -->
    <main class="py-6">
      <div class="max-w-7xl mx-auto px-4 sm:px-6 lg:px-8">
        <router-view />
      </div>
    </main>
  </div>
</template>

<script>
import { useAuthStore } from '../stores/auth';

export default {
  name: 'Layout',
  setup() {
    const authStore = useAuthStore();
    
    // Get user initials for profile avatar
    const userInitials = computed(() => {
      if (authStore.user) {
        const name = authStore.user.name || '';
        return name.split(' ').map(n => n[0]).join('').toUpperCase().substring(0, 2);
      }
      return 'U';
    });
    
    return {
      userInitials
    };
  }
};
</script>