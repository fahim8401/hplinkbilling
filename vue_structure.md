# Vue Frontend Structure Plan

## Overview
This document outlines the proposed structure for the Vue 3 frontend of the ISP Billing & CRM system. The structure follows Vue.js best practices while incorporating Tailwind CSS for responsive design and component-based architecture.

## Directory Structure

```
src/
├── assets/
│   ├── images/
│   ├── icons/
│   └── styles/
├── components/
│   ├── common/
│   ├── layout/
│   ├── ui/
│   └── widgets/
├── composables/
├── layouts/
├── pages/
│   ├── auth/
│   ├── super-admin/
│   ├── company-admin/
│   ├── reseller/
│   ├── customer/
│   └── shared/
├── plugins/
├── router/
├── store/
├── utils/
└── views/
```

## Component Organization

### Common Components
Reusable components across all user roles:
- Buttons, forms, inputs
- Modals, dialogs
- Tables, data grids
- Charts and graphs
- Notifications, alerts
- Loading indicators
- Pagination controls

### Layout Components
Page structure components:
- AppHeader (with role-specific navigation)
- AppSidebar (with role-specific menu)
- AppFooter
- Breadcrumbs
- Page containers

### UI Components
Custom UI elements:
- Customer status badges
- Package cards
- POP status indicators
- Invoice status tags
- Usage charts
- Data visualization components

### Widget Components
Dashboard and summary components:
- Summary cards (active customers, revenue, etc.)
- Usage statistics widgets
- Recent activity feeds
- Quick action buttons
- Chart widgets

## State Management Approach

### Store Structure (Pinia)
Using Pinia for state management with modules:

```
store/
├── index.js
├── modules/
│   ├── auth.js
│   ├── company.js
│   ├── customer.js
│   ├── billing.js
│   ├── network.js
│   ├── reseller.js
│   ├── support.js
│   └── ui.js
```

### Module Responsibilities

1. **auth**
   - User authentication state
   - Token management
   - Role and permission data
   - Login/logout actions

2. **company**
   - Current company data
   - Company settings
   - Multi-tenancy context

3. **customer**
   - Customer list and filters
   - Customer detail data
   - Usage statistics
   - Session information

4. **billing**
   - Invoice data
   - Payment records
   - Recharge history
   - Billing cycle information

5. **network**
   - POP data
   - Router information
   - MikroTik profiles
   - Live session data

6. **reseller**
   - Reseller list
   - Balance information
   - Commission data
   - Fund transfer records

7. **support**
   - Ticket data
   - Token information
   - Assignment details

8. **ui**
   - Loading states
   - Notification messages
   - Modal visibility
   - Sidebar state

## Routing Structure

### Route Organization

```
router/
├── index.js
├── routes/
│   ├── auth.js
│   ├── super-admin.js
│   ├── company-admin.js
│   ├── reseller.js
│   ├── customer.js
│   └── shared.js
```

### Route Paths

#### Authentication Routes
- `/login` - Login page
- `/register` - Registration (if applicable)
- `/forgot-password` - Password reset
- `/reset-password` - Password reset form

#### Super Admin Routes
- `/super-admin/dashboard` - Overview dashboard
- `/super-admin/companies` - Company management
- `/super-admin/companies/:id` - Company detail
- `/super-admin/companies/:id/edit` - Edit company
- `/super-admin/users` - User management
- `/super-admin/settings` - Global settings
- `/super-admin/reports` - System-wide reports

#### Company Admin Routes
- `/admin/dashboard` - Company dashboard
- `/admin/customers` - Customer list
- `/admin/customers/create` - Create customer
- `/admin/customers/:id` - Customer detail
- `/admin/customers/:id/edit` - Edit customer
- `/admin/billing` - Billing management
- `/admin/invoices` - Invoice list
- `/admin/packages` - Package management
- `/admin/pops` - POP management
- `/admin/routers` - Router management
- `/admin/resellers` - Reseller management
- `/admin/support` - Support tickets
- `/admin/reports` - Company reports
- `/admin/settings` - Company settings

#### Reseller Routes
- `/reseller/dashboard` - Reseller dashboard
- `/reseller/customers` - Assigned customers
- `/reseller/customers/:id` - Customer detail
- `/reseller/billing` - Billing overview
- `/reseller/invoices` - Invoice list
- `/reseller/balance` - Balance management
- `/reseller/commissions` - Commission reports
- `/reseller/support` - Support tickets
- `/reseller/profile` - Reseller profile

#### Customer Routes
- `/customer/dashboard` - Customer dashboard
- `/customer/profile` - Profile management
- `/customer/usage` - Usage statistics
- `/customer/invoices` - Invoice history
- `/customer/payments` - Payment history
- `/customer/support` - Support tickets
- `/customer/packages` - Package information

#### Shared Routes
- `/profile` - User profile (all roles)
- `/settings` - User settings (role-specific)
- `/notifications` - Notification center
- `/help` - Help documentation

## UI Component Library

### Tailwind CSS Configuration
Customizing Tailwind for the ISP Billing & CRM application:

```javascript
// tailwind.config.js
module.exports = {
  theme: {
    extend: {
      colors: {
        primary: {
          50: '#eff6ff',
          100: '#dbeafe',
          200: '#bfdbfe',
          300: '#93c5fd',
          400: '#60a5fa',
          500: '#3b82f6',
          600: '#2563eb',
          700: '#1d4ed8',
          800: '#1e40af',
          900: '#1e3a8a',
        },
        secondary: {
          50: '#f0f9ff',
          100: '#e0f2fe',
          200: '#bae6fd',
          300: '#7dd3fc',
          400: '#38bdf8',
          500: '#0ea5e9',
          600: '#0284c7',
          700: '#0369a1',
          800: '#075985',
          900: '#0c4a6e',
        },
        status: {
          active: '#10b981',    // green
          inactive: '#ef4444',  // red
          pending: '#f59e0b',   // amber
          expired: '#8b5cf6',   // violet
          suspended: '#f97316', // orange
        }
      }
    }
  }
}
```

