<?php

namespace BookingProtect\InsuranceHub\Client;

class InsureHubApiException extends InsureHubException {
    public function errorMessage(): string {
        return 'InsureHub API Exception : ' . $this->getMessage();
    }
}