<?php

namespace BookingProtect\InsuranceHub\Client;

use JsonSerializable;

class PolicySearchByOfferingId implements JsonSerializable {
    public string $vendorId;
    public string $offeringId;

    public function jsonSerialize(): array {
        return [
            'vendorId'   => $this->vendorId,
            'offeringId' => $this->offeringId
        ];
    }
}