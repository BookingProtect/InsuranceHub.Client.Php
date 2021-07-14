<?php

namespace BookingProtect\InsuranceHub\Client;

use JsonSerializable;

class PolicySearch implements JsonSerializable {
    public function __construct() {
        $this->vendorSalesReference = NULL;
        $this->customerForename     = NULL;
        $this->customerSurname      = NULL;
    }

    public string $vendorId;
    public ?string $vendorSalesReference;
    public ?string $customerForename;
    public ?string $customerSurname;

    public function jsonSerialize(): array {
        return [
            'vendorId'             => $this->vendorId,
            'vendorSalesReference' => $this->vendorSalesReference,
            'customerForename'     => $this->customerForename,
            'customerSurname'      => $this->customerSurname
        ];
    }
}