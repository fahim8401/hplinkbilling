# API Structure Implementation Plan

## Overview
This document outlines the implementation plan for the RESTful API structure of the ISP Billing & CRM system. The API will support multiple user roles, secure authentication, rate limiting, and comprehensive endpoint organization.

## API Architecture

### Authentication Mechanisms
1. **Laravel Sanctum** - For SPA authentication (web frontend)
2. **Laravel Passport** - For OAuth2 API tokens (mobile apps, third-party integrations)
3. **Token-based authentication** - For system-to-system communication
4. **Role-based access control** - For endpoint authorization

### Rate Limiting
1. **Per-user rate limiting** - Prevent API abuse
2. **Per-endpoint rate limiting** - Critical endpoint protection
3. **Per-role rate limiting** - Different limits for different user types
4. **IP-based rate limiting** - Additional protection layer

### API Versioning
1. **URL-based versioning** - `/api/v1/`, `/api/v2/`
2. **Backward compatibility** - Maintain older versions
3. **Deprecation strategy** - Clear migration path

## API Structure

### Base URL Structure
```
https://api.example.com/v1/
https://api.example.com/v2/
```

### Endpoint Organization
```
/api/v1/
├── auth/
│   ├── login
│   ├── logout
│   ├── refresh
│   ├── register
│   └── user
├── customer/
│   ├── profile
│   ├── usage
│   ├── invoices
│   ├── payments
│   ├── tickets
│   └── packages
├── admin/
│   ├── companies
│   ├── users
│   ├── packages
│   ├── pops
│   ├── routers
│   ├── reports
│   └── settings
├── reseller/
│   ├── balance
│   ├── customers
│   ├── commissions
│   ├── transfers
│   └── reports
├── billing/
│   ├── invoices
│   ├── payments
│   ├── recharges
│   └── statements
├── network/
│   ├── routers
│   ├── profiles
│   ├── sessions
│   └── status
├── support/
│   ├── tickets
│   ├── tokens
│   └── categories
├── sms/
│   ├── send
│   ├── templates
│   ├── gateways
│   └── logs
└── reports/
    ├── financial
    ├── usage
    ├── commissions
    └── export
```

## Authentication Implementation

### Laravel Sanctum for SPA Authentication
```php
// config/sanctum.php
return [
    'stateful' => explode(',', env('SANCTUM_STATEFUL_DOMAINS', sprintf(
        '%s%s',
        'localhost,localhost:3000,127.0.0.1,127.0.0.1:8000,::1',
        env('APP_URL') ? ','.parse_url(env('APP_URL'), PHP_URL_HOST) : ''
    ))),
    
    'guard' => ['web'],
    
    'expiration' => null,
    
    'middleware' => [
        'verify_csrf_token' => App\Http\Middleware\VerifyCsrfToken::class,
        'encrypt_cookies' => App\Http\Middleware\EncryptCookies::class,
    ],
];
```

### Laravel Passport for OAuth2 API Tokens
```php
// AuthServiceProvider.php
public function boot()
{
    $this->registerPolicies();
    
    Passport::routes();
    
    Passport::tokensExpireIn(now()->addDays(15));
    Passport::refreshTokensExpireIn(now()->addDays(30));
    Passport::personalAccessTokensExpireIn(now()->addMonths(6));
}
```

### API Authentication Middleware
```php
class AuthenticateApi
{
    public function handle($request, Closure $next)
    {
        // Check for Sanctum token
        if ($request->bearerToken()) {
            $user = User::where('api_token', $request->bearerToken())->first();
            
            if ($user) {
                Auth::login($user);
                return $next($request);
            }
        }
        
        // Check for Passport token
        if ($request->header('Authorization')) {
            try {
                $user = Auth::guard('api')->user();
                if ($user) {
                    Auth::login($user);
                    return $next($request);
                }
            } catch (Exception $e) {
                // Handle authentication exception
            }
        }
        
        return response()->json(['error' => 'Unauthorized'], 401);
    }
}
```

### Token-based Authentication Service
```php
class APITokenService
{
    public function generateToken($user, $scopes = [], $expiresIn = null)
    {
        $token = Str::random(60);
        
        $apiToken = APIToken::create([
            'user_id' => $user->id,
            'token' => hash('sha256', $token),
            'scopes' => $scopes,
            'expires_at' => $expiresIn ? now()->addSeconds($expiresIn) : null,
            'last_used_at' => now()
        ]);
        
        return $token; // Return unhashed token to user
    }
    
    public function validateToken($token)
    {
        $hashedToken = hash('sha256', $token);
        $apiToken = APIToken::where('token', $hashedToken)
            ->where(function ($query) {
                $query->whereNull('expires_at')
                    ->orWhere('expires_at', '>', now());
            })
            ->first();
            
        if ($apiToken) {
            // Update last used timestamp
            $apiToken->last_used_at = now();
            $apiToken->save();
            
            return $apiToken->user;
        }
        
        return null;
    }
    
    public function revokeToken($token)
    {
        $hashedToken = hash('sha256', $token);
        return APIToken::where('token', $hashedToken)->delete();
    }
}
```

## Rate Limiting Implementation

### Global Rate Limiting Middleware
```php
class RateLimitMiddleware
{
    public function handle($request, Closure $next, $maxAttempts = 60, $decayMinutes = 1)
    {
        $user = $request->user();
        $key = $user ? 'api:' . $user->id : 'api:' . $request->ip();
        
        $executed = RateLimiter::attempt(
            $key,
            $maxAttempts,
            function() { return true; },
            $decayMinutes * 60
        );
        
        if (!$executed) {
            return response()->json([
                'error' => 'Too Many Requests',
                'message' => 'Rate limit exceeded. Please try again later.'
            ], 429);
        }
        
        $response = $next($request);
        
        // Add rate limit headers to response
        return $response->withHeaders([
            'X-RateLimit-Limit' => $maxAttempts,
            'X-RateLimit-Remaining' => RateLimiter::retriesLeft($key, $maxAttempts),
            'X-RateLimit-Reset' => RateLimiter::availableIn($key)
        ]);
    }
}
```

### Role-based Rate Limiting
```php
class RoleRateLimitMiddleware
{
    public function handle($request, Closure $next)
    {
        $user = $request->user();
        
        if (!$user) {
            return response()->json(['error' => 'Unauthorized'], 401);
        }
        
        $limits = [
            'super_admin' => ['attempts' => 1000, 'decay' => 1],
            'company_admin' => ['attempts' => 500, 'decay' => 1],
            'reseller' => ['attempts' => 200, 'decay' => 1],
            'customer' => ['attempts' 