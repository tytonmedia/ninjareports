<?php

namespace App\Http\Controllers;

use App\Models\NinjaReport;
use App\Models\Plan;
use App\Models\Schedule;
 
class ReportSenderController extends Controller
{
    public function run()
    {
        $pdf_dir = public_path('files/pdf/');
        if (!is_dir($pdf_dir)) {
            mkdir($pdf_dir, 0777, true);
        }
        
        $now = date('Y-m-d H:i:00');
        $reports = NinjaReport::where('next_send_time', $now)
                    ->where('is_active', 1)
                    ->where('is_paused', 0)
                    ->with('template','accounts.ad_account.account','user')
                    ->get();
                    
        if ($reports->isNotEmpty()) {
            foreach ($reports as $report) {
                $currentPlan = $report->user->current_billing_plan ? $report->user->current_billing_plan : 'free_trial';
                $plan = Plan::whereTitle($currentPlan)->first();
                $reportsSentCount = Schedule::whereUserId($report->user->id)
                        ->whereBetween('created_at', [date('Y-m-01 00:00:00'), date('Y-m-t 00:00:00')])
                        ->count();
                        
                if ($reportsSentCount > $plan->reports) {
                    continue;
                }

                $recipients = array_filter(explode(',', $report->recipients));
                if (count($recipients) < 0) {
                    continue;
                }

                (new \App\Services\Report\ReportSender)->send($report);
                update_schedule($report, $report->user_id);
            }
        }
    }
}