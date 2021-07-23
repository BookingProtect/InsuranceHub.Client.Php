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

$cancellationRequest = new BP\CancellationRequest();
$cancellationRequest->offeringId = '8be79be5-e276-432b-b888-23bc099e067a';

try
{
    $break = php_sapi_name() == 'cli' ? "\n" : '</br>';

    echo 'Cancelling sale for Offering '.$cancellationRequest->offeringId.$break.$break;

    $result = $client->cancelSale($cancellationRequest);

    if ($result == true){
        echo 'Sale cancelled';
    }else{
        echo 'Unable to cancel sale';
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