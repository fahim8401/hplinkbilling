# SMS Module Implementation Plan

## Overview
This document outlines the implementation plan for the SMS module in the ISP Billing & CRM system. The module will support multiple SMS gateways with HTTP GET/POST/JSON protocols, message templating, auto-triggers, and robust logging.

## Module Components

### 1. Gateway Management
- Multiple gateway configuration
- HTTP GET/POST/JSON support
- Parameter mapping
- Balance checking
- Failover mechanisms

### 2. Message Templating
- Template creation and management
- Variable substitution
- Multi-language support
- Template categorization

### 3. Auto-SMS Triggers
- Event-based message sending
- Customer lifecycle events
- Billing notifications
- Expiry warnings

### 4. SMS Logging and Retry
- Delivery status tracking
- Retry mechanism for failed messages
- Detailed logging
- Performance metrics

## Gateway Management Implementation

### Gateway Model
```php
class SMSGateway extends Model
{
    protected $fillable = [
        'company_id',
        'name',
        'gateway_url',
        'http_method',
        'params',
        'headers',
        'is_active',
        'balance',
        'default_sender_id'
    ];
    
    protected $casts = [
        'params' => 'array',
        'headers' => 'array',
        'is_active' => 'boolean'
    ];
    
    public function company()
    {
        return $this->belongsTo(Company::class);
    }
    
    public function templates()
    {
        return $this->hasMany(SMSTemplate::class);
    }
}
```

### Gateway Configuration
```php
class GatewayConfiguration
{
    public $gateway_url;
    public $http_method;
    public $params;
    public $headers;
    public $default_sender_id;
    
    public function __construct($config)
    {
        $this->gateway_url = $config['gateway_url'];
        $this->http_method = $config['http_method'] ?? 'GET';
        $this->params = $config['params'] ?? [];
        $this->headers = $config['headers'] ?? [];
        $this->default_sender_id = $config['default_sender_id'] ?? null;
    }
    
    public function buildRequest($message, $recipient, $variables = [])
    {
        // Replace variables in parameters
        $processedParams = $this->processVariables($this->params, $variables);
        
        // Add message and recipient to parameters
        $processedParams['message'] = $message;
        $processedParams['to'] = $recipient;
        
        if ($this->default_sender_id) {
            $processedParams['from'] = $this->default_sender_id;
        }
        
        return [
            'url' => $this->gateway_url,
            'method' => $this->http_method,
            'params' => $processedParams,
            'headers' => $this->headers
        ];
    }
    
    private function processVariables($params, $variables)
    {
        $processed = [];
        
        foreach ($params as $key => $value) {
            if (is_string($value)) {
                // Replace variables in the format {variable_name}
                foreach ($variables as $varKey => $varValue) {
                    $value = str_replace('{' . $varKey . '}', $varValue, $value);
                }
                $processed[$key] = $value;
            } else {
                $processed[$key] = $value;
            }
        }
        
        return $processed;
    }
}
```

