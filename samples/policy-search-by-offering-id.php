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

$vendorId = $config['vendor_id'];

$policySearch = new BP\PolicySearchByOfferingId();

$policySearch->vendorId = $vendorId;
$policySearch->offeringId = '009357d8-881a-4e1d-9467-a2dca89ab757';

try
{
    $break = php_sapi_name() == 'cli' ? "\n" : '</br>';

    echo 'Searching for policy with ID '.$policySearch->offeringId.'...'.$break.$break;

    $policy = $client->searchForPolicyByOfferingId($policySearch);

    if (is_null($policy)){
        echo 'Policy Not Found';
    }else{
        echo 'Offering ID: '.$policy->offeringId.$break;
        echo 'Forename: '.$policy->customerForename.$break;
        echo 'Surname: '.$policy->customerSurname.$break;
        echo 'Vendor Sales Reference: '.$policy->vendorSalesReference.$break;
        echo 'Purchase Date: '.$policy->purchaseDate->format('Y-m-d\TH:i:s.vO').$break;
        echo 'Policy has '.count($policy->items).' policy items'.$break.$break;

        foreach ($policy->items as $policyItem){
            echo 'Product Offering ID: '.$policyItem->productOfferingId.$break;
            echo 'Value: '.$policyItem->value.$break;
            echo 'Premium: '.$policyItem->premium.$break;
            echo 'Currency Code: '.$policyItem->currencyCode.$break;
            echo 'Language Code: '.$policyItem->languageCode.$break;
            echo 'Completion Date: '.$policyItem->completionDate->format('Y-m-d\TH:i:s.vO').$break;
            echo 'Cancelled: '.$policyItem->cancelled.$break;
            if ($policyItem->cancelled){
                echo 'Cancelled Date: '.$policyItem->cancelledDate->format('Y-m-d\TH:i:s.vO').$break;
            }
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