# ISP Billing & CRM System Architecture

## High-Level Architecture

```mermaid
graph TD
    A[Client Devices] --> B[Load Balancer]
    B --> C[Web Server Cluster]
    C --> D[(PostgreSQL Database)]
    C --> E[(Redis Cache/Queue)]
    C --> F[MikroTik Routers]
    C --> G[SMS Gateways]
    C --> H[Payment Gateways]
    
    I[Super Admin] --> B
    J[Company Admins] --> B
    K[Resellers] --> B
    L[Customers] --> B
    M[API Clients] --> B
    
    subgraph "Application Layer"
        C
    end
    
    subgraph "Data Layer"
        D
        E
    end
    
    subgraph "External Services"
        F
        G
        H
    end
    
    subgraph "User Roles"
        I
        J
        K
        L
        M
    end
```

## System Components

### 1. Frontend Layer
- Vue 3 + Tailwind CSS responsive web application
- Separate interfaces for:
  - Super Admin portal
  - Company Admin panels
  - Reseller panels
  - Customer portal
  - Mobile-responsive design

### 2. Backend Layer (Laravel PHP 8.x)
- Multi-tenant architecture with domain-based company panels
- RESTful API for web and mobile clients
- Authentication system (Laravel Sanctum/Passport)
- Role-based access control (spatie/laravel-permission)
- Queue processing (Redis + Laravel Queue)
- PDF generation (barryvdh/laravel-dompdf)
- Excel/CSV processing (maatwebsite/excel)

### 3. Database Layer (PostgreSQL)
- Shared database with company_id scoping for multi-tenancy
- Logical data isolation between companies
- Support for schema-based tenant separation (optional)

### 4. Integration Layer
- MikroTik RouterOS API integration for PPPoE management
- SMS gateway integrations (HTTP GET/POST/JSON)
- Payment gateway integrations (bKash/Nagad/Rocket)
- Real-time notifications (Laravel Echo + Pusher/Socket.io)

### 5. Infrastructure Layer
- Docker containerization (Nginx + Supervisor)
- Redis for caching and queue processing
- PostgreSQL for primary data storage