# Role and Permission System Implementation Plan

## Overview
This document outlines the implementation plan for the role and permission system in the ISP Billing & CRM system. The system will be based on the Spatie Laravel Permission package with multi-tenancy awareness and granular permission control.

## System Components

### 1. Default Roles
- Super Admin (system-wide)
- Company Admin
- Reseller
- Admin Support
- Billing Manager
- Reseller Employee/Billing
- API User
- Sub-Reseller

### 2. Permission Structure
- Module-based permissions (CRUD + special actions)
- Granular permission control
- Permission inheritance
- Multi-tenancy aware permissions

### 3. Role Management
- Role creation/editing/deletion
- Permission assignment to roles
- User role assignment
- Custom role creation

### 4. Permission Checking
- Controller-level permission checks
- View-level permission checks
- API endpoint protection
- Middleware-based access control

## Default Roles Implementation

### Role Definitions
```php
class RoleDefinition
{
    const ROLES = [
        'super_admin' => [
            'name' => 'Super Admin',
            'description' => 'System administrator with full access',
            'permissions' => [
                // Full access to all modules
                'company.*',
                'user.*',
                'customer.*',
                'billing.*',
                'package.*',
                'pop.*',
                'router.*',
                'reseller.*',
                'support.*',
                'report.*',
                'setting.*'
            ]
        ],
        'company_admin' => [
            'name' => 'Company Admin',
            'description' => 'Company administrator with full company access',
            'permissions' => [
                'user.*',
                'customer.*',
                'billing.*',
                'package.*',
                'pop.*',
                'router.*',
                'reseller.*',
                'support.*',
                'report.*',
                'setting.company'
            ]
        ],
        'reseller' => [
            'name' => 'Reseller',
            'description' => 'Reseller with access to assigned customers',
            'permissions' => [
                'customer.view',
                'customer.create',
                'customer.edit',
                'customer.recharge',
                'reseller.balance',
                'reseller.commission',
                'support.ticket.create',
                'support.ticket.view'
            ]
        ],
        'admin_support' => [
            'name' => 'Admin Support',
            'description' => 'Support staff with ticket management access',
            'permissions' => [
                'customer.view',
                'support.ticket.*',
                'support.token.*'
            ]
        ],
        'billing_manager' => [
            'name' => 'Billing Manager',
            'description' => 'Billing specialist with financial access',
            'permissions' => [
                'customer.view',
                'billing.*',
                'invoice.*',
                'payment.*',
                'report.financial'
            ]
        ],
        'reseller_employee' => [
            'name' => 'Reseller Employee',
            'description' => 'Reseller employee with limited access',
            'permissions' => [
                'customer.view',
                'customer.recharge',
                'support.ticket.create'
            ]
        ],
        'api_user' => [
            'name' => 'API User',
            'description' => 'User with API access only',
            'permissions' => [
                'api.customer.view',
                'api.customer.usage',
                'api.payment.create'
            ]
        ],
        'sub_reseller' => [
            'name' => 'Sub-Reseller',
            'description' => 'Sub-reseller with limited reseller access',
            'permissions' => [
                'customer.view',
                'customer.create',
                'customer.recharge',
                'support.ticket.create'
            ]
        ]
    ];
}
```

### Role Service
```php
class RoleService
{
    public function createDefaultRoles($company)
    {
        foreach (RoleDefinition::ROLES as $roleKey => $roleDefinition) {
            // Skip super_admin as it's system-wide
            if ($roleKey === 'super_admin') {
                continue;
            }
            
            $role = Role::create([
                'company_id' => $company->id,
                'name' => $roleKey,
                'guard_name' => 'web'
            ]);
            
            // Assign default permissions
            $this->assignPermissionsToRole($role, $roleDefinition['permissions']);
        }
    }
    
    public function createRole($company, $name, $permissions = [])
    {
        $role = Role::create([
            'company_id' => $company->id,
            'name' => $name,
            'guard_name' => 'web'
        ]);
        
        if (!empty($permissions)) {
            $this->assignPermissionsToRole($role, $permissions);
        }
        
        return $role;
    }
    
    public function assignPermissionsToRole($role, $permissions)
    {
        foreach ($permissions as $permission) {
            // Handle wildcard permissions
            if (Str::endsWith($permission, '.*')) {
                $module = Str::before($permission, '.*');
                $modulePermissions = $this->getModulePermissions($module);
                
                foreach ($modulePermissions as $perm) {
                    $role->givePermissionTo($perm);
                }
            } else {
                $role->givePermissionTo($permission);
            }
        }
    }
    
    private function getModulePermissions($module)
    {
        $basePermissions = [
            'view', 'create', 'edit', 'delete'
        ];
        
        $modulePermissions = [];
        foreach ($basePermissions as $permission) {
            $modulePermissions[] = $module . '.' . $permission;
        }
        
        return $modulePermissions;
    }
    
    public function assignRoleToUser($user, $role)
    {
        $user->assignRole($role);
    }
    
    public function removeRoleFromUser($user, $role)
    {
        $user->removeRole($role);
    }
}
```

