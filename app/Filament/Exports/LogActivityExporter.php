<?php

namespace App\Filament\Exports;

use App\Models\LogActivity;
use Filament\Actions\Exports\ExportColumn;
use Filament\Actions\Exports\Exporter;
use Filament\Actions\Exports\Models\Export;

class LogActivityExporter extends Exporter
{
    protected static ?string $model = LogActivity::class;

    public static function getColumns(): array
    {
        return [
            //
            ExportColumn::make('opd.nama_opd')
                ->label('Nama OPD'),

            ExportColumn::make('opd.nama_perangkat')
                ->label('Perangkat'),

            ExportColumn::make('opd.ip_address')
                ->label('IP Address'),

            ExportColumn::make('in_bps')
                ->label('Inbound (bps)'),

            // Kolom formatted Mbps (accessor dari model)
            ExportColumn::make('in_mbps')
                ->label('Inbound (Mbps)')
                ->state(fn(LogActivity $record): string => $record->in_mbps),

            ExportColumn::make('out_bps')
                ->label('Outbound (bps)'),

            ExportColumn::make('out_mbps')
                ->label('Outbound (Mbps)')
                ->state(fn(LogActivity $record): string => $record->out_mbps),

            ExportColumn::make('timestamp')
                ->label('Waktu')
                ->state(fn(LogActivity $record): string => $record->timestamp?->format('d/m/Y H:i:s') ?? ''),
        ];
    }

    public static function getCompletedNotificationBody(Export $export): string
    {
        // $body = 'Your log activity export has completed and ' . number_format($export->successful_rows) . ' ' . str('row')->plural($export->successful_rows) . ' exported.';

        // if ($failedRowsCount = $export->getFailedRowsCount()) {
        //     $body .= ' ' . number_format($failedRowsCount) . ' ' . str('row')->plural($failedRowsCount) . ' failed to export.';
        // }

        // return $body;

        $body = 'Export Log Aktivitas selesai: '
            . number_format($export->successful_rows)
            . ' baris berhasil diekspor.';

        if ($failedRowsCount = $export->getFailedRowsCount()) {
            $body .= ' ' . number_format($failedRowsCount) . ' baris gagal.';
        }

        return $body;
    }
}
