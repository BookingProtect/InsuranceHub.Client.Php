<?php

namespace BookingProtect\InsuranceHub\Client;

class Offering {
    public string $id;
    /**
     * @var ProductOffering[]
     */
    public array $productOfferings;
    /**
     * @var Product[]
     */
    public ?array $productsOutsideOfPricing;

    public function __construct() {
        $this->productOfferings         = [];
        $this->productsOutsideOfPricing = [];
    }
}