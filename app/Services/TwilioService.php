<?php

namespace App\Services;

use Twilio\Rest\Client;

class TwilioService
{
    protected $client;
    protected $verifySid;

    public function __construct()
    {
        $this->client = new Client(
            config('services.twilio.sid'),
            config('services.twilio.token')
        );

        $this->verifySid = config('services.twilio.verify_sid');
    }

    public function sendOTP($phone)
    {
        return $this->client->verify->v2->services($this->verifySid)
            ->verifications
            ->create($phone, "sms");
    }

    public function verifyOTP($phone, $code)
    {
        return $this->client->verify->v2->services($this->verifySid)
            ->verificationChecks
            ->create([
                'to' => $phone,
                'code' => $code
            ]);
    }
}
