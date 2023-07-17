<?php

namespace App\Http\Controllers\api;

use App\Models\Partner;
use App\Models\Payment;
use Illuminate\Http\Request;
use App\Http\Controllers\Controller;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Facades\Mail;
use App\Mail\BusinessCredentialEmail;

class BusinessPartnerController extends Controller {

    public function index() {
        
        $partners = Partner::where('status', 1)
            ->orderBy('created_at', 'desc')
            ->get();

        $partnersData = [];

        foreach ($partners as $partner) {
            $payments = Payment::where('partner_id', $partner->id)->get();
            $paymentCount = $payments->count();
            $totalAmount = $payments->sum('price');

            $partnersData[] = [
                'partner' => $partner,
                'payment_count' => $paymentCount,
                'total_amount' => $totalAmount
            ];
        }

        return response()->json([
            'business_partners' => $partnersData
        ]);
    }

    public function createPartnerAccount(Request $request) {
        
        $request->validate([
            'name' => 'required|string',
            'email' => 'required|email|unique:partners,email',
        ]);

        $generated_password = Payment::generate_password();
        $hashed_password = Hash::make($generated_password);

        $business_partner = Partner::create([
            'name' => $request->name,
            'email' => $request->email,
            'password' => $hashed_password
        ]);

        // sent credentials to email
        Mail::to($request->email)->send(new BusinessCredentialEmail(
            $request->email, 
            $generated_password
        ));

        return response()->json([
            'business_partner_account' => $business_partner
        ]);
    }
}
