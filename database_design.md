# Database Schema Design

## Overview
This document outlines the database schema for the ISP Billing & CRM system. The schema is designed to support multi-tenancy, customer management, billing, reseller systems, POP management, MikroTik integration, SMS notifications, and support tickets.

## Multi-Tenancy Core Tables

### companies
Stores information about each company/tenant in the system.

```sql
CREATE TABLE companies (
    id BIGSERIAL PRIMARY KEY,
    name VARCHAR(255) NOT NULL,
    domain VARCHAR(255) UNIQUE,
    subdomain VARCHAR(255) UNIQUE,
    status VARCHAR(20) DEFAULT 'active', -- active, inactive, suspended
    billing_day INTEGER DEFAULT 10, -- Day of month for billing (1-28)
    vat_percent DECIMAL(5,2) DEFAULT 0.00,
    currency VARCHAR(3) DEFAULT 'BDT',
    timezone VARCHAR(50) DEFAULT 'Asia/Dhaka',
    logo_path TEXT,
    created_at TIMESTAMP WITH TIME ZONE DEFAULT CURRENT_TIMESTAMP,
    updated_at TIMESTAMP WITH TIME ZONE DEFAULT CURRENT_TIMESTAMP,
    deleted_at TIMESTAMP WITH TIME ZONE NULL
);
```

### company_settings
Stores company-specific settings and configurations.

```sql
CREATE TABLE company_settings (
    id BIGSERIAL PRIMARY KEY,
    company_id BIGINT NOT NULL REFERENCES companies(id),
    key VARCHAR(100) NOT NULL,
    value TEXT,
    created_at TIMESTAMP WITH TIME ZONE DEFAULT CURRENT_TIMESTAMP,
    updated_at TIMESTAMP WITH TIME ZONE DEFAULT CURRENT_TIMESTAMP,
    UNIQUE(company_id, key)
);
```

## User Management Tables

### users
Stores all users across all companies (super admin, company admins, resellers, customers).

```sql
CREATE TABLE users (
    id BIGSERIAL PRIMARY KEY,
    company_id BIGINT REFERENCES companies(id),
    name VARCHAR(255) NOT NULL,
    email VARCHAR(255) UNIQUE,
    phone VARCHAR(20),
    username VARCHAR(100) UNIQUE,
    password VARCHAR(255),
    user_type VARCHAR(20) NOT NULL, -- super_admin, company_admin, reseller, employee, customer
    status VARCHAR(20) DEFAULT 'active', -- active, inactive, suspended
    last_login TIMESTAMP WITH TIME ZONE,
    email_verified_at TIMESTAMP WITH TIME ZONE,
    created_at TIMESTAMP WITH TIME ZONE DEFAULT CURRENT_TIMESTAMP,
    updated_at TIMESTAMP WITH TIME ZONE DEFAULT CURRENT_TIMESTAMP,
    deleted_at TIMESTAMP WITH TIME ZONE NULL
);
```

### roles
Stores roles for role-based access control.

```sql
CREATE TABLE roles (
    id BIGSERIAL PRIMARY KEY,
    company_id BIGINT REFERENCES companies(id),
    name VARCHAR(255) NOT NULL,
    guard_name VARCHAR(255) NOT NULL,
    created_at TIMESTAMP WITH TIME ZONE DEFAULT CURRENT_TIMESTAMP,
    updated_at TIMESTAMP WITH TIME ZONE DEFAULT CURRENT_TIMESTAMP
);
```

### permissions
Stores permissions for role-based access control.

```sql
CREATE TABLE permissions (
    id BIGSERIAL PRIMARY KEY,
    company_id BIGINT REFERENCES companies(id),
    name VARCHAR(255) NOT NULL,
    guard_name VARCHAR(255) NOT NULL,
    created_at TIMESTAMP WITH TIME ZONE DEFAULT CURRENT_TIMESTAMP,
    updated_at TIMESTAMP WITH TIME ZONE DEFAULT CURRENT_TIMESTAMP
);
```

### model_has_roles
Associates users with roles.

```sql
CREATE TABLE model_has_roles (
    role_id BIGINT NOT NULL,
    model_type VARCHAR(255) NOT NULL,
    model_id BIGINT NOT NULL,
    company_id BIGINT REFERENCES companies(id),
    PRIMARY KEY (role_id, model_type, model_id)
);
```

### model_has_permissions
Associates users with permissions.