### Custom Components

1. **CustomerStatusBadge**
   - Visual indicator for customer status
   - Color-coded based on status

2. **PackageCard**
   - Display package information
   - Price, speed, and features

3. **POPCard**
   - POP information with customer counts
   - Online/Offline status indicators

4. **InvoiceStatusTag**
   - Visual indicator for invoice status
   - Payment due dates

5. **UsageChart**
   - Daily/weekly/monthly usage visualization
   - Download/upload statistics

6. **DataGrid**
   - Responsive data table component
   - Sorting, filtering, pagination

7. **ModalDialog**
   - Reusable modal component
   - Form submissions, confirmations

8. **NotificationToast**
   - Toast notifications for user feedback
   - Success, error, warning, info variants

## Responsive Design Approach

### Breakpoints
Using Tailwind's default breakpoints with customizations:

```css
/* Mobile first approach */
@media (min-width: 640px) { /* sm */ }
@media (min-width: 768px) { /* md */ }
@media (min-width: 1024px) { /* lg */ }
@media (min-width: 1280px) { /* xl */ }
@media (min-width: 1536px) { /* 2xl */ }
```

### Layout Patterns

1. **Dashboard Layout**
   - Grid-based widget arrangement
   - Responsive card stacking
   - Collapsible sidebar on mobile

2. **Detail View Layout**
   - Tabbed interface for sections
   - Responsive form layouts
   - Action button grouping

3. **List View Layout**
   - Filterable data tables
   - Responsive card grids
   - Mobile-friendly list views

4. **Form Layout**
   - Multi-step forms for complex data
   - Responsive input grouping
   - Validation feedback

## Authentication Flow

### Authentication States
1. **Unauthenticated**
   - Redirect to login page
   - Show login/register options

2. **Authenticated**
   - Load user data and permissions
   - Redirect to appropriate dashboard
   - Set up role-based navigation

3. **Token Expired**
   - Attempt token refresh
   - Redirect to login if refresh fails

### Role-Based Rendering

#### Route Guards
- Role-based route access control
- Permission checking for routes
- Redirects based on user role

#### Component Visibility
- Conditional rendering based on permissions
- Role-specific UI elements
- Dynamic menu generation

#### Data Filtering
- API requests with role-based parameters
- Data scoping based on user context
- Permission-aware data manipulation

## Multi-Tenancy UI Considerations

### Domain-Based UI
- Company-specific branding
- Custom logos and color schemes
- Tenant-specific configurations

### Data Isolation
- Company-scoped data display
- Tenant-aware filtering
- Cross-tenant access restrictions

### Navigation
- Role and tenant-aware navigation
- Dynamic menu generation
- Context-sensitive links

## Pages Structure

### Auth Pages
- Login.vue
- Register.vue (if needed)
- ForgotPassword.vue
- ResetPassword.vue

### Super Admin Pages
- SuperAdminDashboard.vue
- CompanyList.vue
- CompanyDetail.vue
- CompanyEdit.vue
- UserManagement.vue
- GlobalSettings.vue
- SystemReports.vue

### Company Admin Pages
- AdminDashboard.vue
- CustomerList.vue
- CustomerCreate.vue
- CustomerDetail.vue
- CustomerEdit.vue
- BillingManagement.vue
- InvoiceList.vue
- PackageManagement.vue
- POPManagement.vue
- RouterManagement.vue
- ResellerManagement.vue
- SupportTickets.vue
- CompanyReports.vue
- CompanySettings.vue

### Reseller Pages
- ResellerDashboard.vue
- ResellerCustomerList.vue
- ResellerCustomerDetail.vue
- BillingOverview.vue
- ResellerInvoiceList.vue
- BalanceManagement.vue
- CommissionReports.vue
- ResellerSupportTickets.vue
- ResellerProfile.vue

### Customer Pages
- CustomerDashboard.vue
- CustomerProfile.vue
- UsageStatistics.vue
- InvoiceHistory.vue
- PaymentHistory.vue
- CustomerSupportTickets.vue
- PackageInformation.vue

### Shared Pages
- UserProfile.vue
- UserSettings.vue
- NotificationCenter.vue
- HelpDocumentation.vue

## Composables for Logic Reuse

### Custom Composables

1. **useAuth**
   - Authentication state and methods
   - Token management
   - User data access

2. **useCompany**
   - Company data and settings
   - Multi-tenancy context
   - Domain-based configurations

3. **useCustomer**
   - Customer data management
   - Usage statistics
   - Session information

4. **useBilling**
   - Invoice and payment data
   - Recharge operations
   - Billing cycle information

5. **useNetwork**
   - POP and router data
   - MikroTik integration
   - Live session tracking

6. **useReseller**
   - Reseller balance and commissions
   - Fund transfer operations
   - Customer assignments

7. **useSupport**
   - Ticket and token management
   - Assignment logic
   - Communication features

8. **useUI**
   - Loading states
   - Notification management
   - Modal control
   - Responsive utilities

## Performance Considerations

### Lazy Loading
- Route-based code splitting
- Component lazy loading
- Dynamic imports for large features

### Caching
- API response caching
- Component memoization
- Local storage for non-sensitive data

### Optimization
- Image optimization
- Bundle size reduction
- Efficient re-rendering