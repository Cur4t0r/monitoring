<?php

namespace App\Exports;

use Maatwebsite\Excel\Concerns\WithMultipleSheets;

class RekapBandwidthExport implements WithMultipleSheets
{
    /**
     * @param array $periods  Subset dari ['daily','weekly','monthly','yearly']
     *                        urutan menentukan urutan sheet di file Excel
     */
    public function __construct(protected array $periods) {}

    /**
     * Kembalikan array sheet — satu instance RekapBandwidthSheet per periode.
     * Jumlah sheet = jumlah periode yang dipilih user.
     */
    public function sheets(): array
    {
        return array_map(
            fn(string $period) => new RekapBandwidthSheet($period),
            $this->periods
        );
    }
}
