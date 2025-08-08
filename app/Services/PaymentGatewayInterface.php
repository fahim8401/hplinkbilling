<?php

namespace App\Services;

interface PaymentGatewayInterface
{
    public function checkBill($customerId);
    public function processPayment($customerId, $amount, $mobileNo, $trxId, $datetime);
    public function searchTransaction($trxId);
}