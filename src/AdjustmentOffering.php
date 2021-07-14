<?php

namespace BookingProtect\InsuranceHub\Client;

class AdjustmentOffering {
    public string $id;
    public string $offeringId;
    public float $originalTotalPremium;
    public float $totalPremium;
    public float $premiumDifference;
    public string $currencyCode;
    /**
     * @var ProductOffering[]
     */
    public array $productOfferings;
}