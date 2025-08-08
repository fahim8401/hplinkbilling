# Multi-Tenancy Implementation Approach

## Overview
This document outlines the implementation approach for multi-tenancy in the ISP Billing & CRM system. The approach focuses on domain-based tenant identification, shared database with scoping, and robust data isolation.

## Multi-Tenancy Strategy

### Domain-Based Tenant Identification
- Each company has a unique domain or subdomain
- Super Admin portal accessible via primary domain
- Tenant identification through HTTP Host header
- Fallback to subdomain-based identification

### Database Approach
- **Shared Database with Scoping**: Single PostgreSQL database with company_id scoping
- **Logical Isolation**: Data isolation through company_id constraints
- **Performance Optimization**: Proper indexing on company_id columns
- **Scalability**: Horizontal scaling through additional database instances if needed

### Tenant Context Management
- Tenant context stored in middleware
- Automatic scoping of database queries
- Cross-tenant data access prevention
- Tenant-aware model relationships

## Implementation Details

### Tenant Identification Middleware
```php
class TenantIdentification
{
    public function handle($request, Closure $next)
    {
        $host = $request->getHost();
        
        // Check if this is the super admin domain
        if ($host === config('tenancy.super_admin_domain')) {
            // Set context to super admin mode
            tenancy()->initializeSuperAdmin();
            return $next($request);
        }
        
        // Try to identify tenant by domain
        $company = Company::where('domain', $host)
            ->orWhere('subdomain', $this->extractSubdomain($host))
            ->first();
            
        if (!$company) {
            return response()->json(['error' => 'Tenant not found'], 404);
        }
        
        // Initialize tenant context
        tenancy()->initialize($company);
        
        return $next($request);
    }
    
    private function extractSubdomain($host)
    {
        $baseDomain = config('tenancy.base_domain');
        if (Str::endsWith($host, $baseDomain)) {
            return Str::before($host, '.' . $baseDomain);
        }
        return null;
    }
}
```

### Tenant Scoping Middleware
```php
class TenantScoping
{
    public function handle($request, Closure $next)
    {
        // Apply global scope to all models that belong to tenant
        $this->applyTenantScope();
        
        return $next($request);
    }
    
    private function applyTenantScope()
    {
        if (tenancy()->isInitialized() && !tenancy()->isSuperAdmin()) {
            $companyId = tenancy()->company()->id;
            
            // Apply global scope to tenant models
            foreach ($this->getTenantModels() as $model) {
                $model::addGlobalScope('company', function ($builder) use ($companyId) {
                    $builder->where('company_id', $companyId);
                });
            }
        }
    }
    
    private function getTenantModels()
    {
        return [
            Customer::class,
            Package::class,
            POP::class,
            MikrotikRouter::class,
            Invoice::class,
            Payment::class,
            // ... other tenant-specific models
        ];
    }
}
```

### Tenant-Aware Models
```php
class TenantModel extends Model
{
    protected static function boot()
    {
        parent::boot();
        
        // Auto-assign company_id on create
        static::creating(function ($model) {
            if (tenancy()->isInitialized() && !tenancy()->isSuperAdmin()) {
                $model->company_id = tenancy()->company()->id;
            }
        });
        
        // Ensure company_id matches tenant on update
        static::updating(function ($model) {
            if (tenancy()->isInitialized() && !tenancy()->isSuperAdmin()) {
                if ($model->company_id !== tenancy()->company()->id) {
                    throw new UnauthorizedAccessException('Cannot modify data from another tenant');
                }
            }
        });
    }
}
```

### Company Model
```php
class Company extends Model
{
    protected $fillable = [
        'name',
        'domain',
        'subdomain',
        'status',
        'billing_day',
        'vat_percent',
        'currency',
        'timezone',
        'logo_path'
    ];
    
    protected $casts = [
        'billing_day' => 'integer',
        'vat_percent' => 'decimal:2'
    ];
    
    public function users()
    {
        return $this->hasMany(User::class);
    }
    
    public function customers()
    {
        return $this->hasMany(Customer::class);
    }
    
    public function packages()
    {
        return $this->hasMany(Package::class);
    }
    
    public function settings()
    {
        return $this->hasMany(CompanySetting::class);
    }
    
    public function isActive()
    {
        return $this->status === 'active';
    }
    
    public function isSubdomainBased()
    {
        return !empty($this->subdomain) && empty($this->domain);
    }
}
```

