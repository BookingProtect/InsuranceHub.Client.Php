<?php

namespace BookingProtect\InsuranceHub\Client;

use DateTime;

class PolicyItem {
    public string $productOfferingId;
    public float $value;
    public float $premium;
    public string $currencyCode;
    public string $categoryCode;
    public string $languageCode;
    public DateTime $completionDate;
    public bool $cancelled;
    public ?DateTime $cancelledDate;
}