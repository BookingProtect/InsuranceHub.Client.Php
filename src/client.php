<?php

namespace BookingProtect\InsuranceHub\Client;

use DateTime;
use Exception;
use JsonSerializable;
use ReflectionProperty;
use stdClass;
use ReflectionObject;

interface IAuthTokenGenerator
{
    public function generateToken(string $vendorId, string $apiKey): string;
}

interface IApiClient
{
    public function getOffering(OfferingRequest $offeringRequest): Offering;
    public function submitOfferingResult(OfferingResult $offeringResult): bool;
    public function getMatrix(MatrixRequest $matrixRequest): ?Matrix;
    public function getPriceBand(PriceBandRequest $priceBandRequest): ?PriceBand;
    /**
     * @return \BookingProtect\InsuranceHub\Client\Policy[]
     */
    public function searchForPolicy(PolicySearch $policySearch): array;
    public function searchForPolicyByOfferingId(PolicySearchByOfferingId $policySearch): ?Policy;
    public function getAdjustmentOffering(AdjustmentRequest $adjustmentRequest): AdjustmentOffering;
    public function submitAdjustmentResult(AdjustmentOfferingResult $adjustmentResult): bool;
    public function cancelSale(CancellationRequest $cancellationRequest): bool;
}

class AuthTokenGenerator implements IAuthTokenGenerator
{
    public function generateToken(string $vendorId, string $apiKey): string
    {
        $formattedVendorId = str_replace('-', '', strtolower($vendorId));
        $formattedApiKey = str_replace('-', '', strtolower($apiKey));
        $date = gmdate('dmY');

        return base64_encode(hash_hmac('sha256', $formattedVendorId.$date, $formattedApiKey, true));
    }
}

class ApiClient implements IApiClient
{
    private IAuthTokenGenerator $authTokenGenerator;
    private ApiClientConfiguration $configuration;
    private IApiClientUrlBuilder $apiClientUrlBuilder;

    public function __construct(ApiClientConfiguration $configuration, IAuthTokenGenerator $authTokenGenerator, IApiClientUrlBuilder $apiClientUrlBuilder)
    {
        $this->authTokenGenerator = $authTokenGenerator;
        $this->configuration = $configuration;
        $this->apiClientUrlBuilder = $apiClientUrlBuilder;
    }

    /**
     * @throws InsureHubApiAuthorisationException
     * @throws InsureHubApiAuthenticationException
     * @throws InsureHubApiException
     * @throws InsureHubApiValidationException
     * @throws Exception
     */
    public function getOffering(OfferingRequest $offeringRequest): Offering {
        $result = $this->execute($this->apiClientUrlBuilder->offeringRequestUrl(), 'POST', $offeringRequest);

        return $this->map(json_decode($result->responseBody), new Offering());
    }

    /**
     * @throws InsureHubApiAuthorisationException
     * @throws InsureHubApiAuthenticationException
     * @throws InsureHubApiException
     * @throws InsureHubApiValidationException
     */
    public function submitOfferingResult(OfferingResult $offeringResult): bool {
        $result = $this->execute($this->apiClientUrlBuilder->offeringResultUrl(), 'POST', $offeringResult);

        return $result->statusCode == 200;
    }

    /**
     * @throws InsureHubApiAuthorisationException
     * @throws InsureHubApiAuthenticationException
     * @throws InsureHubApiException
     * @throws InsureHubApiValidationException
     * @throws Exception
     */
    public function getMatrix(MatrixRequest $matrixRequest): ?Matrix {
        $result = $this->execute($this->apiClientUrlBuilder->matrixUrl($matrixRequest), 'GET');

        if (is_null($result->responseBody)){
            return null;
        }

        return $this->map(json_decode($result->responseBody), new Matrix());
    }

