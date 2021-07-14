<?php

namespace BookingProtect\InsuranceHub\Client;

interface IApiClientUrlBuilder {
    public function offeringRequestUrl(): string;

    public function offeringResultUrl(): string;

    public function cancellationUrl(): string;

    public function adjustmentRequestUrl(): string;

    public function adjustmentResultUrl(): string;

    public function matrixUrl(MatrixRequest $matrixRequest): string;

    public function priceBandUrl(PriceBandRequest $priceBandRequest): string;

    public function policySearchUrl(): string;

    public function policySearchByOfferingIdUrl(): string;
}