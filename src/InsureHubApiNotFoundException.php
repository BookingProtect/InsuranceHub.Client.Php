<?php

namespace BookingProtect\InsuranceHub\Client;


class InsureHubApiNotFoundException extends InsureHubException {
    public function errorMessage(): string {
        return 'Invalid Request : '.$this->getMessage();
    }
}