    /**
     * @throws InsureHubApiAuthorisationException
     * @throws InsureHubApiAuthenticationException
     * @throws InsureHubApiException
     * @throws InsureHubApiValidationException
     * @throws Exception
     */
    public function getPriceBand(PriceBandRequest $priceBandRequest): ?PriceBand {
        $result = $this->execute($this->apiClientUrlBuilder->priceBandUrl($priceBandRequest), 'GET');

        if (is_null($result->responseBody)){
            return null;
        }

        return $this->map(json_decode($result->responseBody), new PriceBand());
    }

    /**
     * @return \BookingProtect\InsuranceHub\Client\Policy[]
     * @throws InsureHubApiAuthenticationException
     * @throws InsureHubApiException
     * @throws InsureHubApiValidationException
     * @throws InsureHubApiAuthorisationException
     * @throws Exception
     */
    public function searchForPolicy(PolicySearch $policySearch): array {
        $result = $this->execute($this->apiClientUrlBuilder->policySearchUrl(), 'POST', $policySearch);

        if (is_null($result->responseBody)){
            return [];
        }

        $arr = json_decode($result->responseBody);

        return $this->mapArray($arr, 'BookingProtect\InsuranceHub\Client\Policy');
    }

    /**
     * @throws InsureHubApiAuthorisationException
     * @throws InsureHubApiAuthenticationException
     * @throws InsureHubApiException
     * @throws InsureHubApiValidationException
     * @throws Exception
     */
    public function searchForPolicyByOfferingId(PolicySearchByOfferingId $policySearch): ?Policy {
        $result = $this->execute($this->apiClientUrlBuilder->policySearchByOfferingIdUrl(), 'POST', $policySearch);

        if (is_null($result->responseBody)){
            return null;
        }

        return $this->map(json_decode($result->responseBody), new Policy());
    }

    /**
     * @throws InsureHubApiAuthorisationException
     * @throws InsureHubApiAuthenticationException
     * @throws InsureHubApiException
     * @throws InsureHubApiValidationException
     * @throws Exception
     */
    public function getAdjustmentOffering(AdjustmentRequest $adjustmentRequest): AdjustmentOffering {
        $result = $this->execute($this->apiClientUrlBuilder->adjustmentRequestUrl(), 'POST', $adjustmentRequest);

        return $this->map(json_decode($result->responseBody), new AdjustmentOffering());
    }

    /**
     * @throws InsureHubApiAuthorisationException
     * @throws InsureHubApiAuthenticationException
     * @throws InsureHubApiException
     * @throws InsureHubApiValidationException
     */
    public function submitAdjustmentResult(AdjustmentOfferingResult $adjustmentResult): bool {
        $result = $this->execute($this->apiClientUrlBuilder->adjustmentResultUrl(), 'POST', $adjustmentResult);

        return $result->statusCode == 201;
    }

    /**
     * @throws InsureHubApiAuthorisationException
     * @throws InsureHubApiAuthenticationException
     * @throws InsureHubApiException
     * @throws InsureHubApiValidationException
     */
    public function cancelSale(CancellationRequest $cancellationRequest): bool {
        $result = $this->execute($this->apiClientUrlBuilder->cancellationUrl(), 'POST', $cancellationRequest);

        return $result->statusCode == 201;
    }

