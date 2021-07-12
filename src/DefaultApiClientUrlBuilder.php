<?php

namespace BookingProtect\InsuranceHub\Client;

/**
 * Class DefaultApiClientUrlBuilder
 *
 * Create your own version of this interface if you wish to use our custom urls
 */
class DefaultApiClientUrlBuilder implements IApiClientUrlBuilder {
    private const OFFERING_REQUEST_URL_PROD = 'https://quote.insure-hub.net/quote';
    private const OFFERING_RESULT_URL_PROD = 'https://sales.insure-hub.net/sales';
    private const CANCELLATION_URL_PROD = 'https://admin.insure-hub.net/cancellation/offering';
    private const ADJUSTMENT_REQUEST_URL_PROD = 'https://admin.insure-hub.net/adjustment/quote';
    private const ADJUSTMENT_RESULT_URL_PROD = 'https://admin.insure-hub.net/adjustment/sales';
    private const MATRIX_URL_PROD = 'https://admin.insure-hub.net/matrix/';
    private const POLICY_SEARCH_URL_PROD = 'https://admin.insure-hub.net/policy/search';
    private const POLICY_SEARCH_BY_OFFERING_ID_URL_PROD = 'https://admin.insure-hub.net/policy/search-by-offering-id';

    private const OFFERING_REQUEST_URL_UAT = 'https://quote.uat.insure-hub.net/quote';
    private const OFFERING_RESULT_URL_UAT = 'https://sales.uat.insure-hub.net/sales';
    private const CANCELLATION_URL_UAT = 'https://admin.uat.insure-hub.net/cancellation/offering';
    private const ADJUSTMENT_REQUEST_URL_UAT = 'https://admin.uat.insure-hub.net/adjustment/quote';
    private const ADJUSTMENT_RESULT_URL_UAT = 'https://admin.uat.insure-hub.net/adjustment/sales';
    private const MATRIX_URL_UAT = 'https://admin.uat.insure-hub.net/matrix/';
    private const POLICY_SEARCH_URL_UAT = 'https://admin.uat.insure-hub.net/policy/search';
    private const POLICY_SEARCH_BY_OFFERING_ID_URL_UAT = 'https://admin.uat.insure-hub.net/policy/search-by-offering-id';

    const ENVIRONMENT_PRODUCTION = 'PRODUCTION';
    const ENVIRONMENT_TESTING = 'UAT';

    private ApiClientConfiguration $configuration;

    public function __construct(ApiClientConfiguration $configuration) {
        $this->configuration = $configuration;
    }

    public function offeringRequestUrl(): string {
        if ($this->configuration->environment == self::ENVIRONMENT_PRODUCTION) {
            return self::OFFERING_REQUEST_URL_PROD;
        }

        return self::OFFERING_REQUEST_URL_UAT;
    }

    public function offeringResultUrl(): string {
        if ($this->configuration->environment == self::ENVIRONMENT_PRODUCTION) {
            return self::OFFERING_RESULT_URL_PROD;
        }

        return self::OFFERING_RESULT_URL_UAT;
    }

    public function cancellationUrl(): string {
        if ($this->configuration->environment == self::ENVIRONMENT_PRODUCTION) {
            return self::CANCELLATION_URL_PROD;
        }

        return self::CANCELLATION_URL_UAT;
    }

    public function adjustmentRequestUrl(): string {
        if ($this->configuration->environment == self::ENVIRONMENT_PRODUCTION) {
            return self::ADJUSTMENT_REQUEST_URL_PROD;
        }

        return self::ADJUSTMENT_REQUEST_URL_UAT;
    }

    public function adjustmentResultUrl(): string {
        if ($this->configuration->environment == self::ENVIRONMENT_PRODUCTION) {
            return self::ADJUSTMENT_RESULT_URL_PROD;
        }

        return self::ADJUSTMENT_RESULT_URL_UAT;
    }

    public function matrixUrl(MatrixRequest $matrixRequest): string {
        $url = self::MATRIX_URL_UAT;

        if ($this->configuration->environment == self::ENVIRONMENT_PRODUCTION) {
            $url = self::MATRIX_URL_PROD;
        }

        return $url . $matrixRequest->productCode . '/' . $matrixRequest->currencyCode;
    }

    public function priceBandUrl(PriceBandRequest $priceBandRequest): string {
        $url = self::MATRIX_URL_UAT;

        if ($this->configuration->environment == self::ENVIRONMENT_PRODUCTION) {
            $url = self::MATRIX_URL_PROD;
        }

        return $url . $priceBandRequest->productCode . '/' . $priceBandRequest->currencyCode . '/' . $priceBandRequest->price;
    }

    public function policySearchUrl(): string {
        if ($this->configuration->environment == self::ENVIRONMENT_PRODUCTION) {
            return self::POLICY_SEARCH_URL_PROD;
        }

        return self::POLICY_SEARCH_URL_UAT;
    }

    public function policySearchByOfferingIdUrl(): string {
        if ($this->configuration->environment == self::ENVIRONMENT_PRODUCTION) {
            return self::POLICY_SEARCH_BY_OFFERING_ID_URL_PROD;
        }

        return self::POLICY_SEARCH_BY_OFFERING_ID_URL_UAT;
    }
}