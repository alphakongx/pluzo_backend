<?php
require 'vendor/autoload.php';
use Aws\Sns\SnsClient; 
use Aws\Exception\AwsException;

$SnSclient = new SnsClient([
    //'profile' => 'default',
    'region' => 'us-east-1',
    'version' => 'latest',
    'credentials' => [
        'key'    => 'AKIARQV6Y3IO6M6PO4FQ',
        'secret' => 'Iq4xC+NHWFINEWjj2F4o59FsPEMTW11eJnLCymv+',
    ]
]);

$phone = str_replace(' ', '', $_POST['phone']);
$message = $_POST['message'];
/*
$phone = '+6282144424304';
$message = 'test sms';
*/
$result = $SnSclient->publish([
        'Message' => $message,
        'PhoneNumber' => $phone,
    ]);
?>