```sql
CREATE TABLE model_has_permissions (
    permission_id BIGINT NOT NULL,
    model_type VARCHAR(255) NOT NULL,
    model_id BIGINT NOT NULL,
    company_id BIGINT REFERENCES companies(id),
    PRIMARY KEY (permission_id, model_type, model_id)
);
```

### role_has_permissions
Associates roles with permissions.

```sql
CREATE TABLE role_has_permissions (
    permission_id BIGINT NOT NULL,
    role_id BIGINT NOT NULL,
    PRIMARY KEY (permission_id, role_id)
);
```

## Customer Management Tables

### customers
Stores customer information.

```sql
CREATE TABLE customers (
    id BIGSERIAL PRIMARY KEY,
    company_id BIGINT NOT NULL REFERENCES companies(id),
    name VARCHAR(255) NOT NULL,
    phone VARCHAR(20),
    email VARCHAR(255),
    username VARCHAR(100),
    password VARCHAR(255),
    nid VARCHAR(50),
    ip_address INET,
    mac_address VARCHAR(17),
    package_id BIGINT REFERENCES packages(id),
    pop_id BIGINT REFERENCES pops(id),
    router_id BIGINT REFERENCES mikrotik_routers(id),
    reseller_id BIGINT REFERENCES users(id),
    customer_type VARCHAR(20) DEFAULT 'home', -- home, free, vip, corporate
    status VARCHAR(20) DEFAULT 'active', -- active, inactive, suspended, expired, deleted
    activation_date DATE,
    expiry_date DATE,
    notes TEXT,
    created_at TIMESTAMP WITH TIME ZONE DEFAULT CURRENT_TIMESTAMP,
    updated_at TIMESTAMP WITH TIME ZONE DEFAULT CURRENT_TIMESTAMP,
    deleted_at TIMESTAMP WITH TIME ZONE NULL
);
```

### customer_sessions
Stores live session data from MikroTik routers.

```sql
CREATE TABLE customer_sessions (
    id BIGSERIAL PRIMARY KEY,
    customer_id BIGINT NOT NULL REFERENCES customers(id),
    router_id BIGINT REFERENCES mikrotik_routers(id),
    session_id VARCHAR(100),
    ip_address INET,
    mac_address VARCHAR(17),
    login_time TIMESTAMP WITH TIME ZONE,
    logout_time TIMESTAMP WITH TIME ZONE,
    download_bytes BIGINT DEFAULT 0,
    upload_bytes BIGINT DEFAULT 0,
    created_at TIMESTAMP WITH TIME ZONE DEFAULT CURRENT_TIMESTAMP
);
```

### customer_usage
Stores daily usage data for customers.

```sql
CREATE TABLE customer_usage (
    id BIGSERIAL PRIMARY KEY,
    customer_id BIGINT NOT NULL REFERENCES customers(id),
    usage_date DATE NOT NULL,
    download_bytes BIGINT DEFAULT 0,
    upload_bytes BIGINT DEFAULT 0,
    created_at TIMESTAMP WITH TIME ZONE DEFAULT CURRENT_TIMESTAMP,
    UNIQUE(customer_id, usage_date)
);
```

## Package & Profile Management Tables

### packages
Stores package information linked to MikroTik profiles.

```sql
CREATE TABLE packages (
    id BIGSERIAL PRIMARY KEY,
    company_id BIGINT NOT NULL REFERENCES companies(id),
    name VARCHAR(255) NOT NULL,
    speed VARCHAR(50),
    price DECIMAL(10,2) NOT NULL,
    vat_percent DECIMAL(5,2) DEFAULT 0.00,
    fup_limit BIGINT, -- Fair usage policy limit in bytes
    duration INTEGER DEFAULT 30, -- Duration in days
    is_expired_package BOOLEAN DEFAULT FALSE,
    mikrotik_profile_id BIGINT REFERENCES mikrotik_profiles(id),
    created_at TIMESTAMP WITH TIME ZONE DEFAULT CURRENT_TIMESTAMP,
    updated_at TIMESTAMP WITH TIME ZONE DEFAULT CURRENT_TIMESTAMP,
    deleted_at TIMESTAMP WITH TIME ZONE NULL
);
```

### package_reseller
Many-to-many relationship between packages and resellers.

```sql
CREATE TABLE package_reseller (
    package_id BIGINT NOT NULL REFERENCES packages(id),
    reseller_id BIGINT NOT NULL REFERENCES users(id),
    company_id BIGINT NOT NULL REFERENCES companies(id),
    PRIMARY KEY (package_id, reseller_id)
);
```