    /**
     * @param OfferingRequest|OfferingResult|MatrixRequest|PriceBandRequest|PolicySearch|PolicySearchByOfferingId|AdjustmentRequest|AdjustmentOfferingResult|CancellationRequest $requestBody
     * @throws InsureHubApiAuthorisationException
     * @throws InsureHubApiAuthenticationException
     * @throws InsureHubApiException
     * @throws InsureHubApiValidationException
     */
    private function execute(string $url, string $method, $requestBody = null): CurlResponse
    {
        $authToken = $this->authTokenGenerator->generateToken($this->configuration->vendorId, $this->configuration->apiKey);

        $ch = curl_init($url);

        curl_setopt($ch, CURLOPT_CUSTOMREQUEST, $method);
        if ($method == 'POST'){
            curl_setopt($ch, CURLOPT_POSTFIELDS, json_encode($requestBody));
        }
        curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
        curl_setopt($ch, CURLOPT_SSL_VERIFYHOST, 2);
        curl_setopt($ch, CURLOPT_CAINFO, $this->configuration->certificatePath);
        curl_setopt($ch, CURLOPT_HTTPHEADER, [
            'Content-Type: application/json',
            'Authorization: Bearer '.$this->configuration->vendorId.'|'.$authToken]);

        $response = curl_exec($ch);

        $httpCode = curl_getinfo($ch, CURLINFO_HTTP_CODE);

        switch ($httpCode) {
            case 401:
                throw new InsureHubApiAuthenticationException();
            case 403:
                throw new InsureHubApiAuthorisationException();
            case 400:
                $validationError = json_decode($response);

                $validationMessages = implode(',', $validationError->validationMessages);

                throw new InsureHubApiValidationException($validationMessages);
            case 500:
                $apiError = json_decode($response);
                throw new InsureHubApiException($apiError->message);
        }

        curl_close($ch);

        $result = new CurlResponse();

        if (trim($response) == ''){
            $response = null;
        }

        $result->statusCode = $httpCode;
        $result->responseBody = $response;

        return $result;
    }

    /**
     * @param stdClass $sourceObject
     * @param Offering|Matrix|PriceBand|Policy|AdjustmentOffering $destination
     * @return Offering|Matrix|PriceBand|Policy|AdjustmentOffering
     * @throws Exception
     */
    private function map(stdClass $sourceObject, $destination)
    {
        $sourceReflection = new ReflectionObject($sourceObject);
        $destinationReflection = new ReflectionObject($destination);
        $sourceProperties = $sourceReflection->getProperties(ReflectionProperty::IS_PUBLIC);

        foreach ($sourceProperties as $sourceProperty) {
            $sourceProperty->setAccessible(true);
            $name = $sourceProperty->getName();
            $value = $sourceProperty->getValue($sourceObject);
            if ($destinationReflection->hasProperty($name)) {
                $propDest = $destinationReflection->getProperty($name);

                if ($propDest->hasType() && $propDest->getType() == 'DateTime'){
                    if (is_null($value) && $propDest->getType()->allowsNull()){
                        $value = null;
                    }else{
                        $date = date_create_from_format('Y-m-d\TH:i:s.v\Z', $value);

                        if ($date == false){
                            $date = date_create_from_format('Y-m-d\TH:i:s\Z', $value);
                        }

                        if ($date == false){
                            $value = null;
                        }

                        $value = $date;
                    }
                } elseif ($propDest->hasType() && $propDest->getType() == 'array'){
                    $comment = $propDest->getDocComment();

                    if ($comment != false){
                        $comment = preg_replace('/\r\n/i', '', $comment);
                        $comment = str_replace('/', '', $comment);
                        $comment = str_replace('*', '', $comment);
                        $comment = str_replace('@var', '', $comment);
                        $comment = str_replace(' ', '', $comment);
                        $comment = str_replace('[]', '', $comment);

                        $value = $this->mapArray($value, $comment);
                    }
                }elseif ($propDest->hasType() && !$propDest->getType()->isBuiltin()){
                    $typeName = $propDest->getType()->getName();
                    $value = $this->map($value, new $typeName());
                }

                $propDest->setAccessible(true);
                $propDest->setValue($destination, $value);
            } else {
                $destination->$name = $value;
            }
        }
        return $destination;
    }

    /**
     * @param stdClass[] $source
     * @param string $destinationType
     * @return array
     * @throws Exception
     */
    private function mapArray(array $source, string $destinationType): array
    {
        $result = [];
        foreach ($source as $item) {
            $destination = new $destinationType();
            $result[] = $this->map($item, $destination);
        }

        return $result;
    }
}

class ApiClientConfiguration
{
    public string $apiKey;
    public string $vendorId;
    public string $certificatePath;
    public string $environment;
}