## Permission Structure

### Permission Categories
```php
class PermissionCategories
{
    const CATEGORIES = [
        'company' => [
            'name' => 'Company Management',
            'permissions' => [
                'company.view' => 'View company information',
                'company.edit' => 'Edit company information',
                'company.delete' => 'Delete company',
                'company.settings' => 'Manage company settings'
            ]
        ],
        'user' => [
            'name' => 'User Management',
            'permissions' => [
                'user.view' => 'View users',
                'user.create' => 'Create users',
                'user.edit' => 'Edit users',
                'user.delete' => 'Delete users',
                'user.role' => 'Manage user roles',
                'user.permission' => 'Manage user permissions'
            ]
        ],
        'customer' => [
            'name' => 'Customer Management',
            'permissions' => [
                'customer.view' => 'View customers',
                'customer.create' => 'Create customers',
                'customer.edit' => 'Edit customers',
                'customer.delete' => 'Delete customers',
                'customer.recharge' => 'Recharge customers',
                'customer.suspend' => 'Suspend customers',
                'customer.enable' => 'Enable customers',
                'customer.reset_password' => 'Reset customer passwords',
                'customer.move_pop' => 'Move customers between POPs'
            ]
        ],
        'billing' => [
            'name' => 'Billing Management',
            'permissions' => [
                'billing.view' => 'View billing information',
                'billing.create' => 'Create invoices',
                'billing.edit' => 'Edit invoices',
                'billing.delete' => 'Delete invoices',
                'billing.payment' => 'Process payments',
                'billing.recharge' => 'Process recharges',
                'billing.report' => 'View billing reports'
            ]
        ],
        'package' => [
            'name' => 'Package Management',
            'permissions' => [
                'package.view' => 'View packages',
                'package.create' => 'Create packages',
                'package.edit' => 'Edit packages',
                'package.delete' => 'Delete packages',
                'package.assign' => 'Assign packages to resellers'
            ]
        ],
        'pop' => [
            'name' => 'POP Management',
            'permissions' => [
                'pop.view' => 'View POPs',
                'pop.create' => 'Create POPs',
                'pop.edit' => 'Edit POPs',
                'pop.delete' => 'Delete POPs',
                'pop.assign_router' => 'Assign routers to POPs',
                'pop.assign_reseller' => 'Assign resellers to POPs'
            ]
        ],
        'router' => [
            'name' => 'Router Management',
            'permissions' => [
                'router.view' => 'View routers',
                'router.create' => 'Create routers',
                'router.edit' => 'Edit routers',
                'router.delete' => 'Delete routers',
                'router.sync' => 'Sync router profiles',
                'router.session' => 'View router sessions'
            ]
        ],
        'reseller' => [
            'name' => 'Reseller Management',
            'permissions' => [
                'reseller.view' => 'View resellers',
                'reseller.create' => 'Create resellers',
                'reseller.edit' => 'Edit resellers',
                'reseller.delete' => 'Delete resellers',
                'reseller.balance' => 'Manage reseller balances',
                'reseller.commission' => 'View reseller commissions',
                'reseller.transfer' => 'Transfer funds to resellers'
            ]
        ],
        'support' => [
            'name' => 'Support Management',
            'permissions' => [
                'support.ticket.view' => 'View support tickets',
                'support.ticket.create' => 'Create support tickets',
                'support.ticket.edit' => 'Edit support tickets',
                'support.ticket.delete' => 'Delete support tickets',
                'support.ticket.assign' => 'Assign tickets to staff',
                'support.token.view' => 'View support tokens',
                'support.token.create' => 'Create support tokens',
                'support.token.close' => 'Close support tokens'
            ]
        ],
        'report' => [
            'name' => 'Reporting',
            'permissions' => [
                'report.view' => 'View reports',
                'report.export' => 'Export reports',
                'report.financial' => 'View financial reports',
                'report.usage' => 'View usage reports',
                'report.commission' => 'View commission reports'
            ]
        ],
        'setting' => [
            'name' => 'System Settings',
            'permissions' => [
                'setting.company' => 'Manage company settings',
                'setting.sms' => 'Manage SMS settings',
                'setting.mikrotik' => 'Manage MikroTik settings',
                'setting.billing' => 'Manage billing settings'
            ]
        ],
        'api' => [
            'name' => 'API Access',
            'permissions' => [
                'api.customer.view' => 'View customer via API',
                'api.customer.usage' => 'View customer usage via API',
                'api.payment.create' => 'Create payments via API',
                'api.invoice.view' => 'View invoices via API'
            ]
        ]
    ];
}
```

