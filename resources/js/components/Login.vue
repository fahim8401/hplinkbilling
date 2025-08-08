<template>
  <div class="min-h-screen flex items-center justify-center bg-gray-50 py-12 px-4 sm:px-6 lg:px-8">
    <div class="max-w-md w-full space-y-8">
      <div>
        <h1 class="text-center text-3xl font-bold text-gray-900">
          ISP Billing & CRM
        </h1>
        <h2 class="mt-6 text-center text-2xl font-bold text-gray-900">
          Sign in to your account
        </h2>
      </div>
      <form class="mt-8 space-y-6" @submit.prevent="login">
        <div class="rounded-md shadow-sm -space-y-px">
          <div>
            <label for="email" class="sr-only">Email address</label>
            <input 
              id="email" 
              name="email" 
              type="email" 
              autocomplete="email" 
              required 
              class="form-input rounded-t-md" 
              placeholder="Email address"
              v-model="form.email"
            />
          </div>
          <div>
            <label for="password" class="sr-only">Password</label>
            <input 
              id="password" 
              name="password" 
              type="password" 
              autocomplete="current-password" 
              required 
              class="form-input rounded-b-md" 
              placeholder="Password"
              v-model="form.password"
            />
          </div>
        </div>

        <div class="flex items-center justify-between">
          <div class="flex items-center">
            <input 
              id="remember-me" 
              name="remember-me" 
              type="checkbox" 
              class="h-4 w-4 text-primary-600 focus:ring-primary-500 border-gray-300 rounded" 
              v-model="form.remember"
            />
            <label for="remember-me" class="ml-2 block text-sm text-gray-900">
              Remember me
            </label>
          </div>

          <div class="text-sm">
            <a href="#" class="font-medium text-primary-600 hover:text-primary-500">
              Forgot your password?
            </a>
          </div>
        </div>

        <div>
          <button 
            type="submit" 
            class="form-button w-full justify-center"
            :disabled="loading"
          >
            <span v-if="loading" class="flex items-center">
              <svg class="animate-spin -ml-1 mr-3 h-5 w-5 text-white" xmlns="http://www.w3.org/2000/svg" fill="none" viewBox="0 0 24 24">
                <circle class="opacity-25" cx="12" cy="12" r="10" stroke="currentColor" stroke-width="4"></circle>
                <path class="opacity-75" fill="currentColor" d="M4 12a8 8 0 018-8V0C5.373 0 0 5.373 0 12h4zm2 5.291A7.962 7.962 0 014 12H0c0 3.042 1.135 5.824 3 7.938l3-2.647z"></path>
              </svg>
              Signing in...
            </span>
            <span v-else>
              Sign in
            </span>
          </button>
        </div>
        
        <div v-if="error" class="alert alert-error">
          {{ error }}
        </div>
      </form>
    </div>
  </div>
</template>

<script>
import { ref } from 'vue';
import { useAuthStore } from '../stores/auth';
import { useRouter } from 'vue-router';

export default {
  name: 'Login',
  setup() {
    const authStore = useAuthStore();
    const router = useRouter();
    
    const form = ref({
      email: '',
      password: '',
      remember: false
    });
    
    const loading = ref(false);
    const error = ref(null);
    
    const login = async () => {
      loading.value = true;
      error.value = null;
      
      try {
        await authStore.login(form.value);
        router.push('/');
      } catch (err) {
        error.value = err.message || 'Login failed. Please check your credentials.';
      } finally {
        loading.value = false;
      }
    };
    
    return {
      form,
      loading,
      error,
      login
    };
  }
};
</script>