## Tenant Management

### Super Admin Tenant Management
```php
class CompanyService
{
    public function createCompany($data)
    {
        // Validate domain/subdomain uniqueness
        $this->validateDomainUniqueness($data);
        
        // Create company record
        $company = Company::create($data);
        
        // Create default settings
        $this->createDefaultSettings($company);
        
        // Create default packages
        $this->createDefaultPackages($company);
        
        // Create default roles
        $this->createDefaultRoles($company);
        
        return $company;
    }
    
    public function updateCompany($company, $data)
    {
        // Validate domain/subdomain if changed
        if (isset($data['domain']) || isset($data['subdomain'])) {
            $this->validateDomainUniqueness($data, $company->id);
        }
        
        $company->update($data);
        
        return $company;
    }
    
    public function enableCompany($company)
    {
        $company->status = 'active';
        $company->save();
        
        // Restore any disabled services
        $this->restoreCompanyServices($company);
        
        return $company;
    }
    
    public function disableCompany($company)
    {
        $company->status = 'inactive';
        $company->save();
        
        // Disable services for this company
        $this->disableCompanyServices($company);
        
        return $company;
    }
    
    private function validateDomainUniqueness($data, $excludeId = null)
    {
        $query = Company::where(function ($query) use ($data) {
            if (!empty($data['domain'])) {
                $query->where('domain', $data['domain']);
            }
            
            if (!empty($data['subdomain'])) {
                $query->orWhere('subdomain', $data['subdomain']);
            }
        });
        
        if ($excludeId) {
            $query->where('id', '!=', $excludeId);
        }
        
        if ($query->exists()) {
            throw new ValidationException('Domain or subdomain already exists');
        }
    }
}
```

## Configuration Management

### Multi-Tenancy Configuration
```php
// config/tenancy.php
return [
    'super_admin_domain' => env('TENANCY_SUPER_ADMIN_DOMAIN', 'admin.example.com'),
    'base_domain' => env('TENANCY_BASE_DOMAIN', 'example.com'),
    'default_billing_day' => env('TENANCY_DEFAULT_BILLING_DAY', 10),
    'default_vat_percent' => env('TENANCY_DEFAULT_VAT_PERCENT', 0.00),
    'default_currency' => env('TENANCY_DEFAULT_CURRENCY', 'BDT'),
    'default_timezone' => env('TENANCY_DEFAULT_TIMEZONE', 'Asia/Dhaka'),
];
```

### Tenant-Aware Configuration Service
```php
class TenantConfigService
{
    public function get($key, $default = null)
    {
        if (tenancy()->isInitialized() && !tenancy()->isSuperAdmin()) {
            $company = tenancy()->company();
            $setting = CompanySetting::where('company_id', $company->id)
                ->where('key', $key)
                ->first();
                
            if ($setting) {
                return $setting->value;
            }
        }
        
        return $default;
    }
    
    public function set($key, $value)
    {
        if (tenancy()->isInitialized() && !tenancy()->isSuperAdmin()) {
            $company = tenancy()->company();
            
            CompanySetting::updateOrCreate(
                ['company_id' => $company->id, 'key' => $key],
                ['value' => $value]
            );
            
            return true;
        }
        
        return false;
    }
}
```

## Data Isolation

### Database Constraints
```php
// Migration example with company_id constraints
Schema::create('customers', function (Blueprint $table) {
    $table->id();
    $table->unsignedBigInteger('company_id');
    $table->string('name');
    $table->string('username')->unique();
    // ... other fields
    
    $table->foreign('company_id')->references('id')->on('companies');
    $table->index('company_id'); // Important for performance
    
    $table->timestamps();
    $table->softDeletes();
});
```