## Integration with Spatie Laravel Permission

### Permission Service
```php
class PermissionService
{
    public function createDefaultPermissions($company)
    {
        foreach (PermissionCategories::CATEGORIES as $categoryKey => $category) {
            foreach ($category['permissions'] as $permissionKey => $permissionDescription) {
                Permission::firstOrCreate([
                    'company_id' => $company->id,
                    'name' => $permissionKey,
                    'guard_name' => 'web'
                ], [
                    'description' => $permissionDescription
                ]);
            }
        }
    }
    
    public function createPermission($company, $name, $description = '')
    {
        return Permission::create([
            'company_id' => $company->id,
            'name' => $name,
            'guard_name' => 'web',
            'description' => $description
        ]);
    }
    
    public function assignPermissionToRole($role, $permission)
    {
        $role->givePermissionTo($permission);
    }
    
    public function removePermissionFromRole($role, $permission)
    {
        $role->revokePermissionTo($permission);
    }
    
    public function syncPermissions($role, $permissions)
    {
        $role->syncPermissions($permissions);
    }
}
```

## Multi-Tenancy Aware Permissions

### Tenant-Aware Permission Middleware
```php
class TenantPermissionMiddleware
{
    public function handle($request, Closure $next, $permission)
    {
        $user = $request->user();
        
        if (!$user) {
            return response()->json(['error' => 'Unauthorized'], 401);
        }
        
        // Check if user has permission in current tenant context
        if (tenancy()->isInitialized()) {
            $company = tenancy()->company();
            
            // Verify permission belongs to current company
            $permissionRecord = Permission::where('name', $permission)
                ->where('company_id', $company->id)
                ->first();
                
            if (!$permissionRecord) {
                return response()->json(['error' => 'Permission not found'], 403);
            }
            
            // Check if user has this permission
            if (!$user->hasPermissionTo($permissionRecord)) {
                return response()->json(['error' => 'Insufficient permissions'], 403);
            }
        } else if (tenancy()->isSuperAdmin()) {
            // Super admin has all permissions
            // Check if permission exists
            if (!Permission::where('name', $permission)->exists()) {
                return response()->json(['error' => 'Permission not found'], 403);
            }
        }
        
        return $next($request);
    }
}
```

## Permission Checking in Controllers

### Base Controller with Permission Checking
```php
class BaseController extends Controller
{
    protected function authorizePermission($permission)
    {
        $user = auth()->user();
        
        if (!$user) {
            abort(401, 'Unauthorized');
        }
        
        if (tenancy()->isInitialized()) {
            $company = tenancy()->company();
            
            // Check permission in current tenant context
            $permissionRecord = Permission::where('name', $permission)
                ->where('company_id', $company->id)
                ->first();
                
            if (!$permissionRecord || !$user->hasPermissionTo($permissionRecord)) {
                abort(403, 'Insufficient permissions');
            }
        } else if (tenancy()->isSuperAdmin()) {
            // Super admin has all permissions
            if (!Permission::where('name', $permission)->exists()) {
                abort(403, 'Permission not found');
            }
        } else {
            abort(401, 'Unauthorized');
        }
    }
}

class CustomerController extends BaseController
{
    public function index()
    {
        $this->authorizePermission('customer.view');
        
        // Controller logic here
        $customers = Customer::all();
        
        return response()->json($customers);
    }
    
    public function store(Request $request)
    {
        $this->authorizePermission('customer.create');
        
        // Controller logic here
        $customer = Customer::create($request->all());
        
        return response()->json($customer, 201);
    }
    
    public function update(Request $request, Customer $customer)
    {
        $this->authorizePermission('customer.edit');
        
        // Controller logic here
        $customer->update($request->all());
        
        return response()->json($customer);
    }
}
```