### mikrotik_profiles
Stores MikroTik profile information.

```sql
CREATE TABLE mikrotik_profiles (
    id BIGSERIAL PRIMARY KEY,
    router_id BIGINT NOT NULL REFERENCES mikrotik_routers(id),
    profile_name VARCHAR(255) NOT NULL,
    profile_id VARCHAR(100) NOT NULL, -- MikroTik profile ID
    created_at TIMESTAMP WITH TIME ZONE DEFAULT CURRENT_TIMESTAMP,
    updated_at TIMESTAMP WITH TIME ZONE DEFAULT CURRENT_TIMESTAMP
);
```

## POP & MikroTik Management Tables

### pops
Stores Point of Presence information.

```sql
CREATE TABLE pops (
    id BIGSERIAL PRIMARY KEY,
    company_id BIGINT NOT NULL REFERENCES companies(id),
    name VARCHAR(255) NOT NULL,
    address TEXT,
    contact_person VARCHAR(255),
    contact_phone VARCHAR(20),
    status VARCHAR(20) DEFAULT 'active', -- active, inactive
    created_at TIMESTAMP WITH TIME ZONE DEFAULT CURRENT_TIMESTAMP,
    updated_at TIMESTAMP WITH TIME ZONE DEFAULT CURRENT_TIMESTAMP,
    deleted_at TIMESTAMP WITH TIME ZONE NULL
);
```

### pop_reseller
Many-to-many relationship between POPs and resellers.

```sql
CREATE TABLE pop_reseller (
    pop_id BIGINT NOT NULL REFERENCES pops(id),
    reseller_id BIGINT NOT NULL REFERENCES users(id),
    company_id BIGINT NOT NULL REFERENCES companies(id),
    PRIMARY KEY (pop_id, reseller_id)
);
```

### mikrotik_routers
Stores MikroTik router information.

```sql
CREATE TABLE mikrotik_routers (
    id BIGSERIAL PRIMARY KEY,
    company_id BIGINT NOT NULL REFERENCES companies(id),
    pop_id BIGINT REFERENCES pops(id),
    name VARCHAR(255) NOT NULL,
    ip_address INET NOT NULL,
    port INTEGER DEFAULT 8728,
    username VARCHAR(100) NOT NULL,
    password VARCHAR(255) NOT NULL, -- encrypted
    status VARCHAR(20) DEFAULT 'active', -- active, inactive
    created_at TIMESTAMP WITH TIME ZONE DEFAULT CURRENT_TIMESTAMP,
    updated_at TIMESTAMP WITH TIME ZONE DEFAULT CURRENT_TIMESTAMP,
    deleted_at TIMESTAMP WITH TIME ZONE NULL
);
```

## Billing & Invoice Tables

### invoices
Stores invoice information.

```sql
CREATE TABLE invoices (
    id BIGSERIAL PRIMARY KEY,
    company_id BIGINT NOT NULL REFERENCES companies(id),
    customer_id BIGINT NOT NULL REFERENCES customers(id),
    invoice_number VARCHAR(50) UNIQUE,
    billing_date DATE NOT NULL,
    due_date DATE NOT NULL,
    base_price DECIMAL(10,2) NOT NULL,
    vat_amount DECIMAL(10,2) NOT NULL,
    total_amount DECIMAL(10,2) NOT NULL,
    status VARCHAR(20) DEFAULT 'unpaid', -- paid, unpaid, partial, cancelled
    payment_date TIMESTAMP WITH TIME ZONE,
    notes TEXT,
    created_at TIMESTAMP WITH TIME ZONE DEFAULT CURRENT_TIMESTAMP,
    updated_at TIMESTAMP WITH TIME ZONE DEFAULT CURRENT_TIMESTAMP
);
```

### payments
Stores payment records.

```sql
CREATE TABLE payments (
    id BIGSERIAL PRIMARY KEY,
    company_id BIGINT NOT NULL REFERENCES companies(id),
    customer_id BIGINT NOT NULL REFERENCES customers(id),
    invoice_id BIGINT REFERENCES invoices(id),
    amount DECIMAL(10,2) NOT NULL,
    payment_method VARCHAR(20) NOT NULL, -- receive, due, online
    payment_gateway VARCHAR(50), -- bKash, Nagad, Rocket, etc.
    operator_id BIGINT REFERENCES users(id),
    transaction_id VARCHAR(100),
    payment_date TIMESTAMP WITH TIME ZONE,
    notes TEXT,
    created_at TIMESTAMP WITH TIME ZONE DEFAULT CURRENT_TIMESTAMP
);
```

