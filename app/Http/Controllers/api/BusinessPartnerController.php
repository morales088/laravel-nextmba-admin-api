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
use Illuminate\Pagination\LengthAwarePaginator;

class BusinessPartnerController extends Controller {

    public function index(Request $request) {
        
        $currentPage = $request->query('page', 1);
        $perPage = $request->query('per_page', 10);
        $offset = $request->query('offset', ($currentPage - 1) * $perPage);

        $partners = Partner::where('status', 1)
            ->orderBy('created_at', 'desc')
            ->where('status', '<>', 0)
            ->offset($offset)
            ->limit($perPage)
            ->get();

        $items = Partner::where('status', '<>', 0)->count();
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

        $businessPartners = new LengthAwarePaginator($partnersData, $items, $perPage, $currentPage, [
            'path' => $request->url(),
            'query' => $request->query()
        ]);

        return response()->json([
            'business_partners' => $businessPartners
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
