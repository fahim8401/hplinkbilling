import { createApp } from 'vue';
import { createPinia } from 'pinia';
import { createRouter, createWebHistory } from 'vue-router';

// Import components
import App from './components/App.vue';

// Import routes
import routes from './routes';

// Create Vue app
const app = createApp(App);

// Create Pinia store
const pinia = createPinia();
app.use(pinia);

// Create router
const router = createRouter({
    history: createWebHistory(),
    routes,
});
app.use(router);

// Mount the app
app.mount('#app');