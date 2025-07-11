<?php
// Enable CORS for frontend access
header("Access-Control-Allow-Origin: *");
header("Access-Control-Allow-Headers: *");

// Allow POST only
if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    echo json_encode(['error' => 'Only POST method allowed']);
    exit;
}

// Read phone and amount from frontend
$phone = $_POST['phone'] ?? '';
$amount = $_POST['amount'] ?? '';

if (!$phone || !$amount) {
    echo json_encode(['error' => 'Missing phone or amount']);
    exit;
}

// --- M-Pesa Sandbox Credentials ---
$consumerKey = 'Zj59TDGxP8RtFOT58zs2d7pexmvBuiblCINT1usG2WJoBMqy';
$consumerSecret = 'IYq9pVfBBYwybo1s2oZ5Hb9GUgPkmIgQizjlF5VF5nZBQDQ3waUa2YIrwCG3BOvE';
$BusinessShortCode = '174379';
$Passkey = 'mQyG25zNMLU/ortqkwRECnFiTmjPiyLblG5YUY/TrOK+Gu5/vZOgmMe2ou9SxAhXYnCnLQf+1jdk8MilgbZass20lmgBwSZx1RV6qhuxQ9KfRC8AJEpAHo7E8fnKia6q31tEdl96M58EKzhcwXzJH7ExtDAHnYObiUoRmWgMVf4vT+TeOGsXSiq8gNtaqteXnnvFDDqTxdf1FldkF/lgILKNRbQW6uLB3Rq4GjWxa8fR2NRQZLhqr/Imbr/sTt5FEyU3BYeBIhRG1krflpZLYJSg+hsmLXLIYfQx0fCa3CWOe17H8Lo40x9Sn11g03VPsumxtd831LGplhoMoxFpjg==';

$Timestamp = date('YmdHis');
$Password = base64_encode($BusinessShortCode . $Passkey . $Timestamp);
$AccountReference = 'NewFlex';
$TransactionDesc = 'Order Payment';
$CallBackURL = 'https://mydomain.com/callback.php'; // optional

// 1. Get Access Token
$credentials = base64_encode("$consumerKey:$consumerSecret");

$curl = curl_init();
curl_setopt($curl, CURLOPT_URL, 'https://sandbox.safaricom.co.ke/oauth/v1/generate?grant_type=client_credentials');
curl_setopt($curl, CURLOPT_HTTPHEADER, ["Authorization: Basic $credentials"]);
curl_setopt($curl, CURLOPT_RETURNTRANSFER, true);
$token_response = curl_exec($curl);
curl_close($curl);

$accessToken = json_decode($token_response)->access_token ?? null;

if (!$accessToken) {
    echo json_encode(['error' => 'Failed to get access token']);
    exit;
}

// 2. Prepare STK push request
$stkData = [
    'BusinessShortCode' => $BusinessShortCode,
    'Password' => $Password,
    'Timestamp' => $Timestamp,
    'TransactionType' => 'CustomerPayBillOnline',
    'Amount' => (int)$amount,
    'PartyA' => $phone,
    'PartyB' => $BusinessShortCode,
    'PhoneNumber' => $phone,
    'CallBackURL' => $CallBackURL,
    'AccountReference' => $AccountReference,
    'TransactionDesc' => $TransactionDesc
];

$curl2 = curl_init();
curl_setopt($curl2, CURLOPT_URL, 'https://sandbox.safaricom.co.ke/mpesa/stkpush/v1/processrequest');
curl_setopt($curl2, CURLOPT_HTTPHEADER, [
    "Content-Type: application/json",
    "Authorization: Bearer $accessToken"
]);
curl_setopt($curl2, CURLOPT_POST, true);
curl_setopt($curl2, CURLOPT_POSTFIELDS, json_encode($stkData));
curl_setopt($curl2, CURLOPT_RETURNTRANSFER, true);
$response2 = curl_exec($curl2);
curl_close($curl2);

// Return JSON response to frontend
echo $response2;
?>
