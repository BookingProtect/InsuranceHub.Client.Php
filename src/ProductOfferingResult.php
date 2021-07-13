<?php

namespace BookingProtect\InsuranceHub\Client;

use JsonSerializable;

class ProductOfferingResult implements JsonSerializable {
    public string $productOfferingId;
    public bool $sold;

    public function jsonSerialize(): array {
        return [
            'productOfferingId' => $this->productOfferingId,
            'sold'              => $this->sold
        ];
    }
}