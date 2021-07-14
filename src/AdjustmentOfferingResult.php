<?php

namespace BookingProtect\InsuranceHub\Client;

use JsonSerializable;

class AdjustmentOfferingResult implements JsonSerializable {
    public string $vendorId;
    public string $adjustmentId;
    public string $vendorSaleReference;
    public bool $sold;

    public function jsonSerialize() : array {
        return [
            'vendorId' => $this->vendorId,
            'adjustmentId' => $this->adjustmentId,
            'vendorSaleReference' => $this->vendorSaleReference,
            'sold' => $this->sold,
        ];
    }
}