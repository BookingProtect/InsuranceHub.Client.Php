<?php

namespace BookingProtect\InsuranceHub\Client;

use JsonSerializable;

class OfferingResult implements JsonSerializable {
    public function __construct() {
        $this->vendorSaleReference = NULL;
        $this->customerForename    = NULL;
        $this->customerSurname     = NULL;
        $this->sales               = [];
    }

    public string $vendorId;
    public string $offeringId;
    public ?string $vendorSaleReference;
    public ?string $customerForename;
    public ?string $customerSurname;
    /**
     * @var ProductOfferingResult[]
     */
    public array $sales;

    public function jsonSerialize(): array {
        return [
            'vendorId'            => $this->vendorId,
            'offeringId'          => $this->offeringId,
            'vendorSaleReference' => $this->vendorSaleReference,
            'customerForename'    => $this->customerForename,
            'customerSurname'     => $this->customerSurname,
            'sales'               => $this->sales
        ];
    }
}