# MikroTik RouterOS Integration Plan

## Overview
This document outlines the implementation plan for integrating MikroTik RouterOS with the ISP Billing & CRM system. The integration will enable automated PPPoE user management, live session tracking, and profile synchronization.

## Integration Components

### 1. Router Management
- Add/Edit/Remove MikroTik routers
- Store encrypted credentials
- Assign routers to POPs
- Monitor router status

### 2. PPPoE User Management
- Create PPPoE users
- Disable/Enable PPPoE users
- Delete PPPoE users
- Change user passwords
- Assign users to profiles

### 3. Live Session Tracking
- Fetch live user sessions
- Monitor interface counters
- Display real-time speed usage
- Session history tracking

### 4. Profile Synchronization
- Sync profile list from RouterOS
- Map profiles to packages
- Profile CRUD operations

## API Client Implementation

### Using routeros-api-php Library
The integration will use the `routeros-api-php` library for communicating with MikroTik routers.

### Connection Management
```php
class MikrotikAPI
{
    private $client;
    private $router;
    
    public function __construct($router)
    {
        $this->router = $router;
        $this->client = new RouterOS\Client([
            'host' => $router->ip_address,
            'user' => $router->username,
            'pass' => decrypt($router->password),
            'port' => $router->port
        ]);
    }
    
    public function isConnected()
    {
        try {
            $this->client->exec('/system/resource/print');
            return true;
        } catch (Exception $e) {
            return false;
        }
    }
}
```

## Router Management Implementation

### Router Model
```php
class MikrotikRouter extends Model
{
    protected $fillable = [
        'company_id',
        'pop_id',
        'name',
        'ip_address',
        'port',
        'username',
        'password',
        'status'
    ];
    
    protected $casts = [
        'password' => 'encrypted'
    ];
    
    public function pop()
    {
        return $this->belongsTo(POP::class);
    }
    
    public function profiles()
    {
        return $this->hasMany(MikrotikProfile::class);
    }
}
```

### Router Service
```php
class RouterService
{
    public function addRouter($data)
    {
        // Encrypt password before saving
        $data['password'] = encrypt($data['password']);
        
        return MikrotikRouter::create($data);
    }
    
    public function testConnection($router)
    {
        try {
            $api = new MikrotikAPI($router);
            return $api->isConnected();
        } catch (Exception $e) {
            Log::error('Router connection test failed', [
                'router_id' => $router->id,
                'error' => $e->getMessage()
            ]);
            return false;
        }
    }
    
    public function updateRouterStatus($router)
    {
        $isConnected = $this->testConnection($router);
        $router->status = $isConnected ? 'active' : 'inactive';
        $router->save();
        
        return $isConnected;
    }
}
```

## PPPoE User Management

### User Creation
```php
class PPPoEUserService
{
    public function createUser($customer, $router = null)
    {
        // Get router for customer's POP
        if (!$router) {
            $router = $this->getRouterForCustomer($customer);
        }
        
        if (!$router) {
            throw new Exception('No router found for customer POP');
        }
        
        try {
            $api = new MikrotikAPI($router);
            
            // Create PPPoE user
            $response = $api->client->exec('/ppp/secret/add', [
                'name' => $customer->username,
                'password' => $customer->password,
                'service' => 'pppoe',
                'profile' => $this->getProfileName($customer->package),
                'disabled' => 'no'
            ]);
            
            // Log successful creation
            $this->logUserAction($customer, 'create', 'success');
            
            return true;
        } catch (Exception $e) {
            // Log error
            $this->logUserAction($customer, 'create', 'failed', $e->getMessage());
            throw $e;
        }
    }
    
    public function disableUser($customer)
    {
        $router = $this->getRouterForCustomer($customer);
        
        try {
            $api = new MikrotikAPI($router);
            
            // Disable PPPoE user
            $response = $api->client->exec('/ppp/secret/set', [
                'numbers' => $customer->username,
                'disabled' => 'yes'
            ]);
            
            // Update customer status
            $customer->status = 'suspended';
            $customer->save();
            
            // Log action
            $this->logUserAction($customer, 'disable', 'success');
            
            return true;
        } catch (Exception $e) {
            $this->logUserAction($customer, 'disable', 'failed', $e->getMessage());
            throw $e;
        }
    }
    
    public function enableUser($customer)
    {
        $router = $this->getRouterForCustomer($customer);
        
        try {
            $api = new MikrotikAPI($router);
            
            // Enable PPPoE user
            $response = $api->client->exec('/ppp/secret/set', [
                'numbers' => $customer->username,
                'disabled' => 'no'
            ]);
            
            // Update customer status
            $customer->status = 'active';
            $customer->save();
            
            // Log action
            $this->logUserAction($customer, 'enable', 'success');
            
            return true;
        } catch (Exception $e) {
            $this->logUserAction($customer, 'enable', 'failed', $e->getMessage());
            throw $e;
        }
    }
    
    public function deleteUser($customer)
    {
        $router = $this->getRouterForCustomer($customer);
        
        try {
            $api = new MikrotikAPI($router);
            
            // Delete PPPoE user
            $response = $api->client->exec('/ppp/secret/remove', [
                'numbers' => $customer->username
            ]);
            
            // Log action
            $this->logUserAction($customer, 'delete', 'success');
            
            return true;
        } catch (Exception $e) {
            $this->logUserAction($customer, 'delete', 'failed', $e->getMessage());
            throw $e;
        }
    }
    
    public function changePassword($customer, $newPassword)
    {
        $router = $this->getRouterForCustomer($customer);
        
        try {
            $api = new MikrotikAPI($router);
            
            // Change user password
            $response = $api->client->exec('/ppp/secret/set', [
                'numbers' => $customer->username,
                'password' => $newPassword
            ]);
            
            // Update customer password
            $customer->password = $newPassword;
            $customer->save();
            
            // Log action
            $this->logUserAction($customer, 'change_password', 'success');
            
            return true;
        } catch (Exception $e) {
            $this->logUserAction($customer, 'change_password', 'failed', $e->getMessage());
            throw $e;
        }
    }
}
```

