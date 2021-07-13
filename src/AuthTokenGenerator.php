<?php

namespace BookingProtect\InsuranceHub\Client;

class AuthTokenGenerator implements IAuthTokenGenerator {
    public function generateToken(string $vendorId, string $apiKey): string {
        $formattedVendorId = str_replace('-', '', strtolower($vendorId));
        $formattedApiKey   = str_replace('-', '', strtolower($apiKey));
        $date              = gmdate('dmY');

        return base64_encode(hash_hmac('sha256', $formattedVendorId . $date, $formattedApiKey, true));
    }
}