### Query Scoping
```php
// Example of tenant-aware query
class CustomerService
{
    public function getCustomers($filters = [])
    {
        $query = Customer::query();
        
        // Apply filters
        if (!empty($filters['status'])) {
            $query->where('status', $filters['status']);
        }
        
        if (!empty($filters['search'])) {
            $query->where(function ($q) use ($filters) {
                $q->where('name', 'like', '%' . $filters['search'] . '%')
                  ->orWhere('username', 'like', '%' . $filters['search'] . '%');
            });
        }
        
        // Tenant scoping is automatically applied by middleware
        return $query->paginate(20);
    }
}
```

## Cross-Tenant Data Access Prevention

### Security Middleware
```php
class PreventCrossTenantAccess
{
    public function handle($request, Closure $next)
    {
        // Check if request is trying to access data from another tenant
        $requestedCompanyId = $request->route('company_id') 
            ?? $request->input('company_id')
            ?? $request->query('company_id');
            
        if ($requestedCompanyId && tenancy()->isInitialized()) {
            if ($requestedCompanyId != tenancy()->company()->id && !tenancy()->isSuperAdmin()) {
                return response()->json(['error' => 'Unauthorized access to another tenant'], 403);
            }
        }
        
        return $next($request);
    }
}
```

## Performance Considerations

### Indexing Strategy
```php
// Ensure all tenant tables have company_id indexed
Schema::table('customers', function (Blueprint $table) {
    $table->index('company_id');
    $table->index(['company_id', 'status']);
    $table->index(['company_id', 'username']);
});

Schema::table('invoices', function (Blueprint $table) {
    $table->index('company_id');
    $table->index(['company_id', 'status']);
    $table->index(['company_id', 'customer_id']);
});
```

### Caching Strategy
```php
class TenantCacheService
{
    public function remember($key, $ttl, $callback)
    {
        if (tenancy()->isInitialized()) {
            $key = 'tenant:' . tenancy()->company()->id . ':' . $key;
        } else {
            $key = 'global:' . $key;
        }
        
        return cache()->remember($key, $ttl, $callback);
    }
    
    public function forget($key)
    {
        if (tenancy()->isInitialized()) {
            $key = 'tenant:' . tenancy()->company()->id . ':' . $key;
        } else {
            $key = 'global:' . $key;
        }
        
        return cache()->forget($key);
    }
}
```

## Testing Strategy

### Multi-Tenancy Tests
```php
class MultiTenancyTest extends TestCase
{
    public function test_tenant_isolation()
    {
        $company1 = Company::factory()->create();
        $company2 = Company::factory()->create();
        
        $customer1 = Customer::factory()->create(['company_id' => $company1->id]);
        $customer2 = Customer::factory()->create(['company_id' => $company2->id]);
        
        // Initialize tenant context for company1
        tenancy()->initialize($company1);
        
        // Should only see company1's customers
        $customers = Customer::all();
        $this->assertCount(1, $customers);
        $this->assertEquals($customer1->id, $customers->first()->id);
    }
    
    public function test_cross_tenant_access_prevention()
    {
        $this->expectException(UnauthorizedAccessException::class);
        
        $company1 = Company::factory()->create();
        $company2 = Company::factory()->create();
        
        // Try to access company2's data from company1 context
        tenancy()->initialize($company1);
        $customer = Customer::find(2); // Belongs to company2
        
        // This should throw an exception or return null
        $this->assertNull($customer);
    }
}
```

## Deployment Considerations

### Domain Configuration
```nginx
# Nginx configuration for multi-tenancy
server {
    listen 80;
    server_name *.example.com;
    
    location / {
        proxy_pass http://backend;
        proxy_set_header Host $host;
        proxy_set_header X-Real-IP $remote_addr;
    }
}

server {
    listen 80;
    server_name admin.example.com;
    
    location / {
        proxy_pass http://backend;
        proxy_set_header Host $host;
        proxy_set_header X-Real-IP $remote_addr;
    }
}
```

This multi-tenancy implementation approach provides a robust foundation for the ISP Billing & CRM system, ensuring data isolation, performance, and scalability while maintaining ease of management.