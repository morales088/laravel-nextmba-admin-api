<?php

namespace App\Http\Controllers\api;

use App\Models\Student;
use App\Models\Partnership;
use Illuminate\Http\Request;
use App\Http\Controllers\Controller;
use App\Models\PartnershipWithdraws;
use Illuminate\Support\Facades\Auth;
use DB;

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

        foreach ($applications as $key => $value) {
            // dd($value->toArray());
            $commission = DB::TABLE('payments as p')
                            ->leftJoin('withdrawal_payments as wp', 'wp.payment_id', '=', 'p.id')
                            ->leftJoin('partnership_withdraws as pw', function($join)
                                {
                                    $join->on('wp.withdrawal_id', '=', 'pw.id');
                                    $join->on('p.from_student_id', '=' ,'pw.student_id');
                                    // $join->on('pw.commission_status', '=', DB::raw(2));
                                })
                            ->where('p.from_student_id', $value->student_id)
                            ->where('p.status', 'paid')
                            // ->whereNull('pw.id')
                            ->select('p.*', 
                                    DB::raw('if(pw.id is null or pw.commission_status != 2, (p.price * p.commission_percentage), 0) as balance'),
                                    DB::raw('if(pw.commission_status = 2, (p.price * p.commission_percentage), 0) as withdraw'),
                                    DB::raw('(p.price * p.commission_percentage) as total_commission'),
                                    )

                            ->get();
            $clicks = DB::TABLE('events as e')
                        ->where('partnership_id', $value->id)
                        ->where('type', 'click')
                        ->get();
            // dd($clicks->count());
            $value->total_clicks = $clicks->count();

            $value->affiliate_purchases = $commission->count();
            $value->balance = $commission->sum('balance');
            $value->total_withdraw_amount = $commission->sum('withdraw');
            $value->total_commission_amount = $commission->sum('total_commission');
        }

        // dd($applications);
        return response()->json([
            'applications' => $applications,
            'pending' => $grouped->has(0) ? $grouped->get(0)->count() : 0,
            'declined' => $grouped->has(2) ? $grouped->get(2)->count() : 0,
            'approved' => $grouped->has(1) ? $grouped->get(1)->count() : 0,
            'balance' => 0
        ], 200);
    }

    public function getWithdrawals() {
        
        $withdrawals = PartnershipWithdraws::with(['student:id,email'])
                        ->orderBy('commission_status', 'ASC')
                        ->get();
        $grouped = $withdrawals->groupBy('commission_status');

        return response()->json([
            'withdrawals' => $withdrawals,
            'pending' => $grouped->has(0) ? $grouped->get(0)->count() : 0,
            'declined' => $grouped->has(2) ? $grouped->get(2)->count() : 0,
            'processed' => $grouped->has(1) ? $grouped->get(1)->count() : 0,
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

    public function updateAffiliate(Request $request, $id) {

        $request->query->add(['id' => $id]);
        $request->validate([
            'id' => 'required|numeric|min:1|exists:partnerships,id',
            'affiliate_status' => 'in:pending,approved,declined',
            'affiliate_code' => 'sometimes|max:100',
            'withdraw_method' => 'sometimes|max:255',
            // 'percentage' => 'required|in:0.15,0.25',
            'remarks' => 'nullable|string|max:255'
        ]);

        $application = Partnership::findOrFail($id);
        $student = Student::findOrFail($application->student_id);

        if ($request->affiliate_status == 'approved') {
            // check if the student has existing affiliate code
            if ($request->affiliate_code) {
                $affiliate_code = $request->affiliate_code;
            } elseif ($application->affiliate_code) {
                $affiliate_code = $application->affiliate_code;
            } else {
                $affiliate_code = bin2hex(random_bytes(5)); // generating temporary unique code
            }

            $withdraw_method = $request->withdraw_method ?? $application->withdraw_method;
            
            $application->update([
                'admin_id' => Auth::user()->id,
                'affiliate_status' => 1, // approved
                'affiliate_code' => $affiliate_code, 
                'percentage' => $request->percentage,
                'withdraw_method' => $withdraw_method,
                'remarks' => $request->remarks
            ]);

            $student->update([
                'affiliate_access' => 1 // update to partner
            ]);

            return response()->json([
                'message' => "Application has been approved successfully.",
                'application' => $application
            ], 200);

        } elseif ($request->affiliate_status == 'declined') {
            $application->update([
                'admin_id' => Auth::user()->id,
                'affiliate_status' => 2, // declined
                'remarks' => $request->remarks
            ]);

            return response()->json([
                'message' => "Application has been declined successfully.",
                'application' => $application
            ], 200);
        } elseif ($request->affiliate_status == 'pending') {
            $application->update([
                'admin_id' => Auth::user()->id,
                'affiliate_status' => 0, // pending
                'remarks' => $request->remarks
            ]);

            return response()->json([
                'message' => "Application has been updated to pending.",
                'application' => $application
            ], 200);
        } else {
            return response()->json([
                'application' => $application
            ]);
        }
    }

    // Initial commit
    public function updateWithdraw(Request $request, $id) {

        $request->query->add(['id' => $id]);
        $request->validate([
            'id' => 'required|numeric|min:1|exists:partnership_withdraws,id',
            'commission_status' => 'in:pending,processed,declined',
            'withdraw_method' => 'sometimes|max:255',
            'remarks' => 'nullable|string|max:255'
        ]);

        // find($withdraw->student_id);
        $withdraw = PartnershipWithdraws::findOrFail($id);
        $student = Student::findOrFail($withdraw->student_id);
        // $partnership = Partnership::where('student_id', $withdraw->student_id)->first();
        // dd($partnership->withdraw_method);

        if ($request->commission_status == 'processed') {

            // $withdraw_method = $request->withdraw_method ?? $partnership->withdraw_method;
            
            $withdraw->update([
                'admin_id' => Auth::user()->id,
                'commission_status' => 2, // processed
                'withdraw_method' => $request->withdraw_method, // $withdraw_method,
                'remarks' => $request->remarks
            ]);

            return response()->json([
                'message' => "Withdraw has been updated successfully.",
                'withdraw' => $withdraw
            ], 200);

        } elseif ($request->commission_status == 'declined') {
            $withdraw->update([
                'admin_id' => Auth::user()->id,
                'commission_status' => 1, // declined
                'remarks' => $request->remarks
            ]);

            return response()->json([
                'message' => "Withdraw has been declined successfully.",
                'withdraw' => $withdraw
            ], 200);
        } elseif ($request->commission_status == 'pending') {
            $withdraw->update([
                'admin_id' => Auth::user()->id,
                'commission_status' => 0, // pending
                'remarks' => $request->remarks
            ]);

            return response()->json([
                'message' => "Withdraw has been updated to pending.",
                'withdraw' => $withdraw
            ], 200);
        } else {
            return response()->json([
                'withdraw' => $withdraw
            ]);
        }
    }
}
