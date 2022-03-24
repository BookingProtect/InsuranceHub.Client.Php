<?php

namespace BookingProtect\InsuranceHub\Client;

use DateTime;

class Policy {
    public string $offeringId;
    public string $vendorId;
    public string $vendorName;
    public DateTime $purchaseDate;
    public string $vendorSalesReference;
    public string $customerForename;
    public string $customerSurname;
    public string $emailAddress;
    /**
     * @var PolicyItem[]
     */
    public array $items;
}