<?php

namespace BookingProtect\InsuranceHub\Client;

interface IAuthTokenGenerator {
    public function generateToken(string $vendorId, string $apiKey): string;
}