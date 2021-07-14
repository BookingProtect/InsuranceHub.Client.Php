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

$matrixRequest = new BP\MatrixRequest();
$matrixRequest->productCode = 'TKT';
$matrixRequest->currencyCode = 'EUR';

try
{
    $break = php_sapi_name() == 'cli' ? "\n" : '</br>';

    echo 'Retrieving Matrix info for '.$matrixRequest->productCode.' and '.$matrixRequest->currencyCode.$break.$break;

    $matrix = $client->getMatrix($matrixRequest);

    if (is_null($matrix)){
        echo 'Matrix Not Found';
    }else{
        echo 'ID: '.$matrix->id.$break;
        echo 'Pricing Model: '.$matrix->pricingModel.$break;
        echo 'Valid From: '.$matrix->validFrom->format('Y-m-d\TH:i:s.vO').$break;
        if (is_null($matrix->validTo)){
            echo 'Valid To: Currently Active'.$break;
        } else{
            echo 'Valid To: '.$matrix->validTo->format('Y-m-d\TH:i:s.vO').$break;
        }

        echo 'Matrix has '.count($matrix->priceBands).' price bands'.$break.$break;

        foreach ($matrix->priceBands as $priceBand){
            echo 'ID: '.$priceBand->id.$break;
            echo 'Upper Bound: '.$priceBand->upperBound.$break;
            echo 'Lower Bound: '.$priceBand->lowerBound.$break;
            echo 'Premium: '.$priceBand->premium.$break;
            echo 'Commission: '.$priceBand->commission.$break;
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