interface IApiClientUrlBuilder
{
    public function offeringRequestUrl(): string;
    public function offeringResultUrl(): string ;
    public function cancellationUrl(): string ;
    public function adjustmentRequestUrl(): string;
    public function adjustmentResultUrl(): string;
    public function matrixUrl(MatrixRequest $matrixRequest): string;
    public function priceBandUrl(PriceBandRequest $priceBandRequest): string;
    public function policySearchUrl(): string;
    public function policySearchByOfferingIdUrl(): string;
}

/**
 * Class DefaultApiClientUrlBuilder
 *
 * Create your own version of this interface if you wish to use our custom urls
 */
class DefaultApiClientUrlBuilder implements IApiClientUrlBuilder
{
    private const OFFERING_REQUEST_URL_PROD             = 'https://quote.insure-hub.net/quote';
    private const OFFERING_RESULT_URL_PROD              = 'https://sales.insure-hub.net/sales';
    private const CANCELLATION_URL_PROD                 = 'https://admin.insure-hub.net/cancellation/offering';
    private const ADJUSTMENT_REQUEST_URL_PROD           = 'https://admin.insure-hub.net/adjustment/quote';
    private const ADJUSTMENT_RESULT_URL_PROD            = 'https://admin.insure-hub.net/adjustment/sales';
    private const MATRIX_URL_PROD                       = 'https://admin.insure-hub.net/matrix/';
    private const POLICY_SEARCH_URL_PROD                = 'https://admin.insure-hub.net/policy/search';
    private const POLICY_SEARCH_BY_OFFERING_ID_URL_PROD = 'https://admin.insure-hub.net/policy/search-by-offering-id';

    private const OFFERING_REQUEST_URL_UAT              = 'https://quote.uat.insure-hub.net/quote';
    private const OFFERING_RESULT_URL_UAT               = 'https://sales.uat.insure-hub.net/sales';
    private const CANCELLATION_URL_UAT                  = 'https://admin.uat.insure-hub.net/cancellation/offering';
    private const ADJUSTMENT_REQUEST_URL_UAT            = 'https://admin.uat.insure-hub.net/adjustment/quote';
    private const ADJUSTMENT_RESULT_URL_UAT             = 'https://admin.uat.insure-hub.net/adjustment/sales';
    private const MATRIX_URL_UAT                        = 'https://admin.uat.insure-hub.net/matrix/';
    private const POLICY_SEARCH_URL_UAT                 = 'https://admin.uat.insure-hub.net/policy/search';
    private const POLICY_SEARCH_BY_OFFERING_ID_URL_UAT  = 'https://admin.uat.insure-hub.net/policy/search-by-offering-id';

    private ApiClientConfiguration $configuration;

    public function __construct(ApiClientConfiguration $configuration)
    {
        $this->configuration = $configuration;
    }

    public function offeringRequestUrl(): string {
        if ($this->configuration->environment == 'PRODUCTION'){
            return self::OFFERING_REQUEST_URL_PROD;
        }

        return self::OFFERING_REQUEST_URL_UAT;
    }

    public function offeringResultUrl(): string {
        if ($this->configuration->environment == 'PRODUCTION'){
            return self::OFFERING_RESULT_URL_PROD;
        }

        return self::OFFERING_RESULT_URL_UAT;
    }

    public function cancellationUrl(): string {
        if ($this->configuration->environment == 'PRODUCTION'){
            return self::CANCELLATION_URL_PROD;
        }

        return self::CANCELLATION_URL_UAT;
    }

    public function adjustmentRequestUrl(): string {
        if ($this->configuration->environment == 'PRODUCTION'){
            return self::ADJUSTMENT_REQUEST_URL_PROD;
        }

        return self::ADJUSTMENT_REQUEST_URL_UAT;
    }

    public function adjustmentResultUrl(): string {
        if ($this->configuration->environment == 'PRODUCTION'){
            return self::ADJUSTMENT_RESULT_URL_PROD;
        }

        return self::ADJUSTMENT_RESULT_URL_UAT;
    }

    public function matrixUrl(MatrixRequest $matrixRequest): string
    {
        $url = self::MATRIX_URL_UAT;

        if ($this->configuration->environment == 'PRODUCTION'){
            $url = self::MATRIX_URL_PROD;
        }

        return $url.$matrixRequest->productCode.'/'.$matrixRequest->currencyCode;
    }