## Live Session Tracking

### Session Data Model
```php
class CustomerSession extends Model
{
    protected $fillable = [
        'customer_id',
        'router_id',
        'session_id',
        'ip_address',
        'mac_address',
        'login_time',
        'logout_time',
        'download_bytes',
        'upload_bytes'
    ];
    
    protected $casts = [
        'login_time' => 'datetime',
        'logout_time' => 'datetime'
    ];
}
```

### Session Tracking Service
```php
class SessionTrackingService
{
    public function fetchLiveSessions($router)
    {
        try {
            $api = new MikrotikAPI($router);
            
            // Fetch active PPPoE sessions
            $sessions = $api->client->exec('/ppp/active/print');
            
            // Process and store session data
            foreach ($sessions as $session) {
                $this->processSessionData($session, $router);
            }
            
            return $sessions;
        } catch (Exception $e) {
            Log::error('Failed to fetch live sessions', [
                'router_id' => $router->id,
                'error' => $e->getMessage()
            ]);
            throw $e;
        }
    }
    
    public function processSessionData($sessionData, $router)
    {
        $username = $sessionData['name'] ?? null;
        
        if (!$username) {
            return;
        }
        
        // Find customer by username
        $customer = Customer::where('username', $username)
            ->where('router_id', $router->id)
            ->first();
            
        if (!$customer) {
            return;
        }
        
        // Create or update session record
        CustomerSession::updateOrCreate(
            [
                'customer_id' => $customer->id,
                'session_id' => $sessionData['.id']
            ],
            [
                'router_id' => $router->id,
                'ip_address' => $sessionData['address'] ?? null,
                'mac_address' => $sessionData['caller-id'] ?? null,
                'login_time' => now(),
                'download_bytes' => $sessionData['bytes-in'] ?? 0,
                'upload_bytes' => $sessionData['bytes-out'] ?? 0
            ]
        );
    }
    
    public function fetchInterfaceCounters($router)
    {
        try {
            $api = new MikrotikAPI($router);
            
            // Fetch interface statistics
            $interfaces = $api->client->exec('/interface/monitor-traffic', [
                'interface' => 'all',
                'once' => ''
            ]);
            
            return $interfaces;
        } catch (Exception $e) {
            Log::error('Failed to fetch interface counters', [
                'router_id' => $router->id,
                'error' => $e->getMessage()
            ]);
            throw $e;
        }
    }
}
```

## Profile Synchronization

### Profile Model
```php
class MikrotikProfile extends Model
{
    protected $fillable = [
        'router_id',
        'profile_name',
        'profile_id',
        'rate_limit',
        'session_timeout',
        'idle_timeout'
    ];
    
    public function router()
    {
        return $this->belongsTo(MikrotikRouter::class);
    }
    
    public function packages()
    {
        return $this->hasMany(Package::class, 'mikrotik_profile_id');
    }
}
```

