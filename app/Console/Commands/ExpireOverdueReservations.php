<?php

namespace App\Console\Commands;

use App\Services\ReservationService;
use Illuminate\Console\Command;

class ExpireOverdueReservations extends Command
{
    protected $signature = 'reservations:expire';

    protected $description = 'Lepas reservasi stok yang telah melewati batas waktu (3 jam ongkir atau 12 jam bayar)';

    public function handle(ReservationService $reservationService): int
    {
        $expiredCount = $reservationService->expireOverdue();
        $this->info("Berhasil memproses {$expiredCount} reservasi kadaluwarsa.");

        return Command::SUCCESS;
    }
}
