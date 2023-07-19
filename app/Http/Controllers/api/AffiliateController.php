<?php

namespace App\Http\Controllers\api;

use DB;
use App\Models\Student;
use App\Models\Affiliate;
use App\Traits\Filterable;
use Illuminate\Http\Request;
use App\Models\WithdrawalPayment;
use App\Models\AffiliateWithdraws;
use App\Http\Controllers\Controller;
use Illuminate\Support\Facades\Auth;

class AffiliateController extends Controller
{
    use Filterable;

    public function __construct()
    {
        $this->middleware("auth:api");
    }

    public function getApplications(Request $request) {

        $status = $request->input('status');
        $dateRange = $request->input('date_range');
        $email = $request->input('email');
        $perPage = $request->input('per_page', 10);
        
        $applications = Affiliate::where('status', '<>', 0)
            ->orderBy('created_at', 'DESC')
            ->with(['student:id,name,email']);

        // apply filters
        $applications = $this->filterData($applications, $status, $dateRange, $email, 'affiliate_status');
        $applications = $applications->paginate($perPage);

        foreach ($applications as $key => $value) {
            $commission = DB::TABLE('payments as p')
                ->leftJoin('withdrawal_payments as wp', 'wp.payment_id', '=', 'p.id')
                ->leftJoin('affiliate_withdraws as aw', function($join) {
                    $join->on('wp.withdrawal_id', '=', 'aw.id');
                    $join->on('p.from_student_id', '=' ,'aw.student_id');
                })
                ->where('p.from_student_id', $value->student_id)
                ->where('p.status', 'paid')
                ->select('p.*', 
                    DB::raw('if(aw.id is null or aw.commission_status != 2, (p.price * p.commission_percentage), 0) as balance'),
                    DB::raw('if(aw.commission_status = 2, (p.price * p.commission_percentage), 0) as withdraw'),
                    DB::raw('(p.price * p.commission_percentage) as total_commission'),
                )
                ->get();

            $clicks = DB::TABLE('events as e')
                ->where('affiliate_id', $value->id)
                ->where('type', 'click')
                ->get();
  
            $value->total_clicks = $clicks->count();

            $value->affiliate_purchases = $commission->count();
            $value->balance = $commission->sum('balance');
            $value->total_withdraw_amount = $commission->sum('withdraw');
            $value->total_commission_amount = $commission->sum('total_commission');
        }

        return response()->json([
            'applications' => $applications,
            'counts' => $this->countAffiliates()
        ], 200);
    }

    public function countAffiliates() {

        $affiliate = Affiliate::where('status', '<>', 0)->get();
        $grouped = $affiliate->groupBy('affiliate_status');

        return [
            'pending' => $grouped->has(0) ? $grouped->get(0)->count() : 0,
            'approved' => $grouped->has(1) ? $grouped->get(1)->count() : 0,
            'declined' => $grouped->has(2) ? $grouped->get(2)->count() : 0
        ];
    }

    public function getWithdrawals(Request $request) {
        
        $status = $request->input('status');
        $dateRange = $request->input('date_range');
        $email = $request->input('email');
        $perPage = $request->input('per_page', 10);

        $withdrawals = AffiliateWithdraws::with(['student:id,email'])
            ->orderBy('created_at', 'DESC');
                                    
        // apply filters
        $withdrawals = $this->filterData($withdrawals, $status, $dateRange, $email, 'commission_status');
        $withdrawals = $withdrawals->paginate($perPage);

        return response()->json([
            'withdrawals' => $withdrawals,
            'counts' => $this->countWithdrawals()
        ], 200);
    }

    public function countWithdrawals() {

        $withdrawals = AffiliateWithdraws::all();
        $grouped = $withdrawals->groupBy('commission_status');

        return [
            'declined' => $grouped->has(0) ? $grouped->get(0)->count() : 0,
            'pending' => $grouped->has(1) ? $grouped->get(1)->count() : 0,
            'processed' => $grouped->has(2) ? $grouped->get(2)->count() : 0
        ];
    }

    public function updateAffiliate(Request $request, $id) {

        $application = Affiliate::findOrFail($id);

        $request->query->add(['id' => $id]);
        $request->validate([
            'id' => 'required|numeric|min:1|exists:affiliates,id',
            'affiliate_status' => 'in:pending,approved,declined',
            'affiliate_code' => 'sometimes|max:100|unique:affiliates,affiliate_code,'. $application->id,
            'withdraw_method' => 'sometimes|max:255',
            'remarks' => 'nullable|string|max:255'
        ]);

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
            $percentage = $request->percentage ?? $application->percentage;
            
            $application->update([
                'admin_id' => Auth::user()->id,
                'affiliate_status' => 1, // approved
                'affiliate_code' => $affiliate_code, 
                'percentage' => $percentage,
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

    public function updateWithdraw(Request $request, $id) {

        $request->query->add(['id' => $id]);
        $request->validate([
            'id' => 'required|numeric|min:1|exists:affiliate_withdraws,id',
            'commission_status' => 'in:pending,processed,declined',
            'withdraw_method' => 'sometimes|max:255',
            'remarks' => 'nullable|string|max:255'
        ]);

        $withdraw = AffiliateWithdraws::findOrFail($id);
        $student = Student::findOrFail($withdraw->student_id);
        $affiliate = Affiliate::where('student_id', $withdraw->student_id)->first();

        $payment = DB::TABLE('affiliate_withdraws as aw')
            ->leftJoin('withdrawal_payments as wp', 'wp.withdrawal_id', '=', 'aw.id')
            ->leftJoin('payments as p', 'p.id', '=', 'wp.payment_id')
            ->where('aw.id', $id);

        if ($withdraw->commission_status == 0) {
            return response()->json([
                'message' => "Withdraw has already been declined and can't be updated.",
                'withdraw' => $withdraw
            ]);
        }

        $withdraw_method = $request->withdraw_method ?? $affiliate->withdraw_method;
        
        if ($request->commission_status == 'processed') {

            $withdraw->update([
                'admin_id' => Auth::user()->id,
                'commission_status' => 2, // processed
                'withdraw_method' => $withdraw_method,
                'remarks' => $request->remarks
            ]);

            $payment->update(['p.commission_status' => 1]);

            return response()->json([
                'message' => "Withdraw has been updated successfully.",
                'withdraw' => $withdraw
            ], 200);

        } elseif ($request->commission_status == 'declined') {
            $withdraw->update([
                'admin_id' => Auth::user()->id,
                'commission_status' => 0, // declined
                'remarks' => $request->remarks
            ]);

            $payment->update(['p.commission_status' => 0]);

            return response()->json([
                'message' => "Withdraw has been declined successfully.",
                'withdraw' => $withdraw
            ], 200);

        } elseif ($request->commission_status == 'pending') {
            $withdraw->update([
                'admin_id' => Auth::user()->id,
                'commission_status' => 1, // pending
                'remarks' => $request->remarks
            ]);

            $payment->update(['p.commission_status' => 0]);

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
