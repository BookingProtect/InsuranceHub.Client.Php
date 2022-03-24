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

$vendorId = $config['vendor_id'];

$policySearch = new BP\PolicySearch();

$policySearch->vendorId = $vendorId;
$policySearch->vendorSalesReference = uniqid('TEST_REF_'); // this can be null - only used as an identifier for your request
$policySearch->customerForename = 'Customer Forename';
$policySearch->customerSurname = 'Customer Surname';

try
{
    $break = php_sapi_name() == 'cli' ? "\n" : '</br>';

    echo 'Searching for policies...'.$break.$break;

    $policies = $client->searchForPolicy($policySearch);

    echo count($policies).' policies found'.$break.$break;

    foreach ($policies as $policy){
        echo 'Offering ID: '.$policy->offeringId.$break;
        echo 'Forename: '.$policy->customerForename.$break;
        echo 'Surname: '.$policy->customerSurname.$break;
        echo 'Email Address: '.$policy->emailAddress.$break;
        echo 'Vendor Sales Reference: '.$policy->vendorSalesReference.$break;
        echo 'Purchase Date: '.$policy->purchaseDate->format('Y-m-d\TH:i:s.vO').$break;
        echo 'Policy has '.count($policy->items).' policy items'.$break.$break;
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