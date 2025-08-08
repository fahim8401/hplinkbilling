import { createApp } from 'vue';
import { createPinia } from 'pinia';
import { createRouter, createWebHistory } from 'vue-router';

// Import the main App component
import App from './App.vue';

// Import routes
import { routes } from './router';

// Import CSS
import '../css/app.css';

// Create the Vue app
const app = createApp(App);

// Create Pinia store
const pinia = createPinia();
app.use(pinia);

// Create router
const router = createRouter({
  history: createWebHistory(),
  routes
});

// Use router
app.use(router);

// Mount the app
app.mount('#app');