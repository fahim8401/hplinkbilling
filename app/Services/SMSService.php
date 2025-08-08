<?php

namespace App\Services;

use App\Models\SMSGateway;
use App\Models\SMSTemplate;
use App\Models\SMSLog;
use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Log;

class SMSService
{
    /**
     * Send an SMS message.
     *
     * @param SMSGateway $gateway
     * @param string $phoneNumber
     * @param string $message
     * @param array $variables
     * @return bool
     */
    public function sendSMS(SMSGateway $gateway, $phoneNumber, $message, $variables = [])
    {
        // Create a log entry for this SMS attempt
        $smsLog = SMSLog::create([
            'company_id' => $gateway->company_id,
            'gateway_id' => $gateway->id,
            'phone_number' => $phoneNumber,
            'message' => $message,
            'status' => 'pending',
        ]);

        try {
            // Replace variables in the message
            $processedMessage = $this->processVariables($message, $variables);

            // Prepare the request parameters
            $params = $gateway->params;
            $params['message'] = $processedMessage;
            $params['to'] = $phoneNumber;

            // Add default sender ID if configured
            if (!empty($gateway->default_sender_id)) {
                $params['from'] = $gateway->default_sender_id;
            }

            // Send the SMS based on the HTTP method
            $response = null;
            switch (strtoupper($gateway->http_method)) {
                case 'GET':
                    $response = Http::withHeaders($gateway->headers)->get($gateway->gateway_url, $params);
                    break;
                case 'POST':
                    $response = Http::withHeaders($gateway->headers)->post($gateway->gateway_url, $params);
                    break;
                case 'JSON':
                    $response = Http::withHeaders($gateway->headers)->post($gateway->gateway_url, $params);
                    break;
                default:
                    throw new \Exception('Invalid HTTP method');
            }

            // Process the response
            $success = $this->isSuccessResponse($response, $gateway);
            
            // Update the log entry
            $smsLog->update([
                'status' => $success ? 'sent' : 'failed',
                'response' => $response->body(),
                'sent_at' => now(),
            ]);

            // Deduct balance from gateway if successful
            if ($success && $gateway->hasSufficientBalance(1)) {
                $gateway->deductBalance(1);
            }

            return $success;
        } catch (\Exception $e) {
            // Log the error
            Log::error('Failed to send SMS: ' . $e->getMessage());

            // Update the log entry with the error
            $smsLog->update([
                'status' => 'failed',
                'response' => $e->getMessage(),
            ]);

            return false;
        }
    }

    /**
     * Send an SMS using a template.
     *
     * @param SMSTemplate $template
     * @param string $phoneNumber
     * @param array $variables
     * @return bool
     */
    public function sendSMSTemplate(SMSTemplate $template, $phoneNumber, $variables = [])
    {
        // Render the template with variables
        $message = $template->render($variables);

        // Send the SMS
        return $this->sendSMS($template->gateway, $phoneNumber, $message, $variables);
    }

    /**
     * Process variables in a message.
     *
     * @param string $message
     * @param array $variables
     * @return string
     */
    protected function processVariables($message, $variables)
    {
        foreach ($variables as $key => $value) {
            $message = str_replace('{' . $key . '}', $value, $message);
        }

        return $message;
    }

    /**
     * Check if the response indicates success.
     *
     * @param \Illuminate\Http\Client\Response $response
     * @param SMSGateway $gateway
     * @return bool
     */
    protected function isSuccessResponse($response, SMSGateway $gateway)
    {
        // Check if the response status code indicates success
        if (!$response->successful()) {
            return false;
        }

        // If there are specific success indicators configured, check them
        if (!empty($gateway->success_indicators)) {
            $body = $response->json();
            foreach ($gateway->success_indicators as $key => $value) {
                if (!isset($body[$key]) || $body[$key] != $value) {
                    return false;
                }
            }
        }

        return true;
    }

    /**
     * Check the balance of a gateway.
     *
     * @param SMSGateway $gateway
     * @return float|null
     */
    public function checkBalance(SMSGateway $gateway)
    {
        try {
            // If gateway has a balance check URL
            if (!empty($gateway->balance_check_url)) {
                $response = Http::withHeaders($gateway->headers)->get($gateway->balance_check_url, $gateway->balance_check_params ?? []);

                if ($response->successful()) {
                    $body = $response->json();
                    $balance = $body['balance'] ?? null;

                    // Update gateway balance
                    if ($balance !== null) {
                        $gateway->balance = $balance;
                        $gateway->last_balance_check = now();
                        $gateway->save();
                    }

                    return $balance;
                }
            }

            return null;
        } catch (\Exception $e) {
            Log::error('Balance check failed: ' . $e->getMessage());
            return null;
        }
    }

    /**
     * Retry sending failed SMS messages.
     *
     * @param int $limit
     * @return int
     */
    public function retryFailedSMS($limit = 10)
    {
        $failedSMS = SMSLog::failed()->limit($limit)->get();
        $retryCount = 0;

        foreach ($failedSMS as $sms) {
            // Get the gateway for this SMS
            $gateway = $sms->gateway;

            // Skip if gateway is not active
            if (!$gateway || !$gateway->isActive()) {
                continue;
            }

            // Retry sending the SMS
            $success = $this->sendSMS($gateway, $sms->phone_number, $sms->message);

            if ($success) {
                $retryCount++;
            }
        }

        return $retryCount;
    }
}