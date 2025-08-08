PROJECT PROMPT — Full ISP Billing & CRM (Multi-tenant) with MikroTik PPPoE, Resellers, POP, SMS, and Full Billing Logic

Goal:
Build a production-ready, multi-tenant ISP Billing & CRM application (white-label) with domain-mapped company panels, MikroTik RouterOS (PPPoE) integration, POP management, reseller balance & commission system, SMS notifications, support token system, bulk customer import, and full automatic/manual billing management based on the exact rules below.

TECH STACK (preferred):
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

MULTI-TENANCY & SUPER ADMIN
- Super Admin portal (single top-level login).
- Super Admin can:
  - Create/Edit/Delete Company panels (tenant).
  - Assign a custom domain/subdomain to each company (white-label).
  - Enable / Disable any company panel (re-enable later).
  - Configure per-company billing_day (default: 10) — bills for the company are generated on this day every month.
  - See per-company summary: active customers, revenue, due, reseller stats.
  - Global settings: default VAT, global packages (optional), global templates.
- Company tenants are isolated logically (shared DB with company_id scoping or tenant schemas — choose implementation but ensure data isolation).
- When enabling a disabled company panel, all company settings, customers, and data are restored.

COMPANY ADMIN PANEL (per company)
- Roles & Permission Editor:
  - Default roles: Admin, Reseller, Admin Support, Billing Manager, Reseller Employee/Billing, API User, Sub-Reseller.
  - Admin can create/edit/delete custom roles and toggle granular permissions per module (CRUD + special actions).
- POP / Zone Management:
  - CRUD POP.
  - Assign MikroTik router(s) to a POP.
  - Assign a POP to a Reseller (one or multiple).
  - POP list UI must display counts next to POP name: Online, Offline, Expired, Disabled customers.
- MikroTik Management:
  - Add / Edit / Remove MikroTik routers (IP, port, credentials — store encrypted).
  - Assign router to POP.
  - Sync profile list from RouterOS.
  - PPPoE protocol only for customers.
  - Create / disable / delete PPPoE users via RouterOS API.
  - Fetch live user sessions & interface counters for live speed usage.
- Package & Profile Management:
  - Create/Edit/Delete packages (link to MikroTik profile).
  - Fields: name, speed, price (monthly), VAT %, FUP/limit (optional), duration (monthly), is_expired_package flag.
  - Assign packages to resellers (package visibility per reseller).
  - Sub-packages support.
  - Default "Expired" package — assigned automatically on expiry.
- Customer Management:
  - Add/Edit/Delete customers (fields: name, phone, email, username, password, NID, IP, MAC, package_id, pop_id, router_id, reseller_id, type, status, activation_date, expiry_date, notes).
  - Customer types: home, free, vip, corporate.
    - free: never billed, never disabled.
    - vip: billed monthly, but never automatically disabled for unpaid bills.
    - home/corporate: normal billing & auto-disable if unpaid after rules.
  - Customer Detail Page:
    - All customer info and editable fields in separate sections.
    - Live speed (download/upload) from RouterOS session.
    - Daily usage table (per day), session history, seasonal usage (grouped by month/season).
    - Actions: manual recharge, extend/reduce expiry (free / with charge), manual package change (free / with charge), suspend/enable, reset password, move line to another POP or router.
  - Bulk operations:
    - Bulk customer import (CSV/Excel with mapping for username, password, package, POP, reseller).
    - Bulk date extend.
    - Bulk package change.
- Support / Token System:
  - Token generation per customer, assign category, assign to staff, close/print tokens.
  - Ticket history attached to customer, escalate to manager/reseller.
- SMS Module:
  - Configure multiple SMS gateways (HTTP GET/POST/JSON).
  - Balance checker per gateway.
  - Send SMS (single/select/bulk) using templates with variables: {name},{due},{package},{phone},{ip}.
  - Auto-SMS triggers: welcome, invoice generated, payment success, expiry warning, suspension notice.
  - SMS Log (status, gateway, response).
- Reports & Accounting:
  - Account statements, invoice list, bill collection report, income/expense report, manager commission, reseller commission, cash-in-hand.
  - BTRC Customer Export format.
  - PGW response logs.
- Bandwidth Buy/Sell:
  - Record upstream purchases (IIG/PNI/CDN/GGC/BDIX).
  - Track sale invoices to resellers/customers.
  - Profit margin reports.
- Admin Tools:
  - File export (Excel/PDF).
  - Settings: company name, currency, timezone, VAT default, invoice templates, logos.

