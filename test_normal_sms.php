<?php
error_reporting(E_ALL);
ini_set('display_errors', 1);

$token = '3DyunMvIDbiq3wIb9YqV6kQaKMkFwXnikCIC1ZDc41936e5d';
$url   = 'https://app.pingzaar.com/api/v1/messages/send';

// Type integer 1 (Plain SMS)
$payload = [
    'mobile_numbers' => ['+923245032005'],
    'message'        => 'Hello Sohaib! PingZaar SMS Gateway test successful from EpowerEducation portal.',
    'type'           => 1,
    'sims'           => [17]
];

$curl = curl_init($url);
curl_setopt($curl, CURLOPT_RETURNTRANSFER, true);
curl_setopt($curl, CURLOPT_POST, true);
curl_setopt($curl, CURLOPT_POSTFIELDS, json_encode($payload));
curl_setopt($curl, CURLOPT_HTTPHEADER, [
    'Authorization: Bearer ' . trim($token),
    'Content-Type: application/json',
    'Accept: application/json'
]);
curl_setopt($curl, CURLOPT_SSL_VERIFYHOST, 0);
curl_setopt($curl, CURLOPT_SSL_VERIFYPEER, 0);

$res = curl_exec($curl);
$code = curl_getinfo($curl, CURLINFO_HTTP_CODE);
curl_close($curl);

echo "HTTP Status: " . $code . "\n";
echo "Response:\n" . $res . "\n";
