<?php

namespace BookingProtect\InsuranceHub\Client;

class InsureHubApiAuthenticationException extends InsureHubException {
    public function errorMessage(): string {
        return 'Unauthorised request.  Check your Vendor ID and Api Key';
    }
}