    public function priceBandUrl(PriceBandRequest $priceBandRequest): string
    {
        $url = self::MATRIX_URL_UAT;

        if ($this->configuration->environment == 'PRODUCTION'){
            $url = self::MATRIX_URL_PROD;
        }

        return $url.$priceBandRequest->productCode.'/'.$priceBandRequest->currencyCode.'/'.$priceBandRequest->price;
    }

    public function policySearchUrl(): string
    {
        if ($this->configuration->environment == 'PRODUCTION'){
            return self::POLICY_SEARCH_URL_PROD;
        }

        return self::POLICY_SEARCH_URL_UAT;
    }

    public function policySearchByOfferingIdUrl(): string
    {
        if ($this->configuration->environment == 'PRODUCTION'){
            return self::POLICY_SEARCH_BY_OFFERING_ID_URL_PROD;
        }

        return self::POLICY_SEARCH_BY_OFFERING_ID_URL_UAT;
    }
}

class CurlResponse
{
    public int $statusCode;
    public ?string $responseBody;
}

class OfferingRequest implements JsonSerializable
{
    public function __construct(){
        $this->vendorRequestReference = null;
        $this->products = [];
        $this->premiumAsSummary = true;
    }

    public string $vendorId;
    public ?string $vendorRequestReference;
    /**
     * @var \BookingProtect\InsuranceHub\Client\Product[]
     */
    public array $products;
    public bool $premiumAsSummary;

    public function jsonSerialize(): array {
        return [
            'vendorId' => $this->vendorId,
            'vendorRequestReference' => $this->vendorRequestReference,
            'products' => $this->products,
            'premiumAsSummary' => $this->premiumAsSummary
        ];
    }
}

class Product implements JsonSerializable
{
    public function __construct()
    {
        $this->currencyCode = null;
        $this->languageCode = null;
    }

    public string $categoryCode;
    public ?string $languageCode;
    public ?string $currencyCode;
    public string $price;
    public DateTime $completionDate;

    public function jsonSerialize(): array {
        return [
            'categoryCode' => $this->categoryCode,
            'languageCode' => $this->languageCode,
            'currencyCode' => $this->currencyCode,
            'price' => $this->price,
            'completionDate' => $this->completionDate->format('Y-m-d\TH:i:sO')
        ];
    }
}

class Offering
{
    public function __construct(){
        $this->productOfferings = [];
        $this->productsOutsideOfPricing = [];
    }

    public string $id;
    /**
     * @var \BookingProtect\InsuranceHub\Client\ProductOffering[]
     */
    public array $productOfferings;
    /**
     * @var \BookingProtect\InsuranceHub\Client\Product[]
     */
    public ?array $productsOutsideOfPricing;
}

class ProductOffering
{
    public string $id;
    public string $categoryCode;
    public string $currencyCode;
    public float $premium;
    public ProductOfferingWording $wording;
}

class ProductOfferingWording
{
    public string $categoryCode;
    public string $description;
    public string $languageCode;
    public string $salesProcessCode;
    public string $salesProcessMessage;
    public string $salesMessage;
    public ?string $advertisement;
    public ?string $advertisementUrl;
    public string $logoUrl;
    public string $termsAndConditions;
}

class OfferingResult implements JsonSerializable
{
    public function __construct()
    {
        $this->vendorSaleReference = null;
        $this->customerForename = null;
        $this->customerSurname = null;
        $this->sales = [];
    }

    public string $vendorId;
    public string $offeringId;
    public ?string $vendorSaleReference;
    public ?string $customerForename;
    public ?string $customerSurname;
    /**
     * @var \BookingProtect\InsuranceHub\Client\ProductOfferingResult[]
     */
    public array $sales;

    public function jsonSerialize(): array {
        return [
            'vendorId' => $this->vendorId,
            'offeringId' => $this->offeringId,
            'vendorSaleReference' => $this->vendorSaleReference,
            'customerForename' => $this->customerForename,
            'customerSurname' => $this->customerSurname,
            'sales' => $this->sales
        ];
    }
}

