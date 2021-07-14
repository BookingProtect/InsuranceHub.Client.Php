<?php

namespace BookingProtect\InsuranceHub\Client;

use DateTime;

class Matrix {
    public string $id;
    /**
     * @var PriceBand[]
     */
    public array $priceBands;
    public string $pricingModel;
    public bool $locked;
    public DateTime $validFrom;
    public ?DateTime $validTo;
}