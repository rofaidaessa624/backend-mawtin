<?php

namespace App\Console\Commands;

use App\Models\Installment;
use App\Models\Notification;
use Carbon\Carbon;
use Illuminate\Console\Command;

class CheckUpcomingInstallments extends Command
{
    protected $signature = 'installments:check-upcoming';
    protected $description = 'إرسال إشعارات للأقساط المستحقة خلال 10 أيام';

    public function handle()
    {
        $today = Carbon::today();
        $tenDaysLater = Carbon::today()->addDays(10);

        // جلب الأقساط اللي هتستحق خلال 10 أيام
        $upcomingInstallments = Installment::where('status', 'pending')
            ->whereBetween('due_date', [$today, $tenDaysLater])
            ->get();

        $count = 0;
        foreach ($upcomingInstallments as $installment) {
            $daysLeft = Carbon::parse($installment->due_date)->diffInDays($today);

            // منع تكرار الإشعار لنفس القسط
            $alreadyNotified = Notification::where('client_id', $installment->client_id)
                ->where('message', 'like', "%القسط رقم {$installment->installment_number}%")
                ->where('created_at', '>=', Carbon::today())
                ->exists();

            if (!$alreadyNotified) {
                Notification::create([
                    'client_id' => $installment->client_id,
                    'title' => 'تذكير بموعد القسط',
                    'message' => "القسط رقم {$installment->installment_number} بقيمة {$installment->amount} ج.م يستحق بعد {$daysLeft} أيام",
                    'type' => 'installment',
                    'is_read' => false,
                ]);
                $count++;
            }
        }

        $this->info("تم إرسال {$count} إشعار تذكير بالأقساط.");
    }
}