<?php

use Illuminate\Foundation\Inspiring;
use Illuminate\Support\Facades\Artisan;
use Illuminate\Support\Facades\Schedule;
use App\Models\Member;
use Carbon\Carbon;

Artisan::command('inspire', function () {
    $this->comment(Inspiring::quote());
})->purpose('Display an inspiring quote');

// Otomatis tandai member yang sudah lewat tanggal_expired jadi 'sudah expired'
// Jalan tiap hari jam 00:05. Alternatif: panggil manual `php artisan members:expire`
Schedule::call(function () {
    Member::whereNotNull('tanggal_expired')
        ->where('status', 'lunas')
        ->where('tanggal_expired', '<', Carbon::now())
        ->update(['status' => 'sudah expired']);
})->dailyAt('00:05')->name('members-auto-expire');

Artisan::command('members:expire', function () {
    $count = Member::whereNotNull('tanggal_expired')
        ->where('status', 'lunas')
        ->where('tanggal_expired', '<', Carbon::now())
        ->update(['status' => 'sudah expired']);
    $this->info("Expired updated: $count member(s).");
})->purpose('Tandai member yang sudah lewat tanggal_expired menjadi sudah expired');
