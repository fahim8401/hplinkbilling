// Import and export all main components, stores, services, and utilities
import * as components from './components';
import * as stores from './stores';
import * as services from './services';
import * as utils from './utils';

// Export everything
export { components, stores, services, utils };

// Export the main App component
export { default as App } from './App.vue';

// Export the main router
export { default as router } from './router';

// Export the main store
export { default as store } from './store';