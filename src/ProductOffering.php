<?php

namespace BookingProtect\InsuranceHub\Client;

class ProductOffering {
    public string $id;
    public string $categoryCode;
    public string $currencyCode;
    public float $premium;
    public ProductOfferingWording $wording;
}