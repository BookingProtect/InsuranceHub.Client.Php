<?php

namespace BookingProtect\InsuranceHub\Client;

use JsonSerializable;

class AdjustmentRequest implements JsonSerializable {
    public string $vendorId;
    public string $offeringId;
    public OfferingRequest $offeringRequest;

    public function jsonSerialize() : array {
        return [
            'vendorId' => $this->vendorId,
            'offeringId' => $this->offeringId,
            'offeringRequest' => $this->offeringRequest
        ];
    }
}