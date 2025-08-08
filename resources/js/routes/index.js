import Dashboard from '../views/Dashboard.vue';
import Customers from '../views/Customers.vue';
import CustomerDetail from '../views/CustomerDetail.vue';
import Billing from '../views/Billing.vue';
import Reports from '../views/Reports.vue';

const routes = [
  {
    path: '/',
    name: 'Dashboard',
    component: Dashboard
  },
  {
    path: '/customers',
    name: 'Customers',
    component: Customers
  },
  {
    path: '/customers/:id',
    name: 'CustomerDetail',
    component: CustomerDetail,
    props: true
  },
  {
    path: '/billing',
    name: 'Billing',
    component: Billing
  },
  {
    path: '/reports',
    name: 'Reports',
    component: Reports
  }
];

export default routes;