### recharges
Stores recharge records.

```sql
CREATE TABLE recharges (
    id BIGSERIAL PRIMARY KEY,
    company_id BIGINT NOT NULL REFERENCES companies(id),
    customer_id BIGINT NOT NULL REFERENCES customers(id),
    amount DECIMAL(10,2) NOT NULL,
    payment_method VARCHAR(20) NOT NULL, -- receive, due, online
    payment_gateway VARCHAR(50), -- bKash, Nagad, Rocket, etc.
    operator_id BIGINT REFERENCES users(id),
    notes TEXT,
    created_at TIMESTAMP WITH TIME ZONE DEFAULT CURRENT_TIMESTAMP
);
```

## Reseller & Commission Tables

### reseller_balances
Stores reseller balance information.

```sql
CREATE TABLE reseller_balances (
    id BIGSERIAL PRIMARY KEY,
    company_id BIGINT NOT NULL REFERENCES companies(id),
    reseller_id BIGINT NOT NULL REFERENCES users(id),
    balance DECIMAL(10,2) NOT NULL DEFAULT 0.00,
    created_at TIMESTAMP WITH TIME ZONE DEFAULT CURRENT_TIMESTAMP,
    updated_at TIMESTAMP WITH TIME ZONE DEFAULT CURRENT_TIMESTAMP
);
```

### reseller_employees
Stores reseller employee information.

```sql
CREATE TABLE reseller_employees (
    id BIGSERIAL PRIMARY KEY,
    company_id BIGINT NOT NULL REFERENCES companies(id),
    reseller_id BIGINT NOT NULL REFERENCES users(id),
    employee_id BIGINT NOT NULL REFERENCES users(id),
    balance DECIMAL(10,2) NOT NULL DEFAULT 0.00,
    created_at TIMESTAMP WITH TIME ZONE DEFAULT CURRENT_TIMESTAMP,
    updated_at TIMESTAMP WITH TIME ZONE DEFAULT CURRENT_TIMESTAMP
);
```

### reseller_commissions
Stores reseller commission information.

```sql
CREATE TABLE reseller_commissions (
    id BIGSERIAL PRIMARY KEY,
    company_id BIGINT NOT NULL REFERENCES companies(id),
    reseller_id BIGINT NOT NULL REFERENCES users(id),
    customer_id BIGINT REFERENCES customers(id),
    invoice_id BIGINT REFERENCES invoices(id),
    base_amount DECIMAL(10,2) NOT NULL,
    commission_percent DECIMAL(5,2) NOT NULL,
    commission_amount DECIMAL(10,2) NOT NULL,
    status VARCHAR(20) DEFAULT 'pending', -- pending, paid
    paid_at TIMESTAMP WITH TIME ZONE,
    created_at TIMESTAMP WITH TIME ZONE DEFAULT CURRENT_TIMESTAMP
);
```

### fund_transfers
Stores fund transfer records.

```sql
CREATE TABLE fund_transfers (
    id BIGSERIAL PRIMARY KEY,
    company_id BIGINT NOT NULL REFERENCES companies(id),
    from_user_id BIGINT REFERENCES users(id),
    to_user_id BIGINT REFERENCES users(id),
    transfer_type VARCHAR(50) NOT NULL, -- from_admin_to_reseller, reseller_to_employee, reseller_commission_payout
    amount DECIMAL(10,2) NOT NULL,
    notes TEXT,
    created_at TIMESTAMP WITH TIME ZONE DEFAULT CURRENT_TIMESTAMP
);
```

## SMS & Notification Tables

### sms_gateways
Stores SMS gateway configurations.

```sql
CREATE TABLE sms_gateways (
    id BIGSERIAL PRIMARY KEY,
    company_id BIGINT NOT NULL REFERENCES companies(id),
    name VARCHAR(255) NOT NULL,
    gateway_url TEXT NOT NULL,
    http_method VARCHAR(10) DEFAULT 'GET', -- GET, POST, JSON
    params JSONB,
    headers JSONB,
    is_active BOOLEAN DEFAULT TRUE,
    balance DECIMAL(10,2) DEFAULT 0.00,
    created_at TIMESTAMP WITH TIME ZONE DEFAULT CURRENT_TIMESTAMP,
    updated_at TIMESTAMP WITH TIME ZONE DEFAULT CURRENT_TIMESTAMP
);
```

