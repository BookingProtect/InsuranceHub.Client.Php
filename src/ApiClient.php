<?php

namespace BookingProtect\InsuranceHub\Client;

use Exception;
use ReflectionObject;
use ReflectionProperty;
use stdClass;

class ApiClient implements IApiClient {
    private IAuthTokenGenerator $authTokenGenerator;
    private ApiClientConfiguration $configuration;
    private IApiClientUrlBuilder $apiClientUrlBuilder;

    public function __construct(ApiClientConfiguration $configuration, IAuthTokenGenerator $authTokenGenerator, IApiClientUrlBuilder $apiClientUrlBuilder) {
        $this->authTokenGenerator  = $authTokenGenerator;
        $this->configuration       = $configuration;
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

        if (is_null($result->responseBody)) {
            return NULL;
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

        if (is_null($result->responseBody)) {
            return NULL;
        }

        return $this->map(json_decode($result->responseBody), new PriceBand());
    }

    /**
     * @return Policy[]
     * @throws InsureHubApiAuthenticationException
     * @throws InsureHubApiException
     * @throws InsureHubApiValidationException
     * @throws InsureHubApiAuthorisationException
     * @throws Exception
     */
    public function searchForPolicy(PolicySearch $policySearch): array {
        $result = $this->execute($this->apiClientUrlBuilder->policySearchUrl(), 'POST', $policySearch);

        if (is_null($result->responseBody)) {
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

        if (is_null($result->responseBody)) {
            return NULL;
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
     *
     * @throws InsureHubApiAuthorisationException
     * @throws InsureHubApiAuthenticationException
     * @throws InsureHubApiException
     * @throws InsureHubApiValidationException
     */
    private function execute(string $url, string $method, $requestBody = NULL): CurlResponse {
        $authToken = $this->authTokenGenerator->generateToken($this->configuration->vendorId, $this->configuration->apiKey);

        $ch = curl_init($url);

        curl_setopt($ch, CURLOPT_CUSTOMREQUEST, $method);
        if ($method == 'POST') {
            curl_setopt($ch, CURLOPT_POSTFIELDS, json_encode($requestBody));
        }
        curl_setopt($ch, CURLOPT_RETURNTRANSFER, TRUE);
        curl_setopt($ch, CURLOPT_SSL_VERIFYHOST, 2);
        curl_setopt($ch, CURLOPT_CAINFO, $this->configuration->certificatePath);
        curl_setopt($ch, CURLOPT_HTTPHEADER, [
            'Content-Type: application/json',
            'Authorization: Bearer ' . $this->configuration->vendorId . '|' . $authToken
        ]);

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

        if (trim($response) == '') {
            $response = NULL;
        }

        $result->statusCode   = $httpCode;
        $result->responseBody = $response;

        return $result;
    }

    /**
     * @param stdClass $sourceObject
     * @param Offering|Matrix|PriceBand|Policy|AdjustmentOffering $destination
     *
     * @return Offering|Matrix|PriceBand|Policy|AdjustmentOffering
     * @throws Exception
     */
    private function map(stdClass $sourceObject, $destination) {
        $sourceReflection      = new ReflectionObject($sourceObject);
        $destinationReflection = new ReflectionObject($destination);
        $sourceProperties      = $sourceReflection->getProperties(ReflectionProperty::IS_PUBLIC);

        foreach ($sourceProperties as $sourceProperty) {
            $sourceProperty->setAccessible(TRUE);
            $name  = $sourceProperty->getName();
            $value = $sourceProperty->getValue($sourceObject);
            if ($destinationReflection->hasProperty($name)) {
                $propDest = $destinationReflection->getProperty($name);

                if ($propDest->hasType() && $propDest->getType() == 'DateTime') {
                    if (is_null($value) && $propDest->getType()->allowsNull()) {
                        $value = NULL;
                    } else {
                        $date = date_create_from_format('Y-m-d\TH:i:s.v\Z', $value);

                        if ($date == FALSE) {
                            $date = date_create_from_format('Y-m-d\TH:i:s\Z', $value);
                        }

                        if ($date == FALSE) {
                            $value = NULL;
                        }

                        $value = $date;
                    }
                } elseif ($propDest->hasType() && $propDest->getType() == 'array') {
                    $comment = $propDest->getDocComment();

                    if ($comment != FALSE) {
                        $comment = preg_replace('/\r\n/i', '', $comment);
                        $comment = str_replace('/', '', $comment);
                        $comment = str_replace('*', '', $comment);
                        $comment = str_replace('@var', '', $comment);
                        $comment = str_replace(' ', '', $comment);
                        $comment = str_replace('[]', '', $comment);

                        $value = $this->mapArray($value, $comment);
                    }
                } elseif ($propDest->hasType() && ! $propDest->getType()->isBuiltin()) {
                    $typeName = $propDest->getType()->getName();
                    $value    = $this->map($value, new $typeName());
                }

                $propDest->setAccessible(TRUE);
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
     *
     * @return array
     * @throws Exception
     */
    private function mapArray(array $source, string $destinationType): array {
        $result = [];
        foreach ($source as $item) {
            $destination = new $destinationType();
            $result[]    = $this->map($item, $destination);
        }

        return $result;
    }
}