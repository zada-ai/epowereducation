<?php
error_reporting(E_ALL);
ini_set('display_errors', 1);

$api_token = '180b8450eefc65da0de1cf88d83de0588a31eeba';
$account_unique_id = '1792321924c4ca4238a0b923820dcc509a6f75849b6ad4a984d1034';
$recipient_number = '+923245032005';
$message_text = 'Hello Sohaib! WhatsApp integration live test successful via PingZaar Server!';

$post_fields = [
    'secret'    => trim($api_token),
    'account'   => $account_unique_id,
    'recipient' => $recipient_number,
    'type'      => 'text',
    'message'   => $message_text
];

$curl = curl_init('https://wa.pingzaar.com/api/send/whatsapp');
curl_setopt($curl, CURLOPT_RETURNTRANSFER, true);
curl_setopt($curl, CURLOPT_POST, true);
curl_setopt($curl, CURLOPT_POSTFIELDS, http_build_query($post_fields));
curl_setopt($curl, CURLOPT_HTTPHEADER, [
    'Authorization: Bearer ' . trim($api_token),
    'Accept: application/json'
]);
curl_setopt($curl, CURLOPT_SSL_VERIFYHOST, 0);
curl_setopt($curl, CURLOPT_SSL_VERIFYPEER, 0);

$response = curl_exec($curl);
$http_code = curl_getinfo($curl, CURLINFO_HTTP_CODE);
curl_close($curl);

echo "HTTP Status: " . $http_code . "\n";
echo "Response: " . $response . "\n";
