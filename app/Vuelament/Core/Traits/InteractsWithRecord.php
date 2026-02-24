<?php

namespace App\Vuelament\Core\Traits;

use Illuminate\Database\Eloquent\Builder;

/**
 * Trait untuk mendeteksi Page yang bereaksi terhadap spesifik Model Record
 * Biasanya diletakkan di custom page seperti DetailPage atau ReportPage.
 *
 * Page Controller akan auto-inject argument record ($record) ketika trait ini ditemukan,
 * sehingga method `getData` akan bisa menangkap `$record` secara pas.
 */
trait InteractsWithRecord
{
    // Cukup marker bahwa class ini akan bereaksi dengan {record} Route param, 
    // karena core framework Vuelament 100% menggunakan properti static, record akan dikirim ke getData() via args
}
