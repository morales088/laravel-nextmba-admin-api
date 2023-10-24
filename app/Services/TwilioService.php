<?php

namespace App\Services;

use Exception;
use Twilio\Rest\Client;


class TwilioService
{

  public function sendSmsCredential($studentInfo) {
    $email = $studentInfo['email'];
    $phone = $studentInfo['phone'] ?? null;
    $password = $studentInfo['password'];

    $receiverNumber = $phone;
    $message = "Dear User, your login credentials are as follows:\nEmail Address: $email\nPassword: $password";

    $account_sid = getenv('TWILIO_ACCOUNT_SID');
    $auth_token = getenv('TWILIO_AUTH_TOKEN');
    $twilio_number = getenv("TWILIO_FROM");

    try {
      $client = new Client($account_sid, $auth_token);
      $client->messages->create($receiverNumber, [
        'from' => $twilio_number,
        'body' => $message
      ]);
    } catch (Exception $e) {
      Log::error('Twilio Error: ' . $e->getMessage());
    }

  }

}