# Laravel Backend Structure Plan

## Overview
This document outlines the proposed structure for the Laravel backend of the ISP Billing & CRM system. The structure follows Laravel conventions while incorporating best practices for maintainability, scalability, and separation of concerns.

## Directory Structure

```
app/
├── Console/
│   └── Commands/
├── Exceptions/
├── Http/
│   ├── Controllers/
│   │   ├── Api/
│   │   │   ├── V1/
│   │   │   │   ├── Auth/
│   │   │   │   ├── Customer/
│   │   │   │   ├── Admin/
│   │   │   │   ├── Reseller/
│   │   │   │   └── SuperAdmin/
│   │   │   └── V2/ (future versions)
│   │   ├── Web/
│   │   │   ├── Auth/
│   │   │   ├── Customer/
│   │   │   ├── Admin/
│   │   │   ├── Reseller/
│   │   │   └── SuperAdmin/
│   │   └── Controller.php
│   ├── Middleware/
│   ├── Requests/
│   └── Resources/
├── Models/
├── Providers/
├── Services/
├── Repositories/
├── Jobs/
├── Events/
├── Listeners/
├── Notifications/
├── Rules/
├── Traits/
└── Helpers/

config/
database/
public/
resources/
routes/
storage/
tests/
```

## Module Structure

### Core Modules

1. **Auth Module**
   - Authentication and authorization
   - User registration, login, password reset
   - Multi-tenancy aware authentication

2. **Company Module**
   - Company management (Super Admin)
   - Domain/subdomain configuration
   - Company settings and configurations

3. **User Module**
   - User management across all roles
   - Role and permission management
   - Profile management

4. **Customer Module**
   - Customer CRUD operations
   - Customer status management
   - Customer session and usage tracking

5. **Billing Module**
   - Invoice generation and management
   - Payment processing
   - Recharge operations
   - Billing cycle management

6. **Package Module**
   - Package CRUD operations
   - MikroTik profile synchronization
   - Package assignment to resellers

7. **POP Module**
   - POP CRUD operations
   - POP-reseller assignments
   - POP status tracking

8. **MikroTik Module**
   - Router management
   - PPPoE user management
   - Live session tracking
   - Profile synchronization

9. **Reseller Module**
   - Reseller management
   - Balance and commission system
   - Fund transfers

10. **SMS Module**
    - SMS gateway management
    - Template management
    - SMS sending and logging

11. **Support Module**
    - Ticket management
    - Token system
    - Ticket assignment and escalation

12. **Report Module**
    - Financial reports
    - Usage reports
    - Commission reports
    - BTRC export

13. **Bulk Operations Module**
    - Customer import
    - Bulk actions (date extend, package change)

## Package Organization

### Core Packages

1. **App\Core**
   - Base classes and interfaces
   - Multi-tenancy foundation
   - Common utilities

2. **App\Tenancy**
   - Multi-tenancy middleware
   - Tenant identification
   - Domain-based routing

3. **App\Auth**
   - Authentication services
   - Role and permission integration
   - API token management

4. **App\Billing**
   - Billing logic implementation
   - Invoice generation
   - Payment processing

5. **App\Customer**
   - Customer management services
   - Usage tracking
   - Session management

6. **App\Network**
   - MikroTik integration
   - Router management
   - PPPoE operations

7. **App\Reseller**
   - Reseller balance system
   - Commission calculation
   - Fund transfer operations

8. **App\SMS**
   - SMS gateway integrations
   - Template management
   - Sending services

9. **App\Support**
   - Ticket system
   - Token generation
   - Assignment logic

10. **App\Reports**
    - Reporting services
    - Data aggregation
    - Export functionality

## API Structure

### Versioning
- API versioning through URL paths (/api/v1/, /api/v2/)
- Semantic versioning for backward compatibility

### Authentication
- Laravel Sanctum for SPA authentication
- Passport for OAuth2 API tokens
- Role-based access control for endpoints

### Endpoints Structure

```
/api/v1/
├── auth/
│   ├── login
│   ├── logout
│   ├── refresh
│   └── user
├── customer/
│   ├── profile
│   ├── usage
│   ├── tickets
│   ├── invoices
│   └── payments
├── admin/
│   ├── companies
│   ├── users
│   ├── packages
│   ├── pops
│   └── reports
├── reseller/
│   ├── balance
│   ├── customers
│   ├── commissions
│   └── transfers
├── mikrotik/
│   ├── routers
│   ├── profiles
│   └── sessions
├── billing/
│   ├── invoices
│   ├── payments
│   └── recharges
└── support/
    ├── tickets
    └── tokens
```

## Service Layer Design

### Service Classes

