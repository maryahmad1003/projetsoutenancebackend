<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use App\Services\TwilioService;

class AuthController extends Controller
{
    protected $twilio;

    public function __construct(TwilioService $twilio)
    {
        $this->twilio = $twilio;
    }

    // 📲 envoyer OTP
    public function sendOTP(Request $request)
    {
        $request->validate([
            'phone' => 'required'
        ]);

        $this->twilio->sendOTP($request->phone);

        return response()->json([
            'message' => 'OTP envoyé'
        ]);
    }

    // ✅ vérifier OTP
    public function verifyOTP(Request $request)
    {
        $request->validate([
            'phone' => 'required',
            'code' => 'required'
        ]);

        $result = $this->twilio->verifyOTP(
            $request->phone,
            $request->code
        );

        if ($result->status === 'approved') {
            return response()->json([
                'message' => 'Authentification réussie'
            ]);
        }

        return response()->json([
            'message' => 'Code invalide'
        ], 400);
    }
}
