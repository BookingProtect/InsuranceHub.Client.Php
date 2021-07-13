<?php

namespace BookingProtect\InsuranceHub\Client;

class AdjustmentRequest {
    public string $vendorId;
    public string $offeringId;
    public OfferingRequest $offeringRequest;
}