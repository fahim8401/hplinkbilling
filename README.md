# ISP Billing & CRM System

A comprehensive multi-tenant ISP Billing & CRM application with MikroTik PPPoE integration, reseller management, POP management, SMS notifications, and full billing logic.

## Features

### Multi-Tenancy
- Super Admin portal for managing company panels
- Domain-mapped company panels (white-label)
- Company-level billing configuration
- Data isolation between tenants

### Customer Management
- Customer CRUD operations
- Package assignment and management
- Customer types (home, free, vip, corporate)
- Customer status management (active, inactive, suspended, expired)

### Billing System
- Automatic invoice generation on company billing day
- Customer monthly billing with calendar-based calculations
- Recharge types (RECEIVE for paid, DUE for unpaid extensions)
- Auto-expiry actions based on customer type
- Proration support for mid-cycle package changes

### MikroTik Integration
- Router management with encrypted credentials
- PPPoE user creation, disabling, and deletion
- Live session tracking and interface counters
- Profile synchronization from RouterOS

### Reseller System
- Reseller balance management
- Fund transfers between resellers and employees
- Commission calculation and payout
- Package visibility per reseller

### SMS Notifications
- Multiple SMS gateway support (HTTP GET/POST/JSON)
- Message templating with variables
- Auto-SMS triggers (welcome, invoice, payment success, expiry warning, suspension)
- SMS logging and retry mechanism

### Support System
- Ticket creation and management
- Token generation and assignment
- Ticket escalation and assignment to staff

### Reporting
- Financial reports (revenue, invoices, payments)
- Customer reports (active customers, churn)
- Reseller commission reports
- Usage statistics

### Bulk Operations
- CSV/XLS customer import with validation
- Bulk date extension
- Bulk package changes
- Bulk customer enable/disable

## Tech Stack

### Backend
- Laravel 8.x (PHP 8.x)
- PostgreSQL database
- Redis for caching and queue processing
- Laravel Sanctum for API authentication
- Spatie Laravel Permission for roles and permissions

### Frontend
- Vue 3 with Composition API
- Tailwind CSS for styling
- Pinia for state management
- Vue Router for navigation

### Infrastructure
- Docker containerization (Nginx + Supervisor)
- Laravel Mix for asset compilation

## Installation

1. Clone the repository:
   ```bash
   git clone https://github.com/your-username/isp-billing-crm.git
   cd isp-billing-crm
   ```

2. Install PHP dependencies:
   ```bash
   composer install
   ```

3. Install Node.js dependencies:
   ```bash
   npm install
   ```

4. Copy and configure the environment file:
   ```bash
   cp .env.example .env
   php artisan key:generate
   ```

5. Configure your database and other settings in the `.env` file

6. Run database migrations:
   ```bash
   php artisan migrate
   ```

7. Build frontend assets:
   ```bash
   npm run dev
   # or for production
   npm run prod
   ```

8. Start the development server:
   ```bash
   php artisan serve
   ```

## Docker Setup

The application includes Docker configuration for easy deployment:

1. Build and start containers:
   ```bash
   docker-compose up -d
   ```

2. Run migrations:
   ```bash
   docker-compose exec app php artisan migrate
   ```

3. Install dependencies:
   ```bash
   docker-compose exec app composer install
   docker-compose exec node npm install
   ```

4. Build assets:
   ```bash
   docker-compose exec node npm run dev
   ```

## API Documentation

The application provides a comprehensive REST API for integration with external systems. API documentation is available at `/api/documentation`.

## Contributing

1. Fork the repository
2. Create a feature branch
3. Commit your changes
4. Push to the branch
5. Create a new Pull Request

## License

This project is licensed under the MIT License - see the [LICENSE](LICENSE) file for details.