## Permission Checking in Views

### Blade Directives for Permission Checking
```php
// In AppServiceProvider boot method
Blade::directive('canPermission', function ($permission) {
    return "<?php if(auth()->user() && auth()->user()->hasPermissionTo({$permission})): ?>";
});

Blade::directive('endcanPermission', function () {
    return '<?php endif; ?>';
});

Blade::directive('canAnyPermission', function ($permissions) {
    return "<?php if(auth()->user() && auth()->user()->hasAnyPermission({$permissions})): ?>";
});

Blade::directive('endcanAnyPermission', function () {
    return '<?php endif; ?>';
});
```

### Usage in Views
```blade
{{-- Show/hide elements based on permissions --}}
@canPermission('customer.create')
    <button class="btn btn-primary" onclick="createCustomer()">
        Create Customer
    </button>
@endcanPermission

@canAnyPermission(['customer.edit', 'customer.delete'])
    <div class="customer-actions">
        @canPermission('customer.edit')
            <button class="btn btn-secondary" onclick="editCustomer({{ $customer->id }})">
                Edit
            </button>
        @endcanPermission
        
        @canPermission('customer.delete')
            <button class="btn btn-danger" onclick="deleteCustomer({{ $customer->id }})">
                Delete
            </button>
        @endcanPermission
    </div>
@endcanAnyPermission

{{-- Show different content based on role --}}
@role('reseller')
    <div class="reseller-dashboard">
        <!-- Reseller-specific content -->
        <h2>Reseller Dashboard</h2>
        <div class="commission-summary">
            <!-- Commission information -->
        </div>
    </div>
@endrole

@role('company_admin')
    <div class="admin-dashboard">
        <!-- Admin-specific content -->
        <h2>Company Admin Dashboard</h2>
        <div class="company-summary">
            <!-- Company summary information -->
        </div>
    </div>
@endrole
```

## API Endpoint Protection

### API Controller with Permission Checking
```php
class APIController extends Controller
{
    protected function checkAPIPermission($permission)
    {
        $user = auth('api')->user();
        
        if (!$user) {
            throw new AuthorizationException('Unauthorized');
        }
        
        if (tenancy()->isInitialized()) {
            $company = tenancy()->company();
            
            // Check API permission in current tenant context
            $permissionRecord = Permission::where('name', $permission)
                ->where('company_id', $company->id)
                ->first();
                
            if (!$permissionRecord || !$user->hasPermissionTo($permissionRecord)) {
                throw new AuthorizationException('Insufficient permissions');
            }
        } else if (tenancy()->isSuperAdmin()) {
            // Super admin has all permissions
            if (!Permission::where('name', $permission)->exists()) {
                throw new AuthorizationException('Permission not found');
            }
        } else {
            throw new AuthorizationException('Unauthorized');
        }
    }
}

class APICustomerController extends APIController
{
    public function index()
    {
        $this->checkAPIPermission('api.customer.view');
        
        $customers = Customer::all();
        return response()->json($customers);
    }
    
    public function usage(Customer $customer)
    {
        $this->checkAPIPermission('api.customer.usage');
        
        $usage = CustomerUsage::where('customer_id', $customer->id)
            ->orderBy('usage_date', 'desc')
            ->limit(30)
            ->get();
            
        return response()->json($usage);
    }
}
```

## Role-Based UI Rendering

