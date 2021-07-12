<?php

namespace BookingProtect\InsuranceHub\Client;

use DateTime;
use JsonSerializable;

class Product implements JsonSerializable {
    public function __construct() {
        $this->currencyCode = null;
        $this->languageCode = null;
    }

    public string $categoryCode;
    public ?string $languageCode;
    public ?string $currencyCode;
    public float $price;
    public DateTime $completionDate;

    public function jsonSerialize(): array {
        return [
            'categoryCode'   => $this->categoryCode,
            'languageCode'   => $this->languageCode,
            'currencyCode'   => $this->currencyCode,
            'price'          => $this->price,
            'completionDate' => $this->completionDate->format('Y-m-d\TH:i:sO')
        ];
    }
}