### sms_templates
Stores SMS templates.

```sql
CREATE TABLE sms_templates (
    id BIGSERIAL PRIMARY KEY,
    company_id BIGINT NOT NULL REFERENCES companies(id),
    name VARCHAR(255) NOT NULL,
    template TEXT NOT NULL,
    variables TEXT, -- JSON array of available variables
    is_active BOOLEAN DEFAULT TRUE,
    created_at TIMESTAMP WITH TIME ZONE DEFAULT CURRENT_TIMESTAMP,
    updated_at TIMESTAMP WITH TIME ZONE DEFAULT CURRENT_TIMESTAMP
);
```

### sms_logs
Stores SMS sending logs.

```sql
CREATE TABLE sms_logs (
    id BIGSERIAL PRIMARY KEY,
    company_id BIGINT NOT NULL REFERENCES companies(id),
    customer_id BIGINT REFERENCES customers(id),
    gateway_id BIGINT REFERENCES sms_gateways(id),
    template_id BIGINT REFERENCES sms_templates(id),
    phone_number VARCHAR(20) NOT NULL,
    message TEXT NOT NULL,
    status VARCHAR(20) DEFAULT 'pending', -- pending, sent, failed
    response TEXT,
    sent_at TIMESTAMP WITH TIME ZONE,
    created_at TIMESTAMP WITH TIME ZONE DEFAULT CURRENT_TIMESTAMP
);
```

## Support & Ticket Tables

### support_tickets
Stores support ticket information.

```sql
CREATE TABLE support_tickets (
    id BIGSERIAL PRIMARY KEY,
    company_id BIGINT NOT NULL REFERENCES companies(id),
    customer_id BIGINT REFERENCES customers(id),
    assigned_to BIGINT REFERENCES users(id),
    category VARCHAR(100),
    subject VARCHAR(255) NOT NULL,
    description TEXT,
    priority VARCHAR(20) DEFAULT 'medium', -- low, medium, high, urgent
    status VARCHAR(20) DEFAULT 'open', -- open, in_progress, resolved, closed
    created_at TIMESTAMP WITH TIME ZONE DEFAULT CURRENT_TIMESTAMP,
    updated_at TIMESTAMP WITH TIME ZONE DEFAULT CURRENT_TIMESTAMP
);
```

### support_tokens
Stores support tokens.

```sql
CREATE TABLE support_tokens (
    id BIGSERIAL PRIMARY KEY,
    company_id BIGINT NOT NULL REFERENCES companies(id),
    customer_id BIGINT REFERENCES customers(id),
    ticket_id BIGINT REFERENCES support_tickets(id),
    token_number VARCHAR(50) UNIQUE,
    category VARCHAR(100),
    assigned_to BIGINT REFERENCES users(id),
    status VARCHAR(20) DEFAULT 'open', -- open, in_progress, resolved, closed
    printed BOOLEAN DEFAULT FALSE,
    created_at TIMESTAMP WITH TIME ZONE DEFAULT CURRENT_TIMESTAMP,
    updated_at TIMESTAMP WITH TIME ZONE DEFAULT CURRENT_TIMESTAMP
);
```

### ticket_attachments
Stores ticket attachments.

```sql
CREATE TABLE ticket_attachments (
    id BIGSERIAL PRIMARY KEY,
    ticket_id BIGINT NOT NULL REFERENCES support_tickets(id),
    file_name VARCHAR(255) NOT NULL,
    file_path TEXT NOT NULL,
    file_size BIGINT,
    created_at TIMESTAMP WITH TIME ZONE DEFAULT CURRENT_TIMESTAMP
);
```

### ticket_logs
Stores ticket activity logs.

```sql
CREATE TABLE ticket_logs (
    id BIGSERIAL PRIMARY KEY,
    ticket_id BIGINT NOT NULL REFERENCES support_tickets(id),
    user_id BIGINT REFERENCES users(id),
    action VARCHAR(100) NOT NULL,
    description TEXT,
    created_at TIMESTAMP WITH TIME ZONE DEFAULT CURRENT_TIMESTAMP
);
```

## Reporting Tables

### bandwidth_purchases
Stores upstream bandwidth purchase records.

