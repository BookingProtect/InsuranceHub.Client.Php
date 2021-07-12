<?php

namespace BookingProtect\InsuranceHub\Client;

use JsonSerializable;

class CancellationRequest implements JsonSerializable {
    public string $offeringId;

    public function jsonSerialize(): array {
        return [
            'offeringId' => $this->offeringId
        ];
    }
}