<?php
// ****************************************************************
// See https://developers.bookingprotect.com for further details
// ****************************************************************

// include client lib - as below or via compose
include __DIR__ . '/../vendor/autoload.php';

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

// create offering request object
$request = new BP\OfferingRequest();
$request->vendorId = $config['vendor_id'];

// set values relating to transaction
$request->vendorRequestReference = uniqid('TEST_REF_'); // this can be null - only used as an identifier for your request

$eventDate = new DateTime();
$eventDate->modify('+1 month');

// create products from user's shopping cart
$product1 = new BP\Product();
$product1->categoryCode = 'TKT';
$product1->languageCode = 'spa';
$product1->currencyCode = 'EUR';
$product1->price = 100.00;
$product1->completionDate = $eventDate;

$product2 = new BP\Product();
$product2->categoryCode = 'TKT';
$product2->languageCode = 'spa';
$product2->currencyCode = 'EUR';
$product2->price = 100.00;
$product2->completionDate = $eventDate;

$request->products[] = $product1;
$request->products[] = $product2;

try
{
    $break = php_sapi_name() == 'cli' ? "\n" : '</br>';

    echo 'Sending OfferingRequest...'.$break.$break;

    $offering = $client->getOffering($request);

    echo 'Offering acquired with '.count($offering->productOfferings).' product offerings'.$break.$break;
    echo 'Offering ID : '.$offering->id.$break.$break;

    foreach($offering->productOfferings as $productOffering){
        echo 'Product Offering ID : '.$productOffering->id.$break;
        echo 'Price : '.$productOffering->premium.$break;
        echo 'Currency Code : '.$productOffering->currencyCode.$break;
        echo 'Sales message : '.$productOffering->wording->salesMessage.$break;
    }

    // create offering result
    $offeringResult = new BP\OfferingResult();
    $offeringResult->vendorId = $config['vendor_id'];

    // set transaction information
    $offeringResult->offeringId = $offering->id;
    $offeringResult->vendorSaleReference = 'Your Sales Reference/Invoice Number';
    $offeringResult->customerSurname = 'Customer Surname';
    $offeringResult->customerForename = 'Customer Forename';

    // set customer's choice - did they want to protect their purchase?
    foreach ($offering->productOfferings as $productOffering) {
        $sale = new BP\ProductOfferingResult();
        $sale->productOfferingId = $productOffering->id;
        $sale->sold = true;

        $offeringResult->sales[] = $sale;
    }

    // send result
    echo $break.'Submitting OfferingResult...'.$break.$break;

    if ($client->submitOfferingResult($offeringResult)){
        echo "Offering result submit successfully$break";
    }else{
        echo "Result failed$break";
    }
}
catch(BP\InsureHubApiNotFoundException $validationException){
    echo 'Not found';
    echo $validationException->errorMessage();
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
