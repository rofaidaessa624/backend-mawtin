<?php

namespace App\Console\Commands;

use Illuminate\Console\Command;
use App\Models\Installment;
use App\Models\Notification;
use Carbon\Carbon;

class SendInstallmentNotifications extends Command
{
    protected $signature = 'notifications:installments';

    protected $description = 'Send installment due notifications';

    public function handle()
    {
        $installments = Installment::where('status', 'pending')->get();

        foreach ($installments as $installment) {

            $daysLeft = Carbon::now()->diffInDays(
                Carbon::parse($installment->due_date),
                false
            );

            // قبل القسط بـ 10 أيام
            if ($daysLeft <= 10 && $daysLeft >= 0) {

                // منع التكرار
                $exists = Notification::where('client_id', $installment->client_id)
                    ->where('title', 'تنبيه موعد قسط')
                    ->whereDate('created_at', today())
                    ->exists();

                if (!$exists) {

                    Notification::create([
                        'client_id' => $installment->client_id,
                        'title' => 'تنبيه موعد قسط',
                        'message' => "متبقي {$daysLeft} يوم على موعد القسط رقم {$installment->installment_number}",
                        'type' => 'installment',
                        'is_read' => false,
                    ]);

                    $this->info("Notification sent to client {$installment->client_id}");
                }
            }
        }

        return Command::SUCCESS;
    }
}