1. **AuthService**
   - User authentication
   - Token management
   - Role verification

2. **CompanyService**
   - Company CRUD operations
   - Domain management
   - Settings configuration

3. **CustomerService**
   - Customer management
   - Status updates
   - Usage tracking

4. **BillingService**
   - Invoice generation
   - Payment processing
   - Recharge operations

5. **PackageService**
   - Package management
   - MikroTik profile sync
   - Reseller assignments

6. **POPService**
   - POP management
   - Router assignments
   - Status tracking

7. **MikrotikService**
   - Router communication
   - PPPoE user management
   - Session tracking

8. **ResellerService**
   - Balance management
   - Commission calculation
   - Fund transfers

9. **SMSService**
   - Gateway management
   - Message sending
   - Template processing

10. **SupportService**
    - Ticket management
    - Token generation
    - Assignment logic

11. **ReportService**
    - Data aggregation
    - Report generation
    - Export functionality

## Repository Pattern Implementation

### Repository Classes

1. **CompanyRepository**
2. **UserRepository**
3. **CustomerRepository**
4. **InvoiceRepository**
5. **PaymentRepository**
6. **PackageRepository**
7. **POPRepository**
8. **MikrotikRepository**
9. **ResellerRepository**
10. **SMSRepository**
11. **TicketRepository**
12. **ReportRepository**

Each repository will implement a base repository interface and provide specific data access methods for its entity.

## Queue Jobs for Background Processing

### Job Classes

1. **GenerateInvoicesJob**
   - Monthly invoice generation
   - Scheduled via cron

2. **ProcessPaymentsJob**
   - Online payment processing
   - Callback handling

3. **SendSMSJob**
   - SMS sending with retry logic
   - Queued notifications

4. **SyncMikrotikProfilesJob**
   - Periodic profile synchronization
   - Router data updates

5. **UpdateCustomerStatusJob**
   - Automatic customer status updates
   - Expiry processing

6. **CalculateCommissionsJob**
   - Commission calculations
   - Batch processing

7. **ImportCustomersJob**
   - Bulk customer import
   - CSV/Excel processing

8. **GenerateReportsJob**
   - Scheduled report generation
   - Data aggregation

## Event and Listener Structure

### Events

1. **CustomerCreated**
2. **CustomerExpired**
3. **InvoiceGenerated**
4. **PaymentReceived**
5. **SMSSent**
6. **TicketCreated**
7. **ResellerCommissionCalculated**
8. **BulkImportCompleted**

### Listeners

Each event will have corresponding listeners for:
- Sending notifications
- Updating related entities
- Logging activities
- Triggering other processes

## Middleware for Multi-Tenancy

### Middleware Classes

1. **TenantIdentification**
   - Identify tenant from domain/subdomain
   - Set tenant context

2. **TenantScoping**
   - Apply company_id scoping to queries
   - Ensure data isolation

3. **RoleAuthorization**
   - Verify user roles and permissions
   - Restrict access to resources

4. **APIVersion**
   - Handle API versioning
   - Route to appropriate controllers

## Custom Artisan Commands

### Command Classes

1. **GenerateInvoicesCommand**
   - Manual invoice generation
   - Scheduled execution

2. **SyncMikrotikProfilesCommand**
   - Manual profile synchronization
   - Debugging tool

3. **ProcessExpiredCustomersCommand**
   - Manual expiry processing
   - System maintenance

4. **CalculateCommissionsCommand**
   - Manual commission calculation
   - Financial reporting

5. **SendExpiryNotificationsCommand**
   - Manual notification sending
   - Customer communication

6. **ImportCustomersCommand**
   - CLI customer import
   - Batch processing

7. **GenerateReportsCommand**
   - CLI report generation
   - Automated reporting

## Configuration Files

### Custom Config Files

1. **config/tenancy.php**
   - Multi-tenancy settings
   - Domain mapping

2. **config/billing.php**
   - Billing rules and logic
   - Expiry calculations

3. **config/mikrotik.php**
   - RouterOS settings
   - API configurations

4. **config/sms.php**
   - SMS gateway settings
   - Template configurations

5. **config/reseller.php**
   - Commission rules
   - Balance settings

## Environment Variables

### Key Environment Variables

1. **TENANCY_MODE**
   - Multi-tenancy implementation approach

2. **BILLING_DAY_DEFAULT**
   - Default billing day for companies

3. **MIKROTIK_ENCRYPTION_KEY**
   - Encryption key for router credentials

4. **SMS_RETRY_ATTEMPTS**
   - Number of SMS retry attempts

5. **QUEUE_CONNECTION**
   - Queue driver configuration

6. **REDIS_PREFIX**
   - Redis key prefix for tenant isolation