### Vue Component with Permission Checking
```javascript
// composables/usePermission.js
import { computed } from 'vue'
import { useStore } from 'vuex'

export function usePermission() {
    const store = useStore()
    
    const hasPermission = (permission) => {
        const user = store.getters['auth/user']
        if (!user) return false
        
        // Check if user has the permission
        return user.permissions.includes(permission)
    }
    
    const hasAnyPermission = (permissions) => {
        const user = store.getters['auth/user']
        if (!user) return false
        
        // Check if user has any of the permissions
        return permissions.some(permission => user.permissions.includes(permission))
    }
    
    const hasRole = (role) => {
        const user = store.getters['auth/user']
        if (!user) return false
        
        // Check if user has the role
        return user.roles.includes(role)
    }
    
    return {
        hasPermission,
        hasAnyPermission,
        hasRole
    }
}
```

### Vue Component Usage
```vue
<template>
    <div>
        <!-- Show/hide elements based on permissions -->
        <button 
            v-if="hasPermission('customer.create')"
            @click="createCustomer"
            class="btn btn-primary"
        >
            Create Customer
        </button>
        
        <div v-if="hasAnyPermission(['customer.edit', 'customer.delete'])" class="customer-actions">
            <button 
                v-if="hasPermission('customer.edit')"
                @click="editCustomer(customer)"
                class="btn btn-secondary"
            >
                Edit
            </button>
            
            <button 
                v-if="hasPermission('customer.delete')"
                @click="deleteCustomer(customer)"
                class="btn btn-danger"
            >
                Delete
            </button>
        </div>
        
        <!-- Show different content based on role -->
        <div v-if="hasRole('reseller')" class="reseller-dashboard">
            <h2>Reseller Dashboard</h2>
            <ResellerCommissionSummary />
        </div>
        
        <div v-if="hasRole('company_admin')" class="admin-dashboard">
            <h2>Company Admin Dashboard</h2>
            <CompanySummary />
        </div>
    </div>
</template>

<script>
import { usePermission } from '@/composables/usePermission'
import ResellerCommissionSummary from '@/components/ResellerCommissionSummary.vue'
import CompanySummary from '@/components/CompanySummary.vue'

export default {
    components: {
        ResellerCommissionSummary,
        CompanySummary
    },
    setup() {
        const { hasPermission, hasAnyPermission, hasRole } = usePermission()
        
        return {
            hasPermission,
            hasAnyPermission,
            hasRole
        }
    },
    methods: {
        createCustomer() {
            // Create customer logic
        },
        editCustomer(customer) {
            // Edit customer logic
        },
        deleteCustomer(customer) {
            // Delete customer logic
        }
    }
}
</script>
```

## Testing Strategy

### Permission Tests
```php
class PermissionTest extends TestCase
{
    public function test_role_creation()
    {
        $company = Company::factory()->create();
        $roleService = new RoleService();
        
        $role = $roleService->createRole($company, 'Test Role', ['customer.view', 'customer.create']);
        
        $this->assertDatabaseHas('roles', [
            'company_id' => $company->id,
            'name' => 'Test Role'
        ]);
        
        $this->assertTrue($role->hasPermissionTo('customer.view'));
        $this->assertTrue($role->hasPermissionTo('customer.create'));
    }
    
    public function test_permission_checking()
    {
        $user = User::factory()->create();
        $company = Company::factory()->create();
        
        // Initialize tenant context
        tenancy()->initialize($company);
        
        // Assign role to user
        $user->assignRole('company_admin');
        
        // Test permission checking
        $this->assertTrue($user->hasPermissionTo('customer.view'));
        $this->assertTrue($user->hasPermissionTo('customer.create'));
        $this->assertTrue($user->hasPermissionTo('customer.edit'));
        $this->assertTrue($user->hasPermissionTo('customer.delete'));
    }
    
    public function test_cross_tenant_permission_isolation()
    {
        $company1 = Company::factory()->create();
        $company2 = Company::factory()->create();
        
        $user = User::factory()->create(['company_id' => $company1->id]);
        
        // Initialize tenant context for company1
        tenancy()->initialize($company1);
        $user->assignRole('company_admin');
        
        // User should have permissions in company1
        $this->assertTrue($user->hasPermissionTo('customer.view'));
        
        // Initialize tenant context for company2
        tenancy()->initialize($company2);
        
        // User should not have permissions in company2
        $this->assertFalse($user->hasPermissionTo('customer.view'));
    }
}
```

This comprehensive role and permission system implementation plan provides a robust foundation for managing access control in the ISP Billing & CRM system, ensuring proper security and multi-tenancy awareness.