```sql
CREATE TABLE bandwidth_purchases (
    id BIGSERIAL PRIMARY KEY,
    company_id BIGINT NOT NULL REFERENCES companies(id),
    provider VARCHAR(100) NOT NULL, -- IIG, PNI, CDN, GGC, BDIX
    bandwidth INTEGER NOT NULL, -- in Mbps
    price DECIMAL(10,2) NOT NULL,
    purchase_date DATE NOT NULL,
    expiry_date DATE NOT NULL,
    notes TEXT,
    created_at TIMESTAMP WITH TIME ZONE DEFAULT CURRENT_TIMESTAMP
);
```

### bandwidth_sales
Stores bandwidth sale records to resellers/customers.

```sql
CREATE TABLE bandwidth_sales (
    id BIGSERIAL PRIMARY KEY,
    company_id BIGINT NOT NULL REFERENCES companies(id),
    customer_id BIGINT REFERENCES customers(id),
    reseller_id BIGINT REFERENCES users(id),
    bandwidth INTEGER NOT NULL, -- in Mbps
    price DECIMAL(10,2) NOT NULL,
    sale_date DATE NOT NULL,
    notes TEXT,
    created_at TIMESTAMP WITH TIME ZONE DEFAULT CURRENT_TIMESTAMP
);
```

## Bulk Operations Tables

### bulk_imports
Stores bulk import records.

```sql
CREATE TABLE bulk_imports (
    id BIGSERIAL PRIMARY KEY,
    company_id BIGINT NOT NULL REFERENCES companies(id),
    user_id BIGINT NOT NULL REFERENCES users(id),
    file_name VARCHAR(255) NOT NULL,
    file_path TEXT NOT NULL,
    total_records INTEGER NOT NULL,
    success_records INTEGER DEFAULT 0,
    failed_records INTEGER DEFAULT 0,
    status VARCHAR(20) DEFAULT 'pending', -- pending, processing, completed, failed
    error_log TEXT,
    created_at TIMESTAMP WITH TIME ZONE DEFAULT CURRENT_TIMESTAMP,
    completed_at TIMESTAMP WITH TIME ZONE
);
```

## Indexes

```sql
-- Multi-tenancy indexes
CREATE INDEX idx_users_company_id ON users(company_id);
CREATE INDEX idx_customers_company_id ON customers(company_id);
CREATE INDEX idx_packages_company_id ON packages(company_id);
CREATE INDEX idx_pops_company_id ON pops(company_id);
CREATE INDEX idx_mikrotik_routers_company_id ON mikrotik_routers(company_id);
CREATE INDEX idx_invoices_company_id ON invoices(company_id);
CREATE INDEX idx_payments_company_id ON payments(company_id);
CREATE INDEX idx_recharges_company_id ON recharges(company_id);
CREATE INDEX idx_reseller_balances_company_id ON reseller_balances(company_id);
CREATE INDEX idx_sms_gateways_company_id ON sms_gateways(company_id);
CREATE INDEX idx_sms_templates_company_id ON sms_templates(company_id);
CREATE INDEX idx_sms_logs_company_id ON sms_logs(company_id);
CREATE INDEX idx_support_tickets_company_id ON support_tickets(company_id);
CREATE INDEX idx_support_tokens_company_id ON support_tokens(company_id);
CREATE INDEX idx_bandwidth_purchases_company_id ON bandwidth_purchases(company_id);
CREATE INDEX idx_bandwidth_sales_company_id ON bandwidth_sales(company_id);
CREATE INDEX idx_bulk_imports_company_id ON bulk_imports(company_id);

-- Performance indexes
CREATE INDEX idx_customers_username ON customers(username);
CREATE INDEX idx_customers_phone ON customers(phone);
CREATE INDEX idx_customers_expiry_date ON customers(expiry_date);
CREATE INDEX idx_customers_status ON customers(status);
CREATE INDEX idx_invoices_customer_id ON invoices(customer_id);
CREATE INDEX idx_invoices_status ON invoices(status);
CREATE INDEX idx_payments_customer_id ON payments(customer_id);
CREATE INDEX idx_payments_invoice_id ON payments(invoice_id);
CREATE INDEX idx_customer_usage_customer_id ON customer_usage(customer_id);
CREATE INDEX idx_customer_usage_date ON customer_usage(usage_date);
CREATE INDEX idx_customer_sessions_customer_id ON customer_sessions(customer_id);
CREATE INDEX idx_customer_sessions_login_time ON customer_sessions(login_time);
```