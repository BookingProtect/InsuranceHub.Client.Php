<?php
// ****************************************************************
// See https://developers.bookingprotect.com for further details
// ****************************************************************

// include client lib - as below or via compose
include __DIR__ . '/../vendor/autoload.php';

// import namespace
use BookingProtect\InsuranceHub\Client as BP;

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
$client = new BP\ApiClient($apiConfig, new BP\AuthTokenGenerator(), $urlBuilder);

$priceBandRequest = new BP\PriceBandRequest();
$priceBandRequest->productCode = 'TKT';
$priceBandRequest->currencyCode = 'EUR';
$priceBandRequest->price = 15.55;

try
{
    $break = php_sapi_name() == 'cli' ? "\n" : '</br>';

    echo 'Retrieving Price Band info for '.$priceBandRequest->productCode.', '.$priceBandRequest->currencyCode.' and '.$priceBandRequest->price.$break.$break;

    $priceBand = $client->getPriceBand($priceBandRequest);

    if (is_null($priceBand)){
        echo 'Price Band Not Found';
    }else{
        echo 'ID: '.$priceBand->id.$break;
        echo 'Upper Bound: '.$priceBand->upperBound.$break;
        echo 'Lower Bound: '.$priceBand->lowerBound.$break;
        echo 'Premium: '.$priceBand->premium.$break;
        echo 'Commission: '.$priceBand->commission.$break;
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