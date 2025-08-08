# ISP Billing & CRM System

A production-ready, multi-tenant ISP Billing & CRM application with domain-mapped company panels, MikroTik RouterOS (PPPoE) integration, POP management, reseller balance & commission system, SMS notifications, support token system, bulk customer import, and full automatic/manual billing management.

## Features

### Multi-Tenancy & Super Admin
- Super Admin portal (single top-level login)
- Create/Edit/Delete Company panels (tenant)
- Assign a custom domain/subdomain to each company (white-label)
- Enable / Disable any company panel (re-enable later)
- Configure per-company billing_day (default: 10)
- See per-company summary: active customers, revenue, due, reseller stats
- Global settings: default VAT, global packages (optional), global templates

### Company Admin Panel
- Roles & Permission Editor with default roles
- POP / Zone Management
- MikroTik Management
- Package & Profile Management
- Customer Management
- Support / Token System
- SMS Module
- Reports & Accounting
- Bandwidth Buy/Sell
- Admin Tools

## Tech Stack
- Backend: Laravel (PHP 8.x)
- Frontend: Vue 3 + Tailwind CSS (responsive)
- Database: PostgreSQL
- Queue: Redis + Laravel Queue
- Auth: Laravel Sanctum or Passport
- MikroTik client: routeros-api-php (RouterOS API)
- PDF: barryvdh/laravel-dompdf
- Excel/CSV: maatwebsite/excel
- Role & Permission: spatie/laravel-permission
- Containerization: Docker (Nginx + Supervisor)
- Optional realtime: Laravel Echo + Pusher / Socket.io

## Directory Structure
For a complete overview of the directory structure, please refer to [DIRECTORY_STRUCTURE.md](DIRECTORY_STRUCTURE.md)

## Project Summary
For a comprehensive summary of all files and directories created, please refer to [PROJECT_SUMMARY.md](PROJECT_SUMMARY.md)

## Installation

1. Clone the repository
2. Run `composer install`
3. Copy `.env.example` to `.env` and configure your environment
4. Run `php artisan key:generate`
5. Run `php artisan migrate`
6. Start the development server with `php artisan serve`

## Deployment

For deployment instructions, please refer to [DEPLOYMENT.md](DEPLOYMENT.md)

## Requirements
- PHP 8.0 or higher
- PostgreSQL 13 or higher
- Redis
- Composer
- Node.js and NPM

## Documentation
For detailed documentation, please refer to the individual component files in the project directory.

## License
This project is licensed under the MIT License.