### Gateway Service
```php
class GatewayService
{
    public function sendSMS($gateway, $message, $recipient, $variables = [])
    {
        try {
            $config = new GatewayConfiguration($gateway->toArray());
            $request = $config->buildRequest($message, $recipient, $variables);
            
            $client = new Client();
            
            $response = $client->request($request['method'], $request['url'], [
                'query' => $request['method'] === 'GET' ? $request['params'] : null,
                'form_params' => $request['method'] === 'POST' ? $request['params'] : null,
                'json' => $request['method'] === 'JSON' ? $request['params'] : null,
                'headers' => $request['headers']
            ]);
            
            // Process response
            $result = $this->processResponse($response, $gateway);
            
            return $result;
        } catch (Exception $e) {
            Log::error('SMS sending failed', [
                'gateway_id' => $gateway->id,
                'recipient' => $recipient,
                'error' => $e->getMessage()
            ]);
            
            throw new SMSException('Failed to send SMS: ' . $e->getMessage());
        }
    }
    
    private function processResponse($response, $gateway)
    {
        $statusCode = $response->getStatusCode();
        $body = $response->getBody()->getContents();
        
        // Parse response based on gateway configuration
        $success = $this->isSuccessResponse($body, $gateway);
        
        return [
            'status' => $success ? 'sent' : 'failed',
            'response_code' => $statusCode,
            'response_body' => $body,
            'gateway_message_id' => $this->extractMessageId($body, $gateway)
        ];
    }
    
    private function isSuccessResponse($body, $gateway)
    {
        // Custom success detection logic based on gateway
        // This could be configured per gateway
        $successIndicators = $gateway->success_indicators ?? ['status' => 'success'];
        
        foreach ($successIndicators as $key => $value) {
            if (!isset($body[$key]) || $body[$key] !== $value) {
                return false;
            }
        }
        
        return true;
    }
    
    private function extractMessageId($body, $gateway)
    {
        // Extract message ID from response if available
        return $body['message_id'] ?? null;
    }
    
    public function checkBalance($gateway)
    {
        try {
            // If gateway has a balance check endpoint
            if (!empty($gateway->balance_check_url)) {
                $client = new Client();
                $response = $client->get($gateway->balance_check_url, [
                    'query' => $gateway->balance_check_params ?? [],
                    'headers' => $gateway->headers ?? []
                ]);
                
                $body = json_decode($response->getBody()->getContents(), true);
                
                // Extract balance from response
                $balance = $body['balance'] ?? 0;
                
                // Update gateway balance
                $gateway->balance = $balance;
                $gateway->last_balance_check = now();
                $gateway->save();
                
                return $balance;
            }
            
            return null;
        } catch (Exception $e) {
            Log::error('Balance check failed', [
                'gateway_id' => $gateway->id,
                'error' => $e->getMessage()
            ]);
            
            return null;
        }
    }
}
```

## Message Templating Implementation

### Template Model
```php
class SMSTemplate extends Model
{
    protected $fillable = [
        'company_id',
        'gateway_id',
        'name',
        'template',
        'variables',
        'is_active',
        'category'
    ];
    
    protected $casts = [
        'variables' => 'array',
        'is_active' => 'boolean'
    ];
    
    public function company()
    {
        return $this->belongsTo(Company::class);
    }
    
    public function gateway()
    {
        return $this->belongsTo(SMSGateway::class);
    }
}
```

### Template Service
```php
class TemplateService
{
    public function renderTemplate($template, $variables = [])
    {
        $message = $template->template;
        
        // Replace variables in the format {variable_name}
        foreach ($variables as $key => $value) {
            $message = str_replace('{' . $key . '}', $value, $message);
        }
        
        return $message;
    }
    
    public function getAvailableVariables($category)
    {
        $variables = [
            'general' => ['name', 'phone', 'email'],
            'billing' => ['name', 'phone', 'email', 'due_amount', 'due_date', 'invoice_number'],
            'payment' => ['name', 'phone', 'email', 'amount', 'payment_date', 'transaction_id'],
            'expiry' => ['name', 'phone', 'email', 'package_name', 'expiry_date'],
            'suspension' => ['name', 'phone', 'email', 'package_name', 'suspend_date']
        ];
        
        return $variables[$category] ?? $variables['general'];
    }
    
    public function validateVariables($template, $variables)
    {
        $requiredVariables = $template->variables ?? [];
        
        foreach ($requiredVariables as $variable) {
            if (!isset($variables[$variable])) {
                throw new SMSException("Missing required variable: {$variable}");
            }
        }
        
        return true;
    }
}
```

## Auto-SMS Triggers Implementation

### Event System
```php
// Event classes for different triggers
class CustomerCreated
{
    public $customer;
    
    public function __construct($customer)
    {
        $this->customer = $customer;
    }
}

class InvoiceGenerated
{
    public $invoice;
    
    public function __construct($invoice)
    {
        $this->invoice = $invoice;
    }
}

class PaymentReceived
{
    public $payment;
    
    public function __construct($payment)
    {
        $this->payment = $payment;
    }
}

class CustomerExpired
{
    public $customer;
    
    public function __construct($customer)
    {
        $this->customer = $customer;
    }
}

class CustomerSuspended
{
    public $customer;
    
    public function __construct($customer)
    {
        $this->customer = $customer;
    }
}
```

### Event Listeners
```php
class SendWelcomeSMS
{
    public function handle(CustomerCreated $event)
    {
        $smsService = new SMSService();
        $smsService->sendWelcomeMessage($event->customer);
    }
}

class SendInvoiceSMS
{
    public function handle(InvoiceGenerated $event)
    {
        $smsService = new SMSService();