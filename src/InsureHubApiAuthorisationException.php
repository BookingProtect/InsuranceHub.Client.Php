<?php

namespace BookingProtect\InsuranceHub\Client;

class InsureHubApiAuthorisationException extends InsureHubException {
    public function errorMessage(): string {
        return 'You do not have access to this service.  Please contact support if you think this is in error.';
    }
}