<template>
  <div id="app">
    <nav class="bg-white shadow">
      <div class="max-w-7xl mx-auto px-4 sm:px-6 lg:px-8">
        <div class="flex justify-between h-16">
          <div class="flex">
            <div class="flex-shrink-0 flex items-center">
              <h1 class="text-xl font-bold text-primary-600">ISP Billing & CRM</h1>
            </div>
            <div class="hidden sm:ml-6 sm:flex sm:space-x-8">
              <router-link to="/" class="nav-link" active-class="nav-link-active">Dashboard</router-link>
              <router-link to="/customers" class="nav-link" active-class="nav-link-active">Customers</router-link>
              <router-link to="/billing" class="nav-link" active-class="nav-link-active">Billing</router-link>
              <router-link to="/reports" class="nav-link" active-class="nav-link-active">Reports</router-link>
            </div>
          </div>
          <div class="hidden sm:ml-6 sm:flex sm:items-center">
            <div class="ml-3 relative">
              <div>
                <button class="flex text-sm rounded-full focus:outline-none focus:ring-2 focus:ring-offset-2 focus:ring-primary-500">
                  <span class="sr-only">Open user menu</span>
                  <div class="h-8 w-8 rounded-full bg-primary-500 flex items-center justify-center text-white font-semibold">
                    {{ userInitials }}
                  </div>
                </button>
              </div>
            </div>
          </div>
        </div>
      </div>
    </nav>

    <main>
      <div class="max-w-7xl mx-auto py-6 sm:px-6 lg:px-8">
        <router-view></router-view>
      </div>
    </main>
  </div>
</template>

<script>
import { mapState } from 'pinia';
import { useAuthStore } from '../stores/auth';

export default {
  name: 'App',
  computed: {
    ...mapState(useAuthStore, ['user']),
    userInitials() {
      if (this.user && this.user.name) {
        return this.user.name.split(' ').map(n => n[0]).join('').toUpperCase();
      }
      return 'U';
    }
  }
}
</script>