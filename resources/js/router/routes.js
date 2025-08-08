// Import page components
import Login from '../views/Login.vue';
import Layout from '../views/Layout.vue';
import Dashboard from '../views/Dashboard.vue';
import Customers from '../views/Customers.vue';
import CustomerDetail from '../views/CustomerDetail.vue';
import Billing from '../views/Billing.vue';
import Reports from '../views/Reports.vue';

// Define routes
export const routes = [
  {
    path: '/login',
    component: Login,
    name: 'Login'
  },
  {
    path: '/',
    component: Layout,
    name: 'Layout',
    children: [
      {
        path: '',
        component: Dashboard,
        name: 'Dashboard'
      },
      {
        path: 'customers',
        component: Customers,
        name: 'Customers'
      },
      {
        path: 'customers/:id',
        component: CustomerDetail,
        name: 'CustomerDetail',
        props: true
      },
      {
        path: 'billing',
        component: Billing,
        name: 'Billing'
      },
      {
        path: 'reports',
        component: Reports,
        name: 'Reports'
      }
    ]
  }
];