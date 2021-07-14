<?php

namespace BookingProtect\InsuranceHub\Client;


class InsureHubApiValidationException extends InsureHubException {
    public function errorMessage(): string {
        return 'Invalid Request : '.$this->getMessage();
    }
}
