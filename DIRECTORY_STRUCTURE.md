# Directory Structure Summary

This document summarizes all the directories created for the ISP Billing & CRM system.

## Application Directories

### app/
- Contains all core application logic
- Subdirectories:
  - Console/Commands - Custom Artisan commands
  - Exceptions - Custom exception classes
  - Http/ - HTTP layer components
    - Controllers/ - Controller classes
      - Api/V1/ - API version 1 controllers
        - Admin/ - Admin API controllers
        - Customer/ - Customer API controllers
        - Reseller/ - Reseller API controllers
        - SuperAdmin/ - Super Admin API controllers
      - Web/ - Web controllers
        - Admin/ - Admin web controllers
        - Customer/ - Customer web controllers
        - Reseller/ - Reseller web controllers
        - SuperAdmin/ - Super Admin web controllers
      - SuperAdmin/ - Super Admin controllers
    - Middleware/ - Custom middleware
    - Requests/ - Form request classes
    - Resources/ - API resource classes
  - Models/ - Eloquent models
  - Providers/ - Service providers
  - Services/ - Business logic services
  - Repositories/ - Data access repositories
  - Jobs/ - Queueable jobs
  - Events/ - Event classes
  - Listeners/ - Event listeners
  - Notifications/ - Notification classes
  - Rules/ - Custom validation rules
  - Traits/ - Reusable traits
  - Helpers/ - Helper functions

### bootstrap/
- Application bootstrap files
- cache/ - Bootstrap cache files

### config/
- Application configuration files
- app.php - Main application configuration
- tenancy.php - Multi-tenancy configuration

### database/
- Database related files
- factories/ - Model factories for testing
- migrations/ - Database migration files
- seeders/ - Database seeders

### public/
- Publicly accessible files
- css/ - CSS stylesheets
- js/ - JavaScript files
- images/ - Image assets

### resources/
- Application resources
- js/ - JavaScript source files
- css/ - CSS source files
- views/ - Blade template files

### routes/
- Route definitions
- api.php - API routes
- superadmin.php - Super Admin routes

### storage/
- Storage for generated files
- app/ - Application storage
- framework/ - Framework storage
  - cache/ - Cache files
  - sessions/ - Session files
  - views/ - Compiled views
- logs/ - Log files

## Deployment Directories

### Deployment scripts (created for remote server upload)
- deploy.ps1 - PowerShell deployment script
- deploy-run.bat - Windows batch file to run PowerShell script
- deploy.sh - Bash deployment script
- deploy.bat - Batch file for PuTTY tools
- test-ssh.bat - SSH connectivity test script
- DEPLOYMENT.md - Deployment instructions
- DEPLOYMENT_SUMMARY.md - Deployment files summary

## Documentation Files

### Documentation files (created during planning phase)
- README.md - Project overview
- core.md - Project requirements
- architecture.md - System architecture
- database_design.md - Database schema
- laravel_structure.md - Laravel structure plan
- vue_structure.md - Vue structure plan
- docker_setup.md - Docker configuration
- billing_logic.md - Billing implementation plan
- mikrotik_integration.md - MikroTik integration plan
- sms_module.md - SMS module plan
- reseller_system.md - Reseller system plan
- api_structure.md - API structure plan
- multitenancy_approach.md - Multi-tenancy implementation
- role_permission_system.md - Role and permission system
- bulk_operations.md - Bulk operations implementation
- reporting_system.md - Reporting system architecture
- support_ticket_system.md - Support ticket system
- DIRECTORY_STRUCTURE.md - This file