class ProductOfferingResult implements JsonSerializable
{
    public string $productOfferingId;
    public bool $sold;

    public function jsonSerialize(): array {
        return [
            'productOfferingId' => $this->productOfferingId,
            'sold' => $this->sold
        ];
    }
}

class CancellationRequest implements JsonSerializable
{
    public string $offeringId;

    public function jsonSerialize(): array {
        return [
            'offeringId' => $this->offeringId
        ];
    }
}

class MatrixRequest
{
    public string $productCode;
    public string $currencyCode;
}

class PriceBandRequest extends MatrixRequest
{
    public float $price;
}

class Matrix
{
    public string $id;
    /**
     * @var \BookingProtect\InsuranceHub\Client\PriceBand[]
     */
    public array $priceBands;
    public string $pricingModel;
    public bool $locked;
    public DateTime $validFrom;
    public ?DateTime $validTo;
}

class PriceBand
{
    public string $id;
    public float $lowerBound;
    public float $upperBound;
    public float $premium;
    public float $commission;
}

class PolicySearch implements JsonSerializable
{
    public function __construct()
    {
        $this->vendorSalesReference = null;
        $this->customerForename = null;
        $this->customerSurname = null;
    }

    public string $vendorId;
    public ?string $vendorSalesReference;
    public ?string $customerForename;
    public ?string $customerSurname;

    public function jsonSerialize(): array {
        return [
            'vendorId' => $this->vendorId,
            'vendorSalesReference' => $this->vendorSalesReference,
            'customerForename' => $this->customerForename,
            'customerSurname' => $this->customerSurname
        ];
    }
}

class PolicySearchByOfferingId implements JsonSerializable
{
    public string $vendorId;
    public string $offeringId;

    public function jsonSerialize(): array {
        return [
            'vendorId' => $this->vendorId,
            'offeringId' => $this->offeringId
        ];
    }
}

class Policy
{
    public string $offeringId;
    public string $vendorId;
    public string $vendorName;
    public DateTime $purchaseDate;
    public string $vendorSalesReference;
    public string $customerForename;
    public string $customerSurname;
    /**
     * @var \BookingProtect\InsuranceHub\Client\PolicyItem[]
     */
    public array $items;
}

class PolicyItem
{
    public string $productOfferingId;
    public float $value;
    public float $premium;
    public string $currencyCode;
    public string $categoryCode;
    public string $languageCode;
    public DateTime $completionDate;
    public bool $cancelled;
    public ?DateTime $cancelledDate;
}

class AdjustmentRequest
{
    public string $vendorId;
    public string $offeringId;
    public OfferingRequest $offeringRequest;
}

class AdjustmentOffering
{
    public string $id;
    public string $offeringId;
    public float $originalTotalPremium;
    public float $totalPremium;
    public float $premiumDifference;
    public string $currencyCode;
    /**
     * @var \BookingProtect\InsuranceHub\Client\ProductOffering[]
     */
    public array $productOfferings;
}

class AdjustmentOfferingResult
{
    public string $vendorId;
    public string $adjustmentId;
    public string $vendorSaleReference;
    public bool $sold;
}

abstract class InsureHubException extends Exception {
    abstract public function errorMessage();
}

class InsureHubApiException extends InsureHubException {
    public function errorMessage(): string {
        return 'InsureHub API Exception : '.$this->getMessage();
    }
}

class InsureHubApiAuthenticationException extends InsureHubException {
    public function errorMessage(): string {
        return 'Unauthorised request.  Check your Vendor ID and Api Key';
    }
}

class InsureHubApiAuthorisationException extends InsureHubException {
    public function errorMessage(): string {
        return 'You do not have access to this service.  Please contact support if you think this is in error.';
    }
}

class InsureHubApiValidationException extends InsureHubException {
    public function errorMessage(): string {
        return 'Invalid Request : '.$this->getMessage();
    }
}
