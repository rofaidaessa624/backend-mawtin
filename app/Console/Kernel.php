protected function commands(): void
{
    $this->load(__DIR__.'/Commands');

    require base_path('routes/console.php');
}

protected function schedule(Schedule $schedule): void
{
    // تشغيل الأمر يومياً الساعة 8 صباحاً
    $schedule->command('installments:check-upcoming')->dailyAt('08:00');
}