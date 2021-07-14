<?php

namespace BookingProtect\InsuranceHub\Client;

class PriceBand {
    public string $id;
    public float $lowerBound;
    public float $upperBound;
    public float $premium;
    public float $commission;
}