### Profile Sync Service
```php
class ProfileSyncService
{
    public function syncProfiles($router)
    {
        try {
            $api = new MikrotikAPI($router);
            
            // Fetch all PPP profiles
            $profiles = $api->client->exec('/ppp/profile/print');
            
            // Process each profile
            foreach ($profiles as $profile) {
                $this->syncProfile($profile, $router);
            }
            
            // Update last sync timestamp
            $router->last_profile_sync = now();
            $router->save();
            
            return count($profiles);
        } catch (Exception $e) {
            Log::error('Failed to sync profiles', [
                'router_id' => $router->id,
                'error' => $e->getMessage()
            ]);
            throw $e;
        }
    }
    
    private function syncProfile($profileData, $router)
    {
        MikrotikProfile::updateOrCreate(
            [
                'router_id' => $router->id,
                'profile_id' => $profileData['.id']
            ],
            [
                'profile_name' => $profileData['name'],
                'rate_limit' => $profileData['rate-limit'] ?? null,
                'session_timeout' => $profileData['session-timeout'] ?? null,
                'idle_timeout' => $profileData['idle-timeout'] ?? null
            ]
        );
    }
    
    public function getProfileForPackage($package)
    {
        return MikrotikProfile::find($package->mikrotik_profile_id);
    }
}
```

## Security Considerations

### Credential Encryption
- All router passwords stored encrypted in database
- Use Laravel's built-in encryption
- Secure key management

### API Security
- Secure communication with routers
- Input validation for all API calls
- Error handling without exposing sensitive data

### Access Control
- Role-based access to router management
- Audit logging for all router operations
- Secure credential handling

## Error Handling and Logging

### Exception Handling
```php
class MikrotikException extends Exception
{
    // Custom MikroTik exceptions
}

class MikrotikService
{
    public function handleApiCall($callback)
    {
        try {
            return $callback();
        } catch (ClientException $e) {
            Log::error('MikroTik API client error', [
                'error' => $e->getMessage(),
                'code' => $e->getCode()
            ]);
            throw new MikrotikException('Failed to communicate with router');
        } catch (ConnectException $e) {
            Log::error('MikroTik connection error', [
                'error' => $e->getMessage()
            ]);
            throw new MikrotikException('Could not connect to router');
        } catch (Exception $e) {
            Log::error('MikroTik general error', [
                'error' => $e->getMessage()
            ]);
            throw new MikrotikException('An error occurred while processing router request');
        }
    }
}
```

## Performance Considerations

### Connection Pooling
- Reuse API connections when possible
- Implement connection timeout handling
- Monitor connection health

### Batch Operations
```php
class BatchMikrotikService
{
    public function batchCreateUsers($customers, $router)
    {
        $api = new MikrotikAPI($router);
        
        foreach ($customers as $customer) {
            try {
                $api->client->exec('/ppp/secret/add', [
                    'name' => $customer->username,
                    'password' => $customer->password,
                    'service' => 'pppoe',
                    'profile' => $this->getProfileName($customer->package),
                    'disabled' => 'no'
                ]);
            } catch (Exception $e) {
                Log::error('Batch user creation failed', [
                    'customer_id' => $customer->id,
                    'error' => $e->getMessage()
                ]);
            }
        }
    }
}
```

### Caching
- Cache frequently accessed router data
- Cache profile information
- Implement cache invalidation strategies

## Monitoring and Maintenance

### Health Checks
- Regular router connectivity tests
- Session data validation
- Profile synchronization status

### Cron Jobs
```php
// app/Console/Commands/SyncMikrotikProfiles.php

public function handle()
{
    $routers = MikrotikRouter::where('status', 'active')->get();
    
    foreach ($routers as $router) {
        try {
            $profileSyncService = new ProfileSyncService();
            $profileSyncService->syncProfiles($router);
        } catch (Exception $e) {
            Log::error('Profile sync failed for router', [
                'router_id' => $router->id,
                'error' => $e->getMessage()
            ]);
        }
    }
}
```

### Alerting
- Router connectivity alerts
- Session anomaly detection
- Profile sync failure notifications

## Testing Strategy

### Unit Tests
1. Router connection management
2. PPPoE user operations
3. Session data processing
4. Profile synchronization

### Integration Tests
1. End-to-end user creation flow
2. Session tracking accuracy
3. Profile mapping validation
4. Multi-router scenarios

### Test Examples
```php
class MikrotikServiceTest extends TestCase
{
    public function test_encrypt_router_password()
    {
        $password = 'secret123';
        $router = MikrotikRouter::make(['password' => $password]);
        
        $this->assertNotEquals($password, $router->password);
        $this->assertEquals($password, decrypt($router->password));
    }
    
    public function test_create_pppoe_user()
    {
        // Mock API client
        $mockClient = Mockery::mock(Client::class);
        $mockClient->shouldReceive('exec')
            ->with('/ppp/secret/add', Mockery::any())
            ->andReturn(['.id' => 'test-id']);
            
        // Test user creation
        $service = new PPPoEUserService($mockClient);
        $result = $service->createUser($this->customer);
        
        $this->assertTrue($result);
    }
}
```

This comprehensive MikroTik RouterOS integration plan provides a solid foundation for implementing the network management features required by the ISP Billing & CRM system.