
<link rel="stylesheet" href="https://maxcdn.bootstrapcdn.com/bootstrap/3.3.7/css/bootstrap.min.css" integrity="sha384-BVYiiSIFeK1dGmJRAkycuHAHRg32OmUcww7on3RYdg4Va+PmSTsz/K68vbdEjh4u" crossorigin="anonymous" />

<?php

class InsureHubAuthTokenGenerator
{
	public function generateToken($vendorId, $apiKey)
    {
        $formattedVendorId = str_replace('-', '', strtolower($vendorId));
        $formattedApiKey = str_replace('-', '', strtolower($apiKey));
        $date = gmdate('dmY');

        return base64_encode(hash_hmac('sha1', $formattedVendorId.$date, $formattedApiKey, true));
    }
}

class InsureHubApiClient
{
    public $url;

    public function __construct($serviceUrl) {
        $this->url = $serviceUrl;
    }

	public function execute($request)
    {
        $tokenGenerator = new InsureHubAuthTokenGenerator();
        $authToken = $tokenGenerator->generateToken($request->vendorId, $request->apiKey);

        $ch = curl_init($this->url);

        curl_setopt($ch, CURLOPT_CUSTOMREQUEST, "POST");
        curl_setopt($ch, CURLOPT_POSTFIELDS, json_encode($request));
        curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
        curl_setopt($ch, CURLOPT_HTTPHEADER, [
            'Content-Type: application/json',
            'X-InsuranceHub-VendorId: '.$request->vendorId,
            'X-InsuranceHub-AuthToken: '.$authToken]);

        $response = curl_exec($ch);

        $httpCode = curl_getinfo($ch, CURLINFO_HTTP_CODE);

        switch ($httpCode) {
            case 401:
                throw new InsureHubApiAuthenticationException();
            case 403:
                throw new InsureHubApiAuthorisationException();
            case 400:
                $validationError = json_decode($response);

                $validationMessages = implode(",", $validationError->validationMessages);

                throw new InsureHubApiValidationException($validationMessages);
            case 500:
                $apiError = json_decode($response);
                throw new InsureHubApiException($apiError->message);
        }

        curl_close($ch);

        return array($httpCode, json_decode($response));
    }
}

class InsureHubRequest implements JsonSerializable
{
    public $vendorId;
    public $apiKey;
    public $vendorRequestReference;
    public $products = [];
    public $premiumAsSummary = true;

    public function jsonSerialize() {
        return [
            'vendorId' => $this->vendorId,
            'vendorRequestReference' => $this->vendorRequestReference,
            'products' => $this->products,
            'premiumAsSummary' => $this->premiumAsSummary
        ];
    }
}

class InsureHubProduct implements JsonSerializable
{
    public $categoryCode;
    public $price;
    public $completionDate;

    public function jsonSerialize() {
        return [
            'categoryCode' => $this->categoryCode,
            'price' => $this->price,
            'completionDate' => $this->completionDate->format(DateTime::ISO8601)
        ];
    }
}

class InsureHubResult implements JsonSerializable
{
    public $vendorId;
    public $apiKey;
    public $offeringId;
    public $vendorSaleReference;
    public $customerForename;
    public $customerSurname;
    public $sales = [];

    public function jsonSerialize() {
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

class InsureHubSale implements JsonSerializable
{
    public $productOfferingId;
    public $sold;

    public function jsonSerialize() {
        return [
            'productOfferingId' => $this->productOfferingId,
            'sold' => $this->sold
        ];
    }
}

abstract class InsureHubException extends Exception {
    abstract protected function errorMessage();
}

class InsureHubApiException extends InsureHubException {
  public function errorMessage() {
    $errorMsg = "InsureHub API Exception : ".$this->getMessage();
    return $errorMsg;
  }
}

class InsureHubApiAuthenticationException extends InsureHubException {
    public function errorMessage() {
        return "Unauthorised request.  Check your Vendor ID and Api Key";
    }
}

class InsureHubApiAuthorisationException extends InsureHubException {
    public function errorMessage() {
        return "You do not have access to this service.  Please contact support if you think this is in error.";
    }
}

class InsureHubApiValidationException extends InsureHubException {
    public function errorMessage() {
        return "Invalid Request : ".$this->getMessage();
    }
}

$vendorId = "59B9F7DA-07BD-E311-9956-002186EB0899"; // should be read from config
$apiKey = "EF4AA52D-C295-4330-B45E-33130EF0E109"; // 5should be read from config

$offeringRequestUrl = "http://uat.quote.insure-hub.net/quote"; // should be read from config
$offeringResultUrl = "http://uat.sales.insure-hub.net/sales"; // should be read from config

$break = "</br>";

// create request object and security info
$request = new InsureHubRequest();
$request->vendorId = $vendorId;
$request->apiKey = $apiKey;

// set values relating to transaction
$request->vendorRequestReference = "abc123";

$product1 = new InsureHubProduct();
$product1->categoryCode = 'TKT';
$product1->price = 100.00;
$product1->completionDate = new DateTime(); // event date

$product2 = new InsureHubProduct();
$product2->categoryCode = 'TKT';
$product2->price = 100.00;
$product2->completionDate = new DateTime(); // event date

$request->products[0] = $product1;
$request->products[1] = $product2;

// create client
$offeringClient = new InsureHubApiClient($offeringRequestUrl);

try{
    $offering = $offeringClient->execute($request)[1];

    echo "Offering aquired with ".count($offering->productOfferings)." product offerings".$break;
    echo "Offering ID : ".$offering->id.$break;

    foreach($offering->productOfferings as &$productOffering){
        echo "Product Offering ID : ".$productOffering->id.$break;
        echo "Price : ".$productOffering->premium.$break;
        echo "Currency Code : ".$productOffering->currencyCode.$break;
        echo "Sales message : ".$productOffering->wording->salesMessage.$break;
    }

    // create result and set security info
    $result = new InsureHubResult();
    $result->vendorId = $vendorId;
    $result->apiKey = $apiKey;

    // set transaction information
    $result->offeringId = $offering->id;
    $result->vendorSaleReference = "Your Sales Reference/Invoice Number";
    $result->customerSurname = "Customer Surname";
    $result->customerForename = "Customer Forename";

    // set customer's choice - did they want to protect their purcahse?
    for ($x = 0; $x < count($offering->productOfferings); $x++) {
        $sale = new InsureHubSale();
        $sale->productOfferingId = $offering->productOfferings[$x]->id;
        $sale->sold = true;

        $result->sales[$x] = $sale;
    }

    // send result
    $resultClient = new InsureHubApiClient($offeringResultUrl);

    $responseCode = $resultClient->execute($result)[0];

    // check success reponse code recieved (2xx)
    if ($responseCode >= 200 && $responseCode < 300)
    {
        echo "Offering result sent successfully";
    }
}
catch(InsureHubException $insureHubException){
    echo $insureHubException->errorMessage();
}
catch(Exception $exception){
    echo $exception->getMessage();
}

?>