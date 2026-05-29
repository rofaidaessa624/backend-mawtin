<?php

namespace App\Console\Commands;

use App\Models\Client;
use App\Services\FirebaseNotificationService;

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
$tenDaysLater = Carbon::today()->addDays(30);
        // جلب الأقساط اللي هتستحق خلال 10 أيام
        $upcomingInstallments = Installment::where('status', 'pending')
            ->whereBetween('due_date', [$today, $tenDaysLater])
            ->get();

        $count = 0;
        foreach ($upcomingInstallments as $installment) {
  $daysLeft = $today->diffInDays(
    Carbon::parse($installment->due_date),
    false
);     


            // منع تكرار الإشعار لنفس القسط
            $alreadyNotified = Notification::where('client_id', $installment->client_id)
                ->where('message', 'like', "%القسط رقم {$installment->installment_number}%")
                ->where('created_at', '>=', Carbon::today())
                ->exists();



                $this->info("Installment Found: {$installment->installment_number}");
$this->info("Due Date: {$installment->due_date}");
$this->info("Days Left: {$daysLeft}");

     if (!$alreadyNotified) {

    Notification::create([
        'client_id' => $installment->client_id,
        'title' => 'تذكير بموعد القسط',
        'message' => "القسط رقم {$installment->installment_number} بقيمة {$installment->amount} ج.م يستحق بعد {$daysLeft} أيام",
        'type' => 'installment',
        'is_read' => false,
    ]);

    $client = Client::find($installment->client_id);

    if ($client) {

        app(FirebaseNotificationService::class)->sendToClient(
            $client,
            'تذكير بموعد القسط',
            "القسط رقم {$installment->installment_number} يستحق بعد {$daysLeft} أيام"
        );
    }

    $count++;
}
        }

        $this->info("تم إرسال {$count} إشعار تذكير بالأقساط.");
    }
}