BILLING RULES (CRITICAL — EXACT LOGIC)
- Company-level bill generation:
  - Super Admin (or per-company setting) sets billing_day for each company; default is 10.
  - On company.billing_day each month, system auto-generates company-level billing run and per-customer invoices (cron job).
  - Bills count active users on that date. (active = not left/deleted; status check must be done at billing run time)
- Customer monthly billing is calendar-based:
  - Add 1 month to expiry to compute next expiry (preserve day-of-month when possible).
  - Example rule: If customer activation_date = 2025-01-31, next expiry = last day of next month (2025-02-28 or 29). General algorithm: new_expiry = add_months(current_expiry, 1) with day preservation, fallback to last_day_of_next_month if day doesn't exist.
  - If customer joined on 1st → next expiry = 1st next month (even if previous month had 31 days).
- Recharge Types (must be implemented exactly):
  1. RECEIVE (paid):
     - If customer.expiry_date < today (expired):
       - new_expiry = date_add_months(today, 1) preserving day as described.
     - Else (customer still active):
       - new_expiry = date_add_months(customer.expiry_date, 1).
     - Mark invoice/payment as PAID and record transaction.
  2. DUE (unpaid extension):
     - Same date rules as RECEIVE for new_expiry, BUT mark bill as UNPAID (due / outstanding) in invoices/payments.
     - System should still extend service as per user type rules if allowed.
- All recharges must record:
  - recharge.id, customer_id, amount, payment_method (receive/due/online), payment_gateway (if online), operator_id (who performed), created_at, notes.
- Auto-expiry actions:
  - For home/corporate: if expiry passed and invoice unpaid (after grace logic), system changes customer.package to "Expired" package and disables PPPoE user via RouterOS API.
  - For vip: do NOT disable on expiry; continue to show invoice as due.
  - For free: do NOT generate bill and do NOT disable.
- Invoice structure: base_price, VAT_amount, total_amount. Reseller commission is computed on base_price (excluding VAT).
- Proration: Add explicit support for proration rules when upgrading/downgrading mid-cycle (optional or defined as: credit unused days and charge difference).

RESELLER & BALANCE SYSTEM (must match exactly)
- Super Admin / Company Admin can add balance to a Reseller account (company_reseller.balance).
- Reseller can transfer balance to their employees (reseller_employee.balance).
- Manual recharge of a customer by reseller/employee is only allowed if the actor has sufficient balance; performing recharge deducts that balance.
- Online payments by customer (bKash/Nagad/Rocket) DO NOT use reseller/employee balances and should directly update customer invoice as paid.
- Reseller Commission:
  - Admin sets commission_pct per reseller (e.g., 5%).
  - Commission calculation: commission_amount = (customer.base_package_price * commission_pct / 100). VAT is excluded from commission calc.
  - When a payment is received for a customer assigned to a reseller, record the reseller commission and credit reseller.pending_commission (or paid if immediate).
  - Show commission reports in reseller panel: total earned, pending, paid.
- Balance & Commission logs:
  - fund_transfers (from_admin_to_reseller, reseller_to_employee, reseller_commission_payouts).

API & MOBILE
- Provide secure REST APIs for:
  - /api/auth/login (user/reseller/customer)
  - /api/customer/profile, /api/customer/usage, /api/customer/tickets
  - /api/payment/initiate, /api/payment/callback (bKash/Nagad/Rocket)
  - /api/mikrotik/live-status (for admin)
  - /api/reseller/balance-transfer
  - API token system with scopes and rate limits.

SMS & NOTIFICATIONS
- SMS templates & variables
- Auto-send SMS on events (invoice generated, payment success, expiry warning, suspension)
- SMS gateway config per company
- SMS logs and retry mechanism (queued)

SUPPORT & TICKETS
- Ticket creation from customer panel
- Token system (create/assign/close/print/search)
- Ticket assignment to support staff / managers
- Ticket logs, attachments

BULK OPERATIONS
- CSV/XLS bulk import for customers (username, password, package, pop, router, reseller).
- Validate duplicates and report errors on import.
- Bulk date extend, bulk package change, bulk disable/enable.

POP UI BEHAVIOR (explicit)
- On POP listing & sidebar, show: POP_NAME — Online(X) / Offline(Y) / Expired(Z) / Disabled(W). Clicking opens POP detail with full lists & filters.

CUSTOMER DETAIL PAGE (explicit)
- When clicking a customer, open full page with:
  - Profile info (editable per-section).
  - All change operations separate (package change, expiry extend, recharge modal, suspend/resume).
  - Live speed meter (download/upload).
  - Day-wise usage table and charts.
  - Seasonal usage (
