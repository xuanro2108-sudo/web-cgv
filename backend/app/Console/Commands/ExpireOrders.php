<?php

namespace App\Console\Commands;

use App\Models\DonHang;
use Illuminate\Console\Command;
use Illuminate\Support\Facades\DB;

class ExpireOrders extends Command
{
    protected $signature = 'orders:expire';

    protected $description = 'Release seats and pending payments for expired orders';

    public function handle(): int
    {
        $count = 0;
        DonHang::where('trangThai', 'CHO_THANH_TOAN')
            ->where(function ($q) {
                $q->where('hetHanLuc', '<=', now())->orWhere(function ($q) {
                    $q->whereNull('hetHanLuc')->where('ngayDat', '<=', now()->subMinutes(10));
                });
            })->chunkById(100, function ($orders) use (&$count) {
                foreach ($orders as $candidate) {
                    DB::transaction(function () use ($candidate, &$count) {
                        $order = DonHang::whereKey($candidate->maDonHang)->lockForUpdate()->first();
                        if ($order && $order->trangThai === 'CHO_THANH_TOAN' && $order->daHetHan()) {
                            $order->huy('HET_HAN');
                            $count++;
                        }
                    }, 3);
                }
            }, 'maDonHang');
        $this->info("Expired {$count} orders.");

        return self::SUCCESS;
    }
}
