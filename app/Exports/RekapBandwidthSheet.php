<?php

namespace App\Exports;

use App\Models\LogActivity;
use App\Models\Opd;
use Carbon\Carbon;
use Maatwebsite\Excel\Concerns\FromCollection;
use Maatwebsite\Excel\Concerns\ShouldAutoSize;
use Maatwebsite\Excel\Concerns\WithHeadings;
use Maatwebsite\Excel\Concerns\WithStyles;
use Maatwebsite\Excel\Concerns\WithTitle;
use PhpOffice\PhpSpreadsheet\Style\Alignment;
use PhpOffice\PhpSpreadsheet\Style\Border;
use PhpOffice\PhpSpreadsheet\Style\Fill;
use PhpOffice\PhpSpreadsheet\Worksheet\Worksheet;
use Illuminate\Support\Collection;

class RekapBandwidthSheet implements
    FromCollection,
    WithTitle,
    WithHeadings,
    ShouldAutoSize,
    WithStyles
{
    /**
     * @param string $period  'daily' | 'weekly' | 'monthly' | 'yearly'
     * @param int    $no      Nomor urut sheet (untuk kolom NO)
     */
    public function __construct(
        protected string $period,
        protected int $startNo = 1
    ) {}

    // -------------------------------------------------------------------------
    // Judul sheet
    // -------------------------------------------------------------------------

    public function title(): string
    {
        return match ($this->period) {
            'daily'   => 'Harian',
            'weekly'  => 'Mingguan',
            'monthly' => 'Bulanan',
            'yearly'  => 'Tahunan',
            default   => 'Data',
        };
    }

    // -------------------------------------------------------------------------
    // Header kolom (baris pertama tabel)
    // -------------------------------------------------------------------------

    public function headings(): array
    {
        $periodLabel = match ($this->period) {
            'daily'   => 'Harian (24 Jam Terakhir)',
            'weekly'  => 'Mingguan (7 Hari Terakhir)',
            'monthly' => 'Bulanan (30 Hari Terakhir)',
            'yearly'  => 'Tahunan (12 Bulan Terakhir)',
        };

        // Baris 1: judul periode (merge dilakukan di styles)
        // Baris 2: header kolom
        return [
            // Baris 1 — judul (diisi via styles/merge, baris ini placeholder)
            ['REKAP PEMAKAIAN BANDWIDTH INTERNET — ' . strtoupper($periodLabel), '', '', '', '', '', '', ''],
            // Baris 2 — kolom
            ['NO', 'PERANGKAT DAERAH', 'Max In', 'Avg In', 'Current In', 'Max Out', 'Avg Out', 'Current Out'],
        ];
    }

    // -------------------------------------------------------------------------
    // Data baris
    // -------------------------------------------------------------------------

    public function collection(): Collection
    {
        $from = $this->getPeriodStart();

        // Ambil semua OPD
        $opds = Opd::orderBy('nama_opd')->get();

        // Satu query aggregate per periode untuk semua OPD (menghindari N+1)
        $aggregates = LogActivity::query()
            ->where('timestamp', '>=', $from)
            ->selectRaw('
                opd_id,
                MAX(in_bps)  AS max_in,
                AVG(in_bps)  AS avg_in,
                MAX(out_bps) AS max_out,
                AVG(out_bps) AS avg_out
            ')
            ->groupBy('opd_id')
            ->get()
            ->keyBy('opd_id');

        // "Current" = data terbaru per OPD (tidak dibatasi periode)
        $latestIds = LogActivity::query()
            ->selectRaw('MAX(id) AS id, opd_id')
            ->groupBy('opd_id')
            ->pluck('id');

        $latests = LogActivity::query()
            ->whereIn('id', $latestIds)
            ->get()
            ->keyBy('opd_id');

        $rows = collect();
        $no   = $this->startNo;

        foreach ($opds as $opd) {
            $agg    = $aggregates->get($opd->id);
            $latest = $latests->get($opd->id);

            $rows->push([
                $no++,
                $opd->nama_opd,
                $this->formatBps((float) ($agg->max_in  ?? 0)),
                $this->formatBps((float) ($agg->avg_in  ?? 0)),
                $this->formatBps((float) ($latest->in_bps  ?? 0)),
                $this->formatBps((float) ($agg->max_out ?? 0)),
                $this->formatBps((float) ($agg->avg_out ?? 0)),
                $this->formatBps((float) ($latest->out_bps ?? 0)),
            ]);
        }

        return $rows;
    }

    // -------------------------------------------------------------------------
    // Styling sheet (header warna, border, merge judul)
    // -------------------------------------------------------------------------

    public function styles(Worksheet $sheet): void
    {
        $lastRow  = $sheet->getHighestRow();
        $lastCol  = 'H';

        // --- Merge baris judul (baris 1) ---
        $sheet->mergeCells("A1:{$lastCol}1");
        $sheet->getStyle('A1')->applyFromArray([
            'font'      => ['bold' => true, 'size' => 12, 'color' => ['argb' => 'FFFFFFFF']],
            'fill'      => ['fillType' => Fill::FILL_SOLID, 'startColor' => ['argb' => 'FF1E3A5F']],
            'alignment' => ['horizontal' => Alignment::HORIZONTAL_CENTER, 'vertical' => Alignment::VERTICAL_CENTER],
        ]);
        $sheet->getRowDimension(1)->setRowHeight(22);

        // --- Header kolom (baris 2) ---
        $sheet->getStyle("A2:{$lastCol}2")->applyFromArray([
            'font'      => ['bold' => true, 'color' => ['argb' => 'FFFFFFFF']],
            'fill'      => ['fillType' => Fill::FILL_SOLID, 'startColor' => ['argb' => 'FF2563EB']],
            'alignment' => ['horizontal' => Alignment::HORIZONTAL_CENTER],
        ]);

        // --- Kolom Max/Avg/Current — warna berbeda untuk In (biru muda) & Out (hijau muda) ---
        // In  → C, D, E  (baris header)
        $sheet->getStyle('C2:E2')->getFont()->getColor()->setARGB('FFFFFFFF');
        $sheet->getStyle('C2:E2')->getFill()->setFillType(Fill::FILL_SOLID)
            ->getStartColor()->setARGB('FF1D4ED8'); // biru gelap

        // Out → F, G, H  (baris header)
        $sheet->getStyle('F2:H2')->getFont()->getColor()->setARGB('FFFFFFFF');
        $sheet->getStyle('F2:H2')->getFill()->setFillType(Fill::FILL_SOLID)
            ->getStartColor()->setARGB('FF15803D'); // hijau gelap

        // --- Stripe zebra untuk data ---
        for ($row = 3; $row <= $lastRow; $row++) {
            $color = ($row % 2 === 0) ? 'FFF0F4FF' : 'FFFFFFFF';
            $sheet->getStyle("A{$row}:{$lastCol}{$row}")->getFill()
                ->setFillType(Fill::FILL_SOLID)
                ->getStartColor()->setARGB($color);

            // Rata tengah kolom NO
            $sheet->getStyle("A{$row}")->getAlignment()
                ->setHorizontal(Alignment::HORIZONTAL_CENTER);

            // Rata tengah kolom nilai
            $sheet->getStyle("C{$row}:{$lastCol}{$row}")->getAlignment()
                ->setHorizontal(Alignment::HORIZONTAL_CENTER);
        }

        // --- Border seluruh tabel (baris 2 ke bawah) ---
        $sheet->getStyle("A2:{$lastCol}{$lastRow}")->applyFromArray([
            'borders' => [
                'allBorders' => [
                    'borderStyle' => Border::BORDER_THIN,
                    'color'       => ['argb' => 'FFD1D5DB'],
                ],
            ],
        ]);

        // --- Freeze header ---
        $sheet->freezePane('A3');
    }

    // -------------------------------------------------------------------------
    // Helper
    // -------------------------------------------------------------------------

    protected function getPeriodStart(): Carbon
    {
        return match ($this->period) {
            'daily'   => now()->subDay(),
            'weekly'  => now()->subWeek(),
            'monthly' => now()->subMonth(),
            'yearly'  => now()->subYear(),
            default   => now()->subDay(),
        };
    }

    protected function formatBps(float $bps): string
    {
        if ($bps >= 1_000_000) {
            return number_format($bps / 1_000_000, 2) . ' Mbps';
        }

        if ($bps >= 1_000) {
            return number_format($bps / 1_000, 2) . ' Kbps';
        }

        return number_format($bps, 0) . ' bps';
    }
}
