<?php

namespace BookingProtect\InsuranceHub\Client;

use Exception;

abstract class InsureHubException extends Exception {
    abstract public function errorMessage();
}