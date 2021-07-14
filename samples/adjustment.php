<?php
// ****************************************************************
// See https://developers.bookingprotect.com for further details
// ****************************************************************

// include client lib - as below or via compose
include '..\vendor\autoload.php';

// import namespace
use BookingProtect\InsuranceHub\Client as BP;
use GuzzleHttp\Client;

// read config from server or any other system you use to store configuration
// ** make sue this is not stored in a public folder **
$config = parse_ini_file(__DIR__ . '/config.ini');

// create client - can be instantiated as below or via dependency injection
$apiConfig = new BP\ApiClientConfiguration();

$apiConfig->environment = $config['environment'];
$apiConfig->certificatePath = __DIR__ . '/cacert.pem'; // change this to somewhere appropriate on your server (Latest Mozilla certificate store can be found here - https://curl.haxx.se/docs/caextract.html )
$apiConfig->apiKey = $config['api_key'];
$apiConfig->vendorId = $config['vendor_id'];

$urlBuilder = new BP\DefaultApiClientUrlBuilder($apiConfig);
$autoTokenGenerator = new BP\AuthTokenGenerator();
$httpClient = new Client();
$jsonMapper = new JsonMapper();
$client = new BP\ApiClient($apiConfig, new BP\AuthTokenGenerator(), $urlBuilder, $httpClient, $jsonMapper);

try
{
    $break = php_sapi_name() == 'cli' ? "\n" : '</br>';

    $vendorId = $config['vendor_id'];
    $existingSaleOfferingId = '192e07d3-deab-4160-9c0f-0a5fc248fd0b';

    // get existing policy details (optional)
    $policySearch = new BP\PolicySearchByOfferingId();
    $policySearch->offeringId = $existingSaleOfferingId;
    $policySearch->vendorId = $vendorId;

    $existingPolicy = $client->searchForPolicyByOfferingId($policySearch);

    if (is_null($existingPolicy)){
        echo 'Existing policy not found';
    }else{
        // create adjustment request object
        $adjustmentRequest = new BP\AdjustmentRequest();
        $adjustmentRequest->vendorId = $vendorId;
        $adjustmentRequest->offeringId = $existingSaleOfferingId;

        $request = new BP\OfferingRequest();
        $request->vendorId = $vendorId;

        // set values relating to transaction
        $request->vendorRequestReference = 'xyz798'; // this can be null - only used as an identifier for your request

        $newEventDate = new DateTime();
        $newEventDate->modify('+1 month');

        // create Products based on existing Policy with new value and/or date
        foreach ($existingPolicy->items as $exitingItem){
            $product = new BP\Product();
            $product->categoryCode = $exitingItem->categoryCode;
            $product->languageCode = $exitingItem->languageCode;
            $product->currencyCode = $exitingItem->currencyCode;
            $product->price = $exitingItem->value + 10.00;
            $product->completionDate = $newEventDate;

            $request->products[] = $product;
        }

        // add additional products
        $product = new BP\Product();
        $product->categoryCode = $existingPolicy->items[0]->categoryCode;
        $product->languageCode = $existingPolicy->items[0]->languageCode;
        $product->currencyCode = $existingPolicy->items[0]->currencyCode;
        $product->price = $existingPolicy->items[0]->value + 10.00;
        $product->completionDate = $newEventDate;

        $request->products[] = $product;

        $adjustmentRequest->offeringRequest = $request;

        echo 'Requesting AdjustmentOfferingRequest...'.$break.$break;

        $offering = $client->getAdjustmentOffering($adjustmentRequest);

        echo 'Adjustment Offering acquired with '.count($offering->productOfferings).' product offerings'.$break;
        echo 'Adjustment ID : '.$offering->id.$break;
        echo 'Offering ID : '.$offering->offeringId.$break;
        echo 'Original Total Premium : '.$offering->originalTotalPremium.$break;
        echo 'Total Premium : '.$offering->totalPremium.$break;
        echo 'Premium Difference : '.$offering->premiumDifference.$break;
        echo 'Currency Code : '.$offering->currencyCode.$break.$break;

        foreach($offering->productOfferings as $productOffering){
            echo 'Product Offering ID : '.$productOffering->id.$break;
            echo 'Price : '.$productOffering->premium.$break;
            echo 'Currency Code : '.$productOffering->currencyCode.$break;
            echo 'Sales message : '.$productOffering->wording->salesMessage.$break;
        }

        // create offering result and set security info
        $adjustmentOfferingResult = new BP\AdjustmentOfferingResult();
        $adjustmentOfferingResult->vendorId = $vendorId;
        $adjustmentOfferingResult->adjustmentId = $offering->id;
        $adjustmentOfferingResult->vendorSaleReference = 'New Sales Reference/Invoice Number';
        $adjustmentOfferingResult->sold = true;

        // send result
        echo $break.'Submitting OfferingAdjustmentResult...'.$break.$break;

        if ($client->submitAdjustmentResult($adjustmentOfferingResult)){
            echo 'Offering Adjustment result submit successfully';
        }else{
            echo 'Result failed';
        }
    }
}
catch(BP\InsureHubApiValidationException $validationException){
    echo 'Invalid Request';
    echo $validationException->errorMessage();
}
catch(BP\InsureHubApiAuthorisationException $authorisationException){
    echo 'Insufficient Permissions';
    echo $authorisationException->errorMessage();
}
catch(BP\InsureHubApiAuthenticationException $authenticationException){
    echo 'Unauthorized';
    echo $authenticationException->errorMessage();
}
catch(BP\InsureHubException $insureHubException){
    echo 'Error';
    echo $insureHubException->errorMessage();
}
catch(Exception $exception){
    echo $exception->getMessage();
}
