<?php

namespace BookingProtect\InsuranceHub\Client;

use Exception;
use GuzzleHttp\Client;
use GuzzleHttp\Exception\GuzzleException;
use GuzzleHttp\Psr7\Request;
use GuzzleHttp\Psr7\Response;
use GuzzleHttp\RequestOptions;
use JsonMapper;

class ApiClient implements IApiClient {
    private IAuthTokenGenerator $authTokenGenerator;
    private ApiClientConfiguration $configuration;
    private IApiClientUrlBuilder $apiClientUrlBuilder;
    private JsonMapper $jsonMapper;

    public function __construct(ApiClientConfiguration $configuration, IAuthTokenGenerator $authTokenGenerator, IApiClientUrlBuilder $apiClientUrlBuilder) {
        $this->authTokenGenerator  = $authTokenGenerator;
        $this->configuration       = $configuration;
        $this->apiClientUrlBuilder = $apiClientUrlBuilder;
        $this->jsonMapper          = new JsonMapper();
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

        return $this->jsonMapper->map(json_decode($result->getBody()->getContents()), new Offering());
    }

    /**
     * @throws InsureHubApiAuthorisationException
     * @throws InsureHubApiAuthenticationException
     * @throws InsureHubApiException
     * @throws InsureHubApiValidationException
     */
    public function submitOfferingResult(OfferingResult $offeringResult): bool {
        $result = $this->execute($this->apiClientUrlBuilder->offeringResultUrl(), 'POST', $offeringResult);

        return $result->getStatusCode() == 200;
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

        $data = $result->getBody()->getContents();
        if (is_null($data)) {
            return NULL;
        }

        return $this->jsonMapper->map(json_decode($data), new Matrix());
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

        $data = $result->getBody()->getContents();
        if (is_null($data)) {
            return null;
        }

        return $this->jsonMapper->map(json_decode($data), new PriceBand());
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

        $data = $result->getBody()->getContents();
        if (is_null($data)) {
            return [];
        }

        $arr = json_decode($data);

        return $this->jsonMapper->mapArray($arr, 'BookingProtect\InsuranceHub\Client\Policy');
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

        $data = $result->getBody()->getContents();
        if (is_null($data)) {
            return NULL;
        }

        return $this->jsonMapper->map(json_decode($data), new Policy());
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

        return $this->jsonMapper->map(json_decode($result->getBody()->getContents()), new AdjustmentOffering());
    }

    /**
     * @throws InsureHubApiAuthorisationException
     * @throws InsureHubApiAuthenticationException
     * @throws InsureHubApiException
     * @throws InsureHubApiValidationException
     */
    public function submitAdjustmentResult(AdjustmentOfferingResult $adjustmentResult): bool {
        $result = $this->execute($this->apiClientUrlBuilder->adjustmentResultUrl(), 'POST', $adjustmentResult);

        return $result->getStatusCode() == 201;
    }

    /**
     * @throws InsureHubApiAuthorisationException
     * @throws InsureHubApiAuthenticationException
     * @throws InsureHubApiException
     * @throws InsureHubApiValidationException
     */
    public function cancelSale(CancellationRequest $cancellationRequest): bool {
        $result = $this->execute($this->apiClientUrlBuilder->cancellationUrl(), 'POST', $cancellationRequest);

        return $result->getStatusCode() == 201;
    }

    /**
     * @param OfferingRequest|OfferingResult|MatrixRequest|PriceBandRequest|PolicySearch|PolicySearchByOfferingId|AdjustmentRequest|AdjustmentOfferingResult|CancellationRequest $requestBody
     *
     * @throws InsureHubApiAuthorisationException
     * @throws InsureHubApiAuthenticationException
     * @throws InsureHubApiException
     * @throws InsureHubApiValidationException
     */
    private function execute(string $url, string $method, $requestBody = NULL): Response {
        $authToken = $this->authTokenGenerator->generateToken($this->configuration->vendorId, $this->configuration->apiKey);

        $client = new Client();
        $request = new Request($method, $url, [
            'Content-Type' => 'application/json',
            'Authorization' => 'Bearer ' . $this->configuration->vendorId . '|' . $authToken
        ], $requestBody ? json_encode($requestBody) : null);
        try {
            $response = $client->send($request, [
                RequestOptions::VERIFY => $this->configuration->certificatePath
            ]);
            switch ($response->getStatusCode()) {
                case 401:
                    throw new InsureHubApiAuthenticationException();
                case 403:
                    throw new InsureHubApiAuthorisationException();
                case 400:
                    $validationError    = json_decode($response->getBody()->getContents());
                    $validationMessages = implode(',', $validationError->validationMessages);
                    throw new InsureHubApiValidationException($validationMessages);
                case 500:
                    $apiError = json_decode($response->getBody()->getContents());
                    throw new InsureHubApiException($apiError->message);
            }

            return $response;
        }
        catch (GuzzleException $e) {
            throw new InsureHubApiException($e);
        }
    }
}