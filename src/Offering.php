<?php

namespace BookingProtect\InsuranceHub\Client;

class Offering {
    public function __construct() {
        $this->productOfferings         = [];
        $this->productsOutsideOfPricing = [];
    }

    public string $id;
    /**
     * @var ProductOffering[]
     */
    public array $productOfferings;
    /**
     * @var Product[]
     */
    public ?array $productsOutsideOfPricing;
}