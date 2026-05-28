protected function commands(): void
{
    $this->load(__DIR__.'/Commands');

    require base_path('routes/console.php');
}

protected function schedule(Schedule $schedule)
{
    $schedule->command('notifications:installments')->daily();
}