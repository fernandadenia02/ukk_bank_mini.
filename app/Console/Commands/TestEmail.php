<?php

namespace App\Console\Commands;

use Illuminate\Console\Command;
use Illuminate\Support\Facades\Mail;
use Throwable;

class TestEmail extends Command
{
    protected $signature = 'email:test {tujuan}';

    protected $description = 'Menguji pengiriman email SMTP';

    public function handle(): int
    {
        $tujuan = $this->argument('tujuan');

        try {
            Mail::raw(
                'Ini adalah email percobaan dari aplikasi Bank Mini Laravel.',
                function ($message) use ($tujuan) {
                    $message->to($tujuan)
                        ->subject('Tes Email Bank Mini');
                }
            );

            $this->info('Permintaan pengiriman email berhasil.');
            $this->info('Periksa kotak masuk dan folder Spam.');

            return self::SUCCESS;
        } catch (Throwable $e) {
            $this->error('Pengiriman email gagal.');
            $this->line($e->getMessage());

            return self::FAILURE;
        }
    }
}
