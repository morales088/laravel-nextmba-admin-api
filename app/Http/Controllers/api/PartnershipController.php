<?php

namespace App\Http\Controllers\api;

use App\Models\Partnership;
use Illuminate\Http\Request;
use App\Http\Controllers\Controller;
use Illuminate\Support\Facades\Auth;

class PartnershipController extends Controller
{
    public function __construct()
    {
        $this->middleware('auth:api');
    }

    public function getPendingRequest() {

        $applications = Partnership::whereStatus(0)->get();
        // dd($applications);
        return response()->json([
            'applications' => $applications
        ], 200);
    }

    public function getAffiliates() {

        $applications = Partnership::whereStatus(1)->get();
        // dd($applications);
        return response()->json([
            'applications' => $applications
        ], 200);
    }

    public function approveAffiliate(Request $request, $id) {

        $request->query->add(['id' => $id]);
        $request->validate([
            'id' => 'required|numeric|min:1|exists:partnerships,id',
            'affiliate_status' => 'required|in:approved,disapproved',
            'remarks' => 'string|max:255'
        ]);

        // $admin = Auth::user($id);
        $application = Partnership::findOrFail($id);

        if ($request->affiliate_status === 'approved') {
            $application->update([
                'admin_id' => Auth::user()->id,
                'affiliate_status' => 1, // approved
                'affiliate_code' => bin2hex(random_bytes(5)), // generating a unique code
                'remarks' => $request->remarks
            ]);

            return response()->json([
                'message' => "Application has been approved successfully.",
                 'application' => $application
            ], 200);

        } elseif ($request->affiliate_status === 'disapproved') {
            $application->update([
                'affiliate_status' => 2, // declined
                'remarks' => $request->remarks
            ]);

            return response()->json([
                'message' => "Application has been disapproved successfully."
            ], 200);
        }
    }
}
