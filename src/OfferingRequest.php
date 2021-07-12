<?php

namespace BookingProtect\InsuranceHub\Client;

use JsonSerializable;

class OfferingRequest implements JsonSerializable {
    public function __construct() {
        $this->vendorRequestReference = NULL;
        $this->products               = [];
        $this->premiumAsSummary       = TRUE;
    }

    public string $vendorId;
    public ?string $vendorRequestReference;
    /**
     * @var Product[]
     */
    public array $products;
    public bool $premiumAsSummary;

    public function jsonSerialize(): array {
        return [
            'vendorId'               => $this->vendorId,
            'vendorRequestReference' => $this->vendorRequestReference,
            'products'               => $this->products,
            'premiumAsSummary'       => $this->premiumAsSummary
        ];
    }
}