<?php

namespace App\Http\Controllers\api;

use App\Models\Student;
use App\Models\Partnership;
use Illuminate\Http\Request;
use App\Http\Controllers\Controller;
use Illuminate\Support\Facades\Auth;

class PartnershipController extends Controller
{
    public function __construct()
    {
        $this->middleware("auth:api");
    }

    public function getApplications() {
        
        $applications = Partnership::where('status', '<>', 0)
                        ->orderBy('affiliate_status', 'ASC')
                        ->with(['student:id,name,email'])
                        ->get();
        $grouped = $applications->groupBy('affiliate_status');

        return response()->json([
            'applications' => $applications,
            'pending' => $grouped->has(0) ? $grouped->get(0)->count() : 0,
            'disapproved' => $grouped->has(2) ? $grouped->get(2)->count() : 0,
            'approved' => $grouped->has(1) ? $grouped->get(1)->count() : 0,
            'balance' => 0
        ], 200);
    }

    public function getPendingRequest() {

        $applications = Partnership::whereStatus(0)
                        ->where('status', '<>', 0)
                        ->orderBy('id', 'DESC')
                        ->get();

        return response()->json([
            'applications' => $applications
        ], 200);
    }

    public function getAffiliates() {

        $applications = Partnership::whereStatus(1)
                        ->where('status', '<>', 0)
                        ->orderBy('id', 'DESC')
                        ->get();
                        
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
        $student = Student::findOrFail($application->student_id);

        if ($request->affiliate_status === 'approved') {
            // check if the student has existing affiliate code
            if (!$application->affiliate_code) {
                $affiliate_code = bin2hex(random_bytes(5));
            } else {
                $affiliate_code = $application->affiliate_code;
            }
            
            $application->update([
                'admin_id' => Auth::user()->id,
                'affiliate_status' => 1, // approved
                'affiliate_code' => $affiliate_code, // generating temporary unique code
                'percentage' => 0.15, // once approved update percentage
                'remarks' => $request->remarks
            ]);

            $student->update([
                'affiliate_access' => 1 // update to partner
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
