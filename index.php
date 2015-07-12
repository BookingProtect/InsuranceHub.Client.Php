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

class InsureHubOfferingRequestor
{
    public $url;
    
    public function __construct($offeringRequestUrl) {
        $this->url = $offeringRequestUrl;
    }
    
	public function requestOffering($request)
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
        
        curl_close($ch);
        
        return json_decode($response);
    }
}

class InsureHubRequest implements JsonSerializable
{
    public $vendorId;
    public $apiKey;
    public $vendorRequestReference;
    public $products = [];
    public $premiumAsSummary = false;
    
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

$request = new InsureHubRequest();

$request->vendorId = "59B9F7DA-07BD-E311-9956-002186EB0899"; 
$request->apiKey = "EF4AA52D-C295-4330-B45E-33130EF0E109"; // should be read from config - value will be different for production 

$tokenGenerator = new InsureHubAuthTokenGenerator();
$authToken = $tokenGenerator->generateToken($request->vendorId, $request->apiKey);

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

$offeringRequestUrl = "http://uat.quote.insure-hub.net/quote"; // should be read from config - value will be different for production
$requestor = new InsureHubOfferingRequestor($offeringRequestUrl);

$offering = $requestor->requestOffering($request);

echo $offering->id

?>