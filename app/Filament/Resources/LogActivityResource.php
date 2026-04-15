<?php

namespace App\Filament\Resources;

use App\Exports\RekapBandwidthExport;
// use App\Filament\Exports\LogActivityExporter;
use App\Filament\Resources\LogActivityResource\Pages;
use App\Models\LogActivity;
use Filament\Forms\Components\CheckboxList;
// use Filament\Actions\Exports\Enums\ExportFormat;
use Filament\Forms\Form;
use Filament\Notifications\Notification;
use Filament\Resources\Resource;
use Filament\Tables;
use Filament\Tables\Table;
use Illuminate\Database\Eloquent\Builder;
use Maatwebsite\Excel\Facades\Excel;

class LogActivityResource extends Resource
{
    protected static ?string $model = LogActivity::class;

    protected static ?string $navigationIcon = 'heroicon-o-chart-bar';

    protected static ?string $navigationLabel = 'Log Aktivitas';

    protected static ?string $modelLabel = 'Log Aktivitas';

    protected static ?string $pluralModelLabel = 'Log Aktivitas';

    public static function form(Form $form): Form
    {
        return $form
            ->schema([]);
    }

    public static function canCreate(): bool
    {
        return false;
    }

    public static function canEdit($record): bool
    {
        return false;
    }

    public static function canDelete($record): bool
    {
        return false;
    }

    public static function getEloquentQuery(): Builder
    {
        return parent::getEloquentQuery()
            ->with('opd:id,nama_opd');
    }

    public static function table(Table $table): Table
    {
        return $table
            ->striped()
            ->paginated([10, 25, 50, 100])
            ->defaultPaginationPageOption(25)
            ->columns([
                //
                Tables\Columns\TextColumn::make('opd.nama_opd')
                    ->label('OPD')
                    ->sortable()
                    ->searchable(),

                Tables\Columns\TextColumn::make('in_mbps')
                    ->label('Inbound')
                    ->badge()
                    ->color('info')
                    ->sortable(query: fn(Builder $query, string $direction) => $query->orderBy('in_bps', $direction)),

                Tables\Columns\TextColumn::make('out_mbps')
                    ->label('Outbound')
                    ->badge()
                    ->color('success')
                    ->sortable(query: fn(Builder $query, string $direction) => $query->orderBy('out_bps', $direction)),

                Tables\Columns\TextColumn::make('timestamp')
                    ->label('Waktu')
                    ->dateTime('d M Y H:i')
                    ->sortable(),
            ])
            ->defaultSort('timestamp', 'desc')
            ->filters([
                //
                Tables\Filters\SelectFilter::make('opd_id')
                    ->label('Filter OPD')
                    ->relationship('opd', 'nama_opd')
                    ->searchable()
                    ->preload(),
            ])
            ->headerActions([
                // ---------------------------------------------------------------
                // Export ke Excel / CSV
                // Catatan setup:
                //   1. php artisan vendor:publish --tag="filament-actions-migrations"
                //   2. php artisan migrate
                //   3. Pastikan QUEUE_CONNECTION di .env diset (bisa 'sync' untuk dev)
                // ---------------------------------------------------------------
                Tables\Actions\ExportAction::make('rekapBandwidth')
                    ->label('Rekap Bandwidth')
                    ->icon('heroicon-o-arrow-down-tray')
                    ->color('success')
                    ->modalHeading('Export Rekap Pemakaian Bandwidth')
                    ->modalDescription('Pilih periode yang ingin diekspor. Setiap periode akan menjadi sheet terpisah dalam satu file Excel.')
                    ->modalWidth('md')
                    ->modalSubmitActionLabel('Download Excel')
                    ->form([
                        CheckboxList::make('periods')
                            ->label('Periode')
                            ->options([
                                'daily'   => 'Harian (24 Jam Terakhir)',
                                'weekly'  => 'Mingguan (7 Hari Terakhir)',
                                'monthly' => 'Bulanan (30 Hari Terakhir)',
                                'yearly'  => 'Tahunan (12 Bulan Terakhir)',
                            ])
                            ->default(['daily', 'weekly', 'monthly', 'yearly'])
                            ->columns(1)
                            ->required()
                            ->rules(['min:1'])
                            ->validationMessages([
                                'min' => 'Pilih minimal satu periode.',
                            ]),
                    ])
                    ->action(function (array $data): mixed {
                        $periods = $data['periods'] ?? [];

                        if (empty($periods)) {
                            Notification::make()
                                ->title('Pilih minimal satu periode')
                                ->warning()
                                ->send();

                            return null;
                        }

                        // Urutkan sesuai urutan logis (bukan urutan klik user)
                        $order   = ['daily', 'weekly', 'monthly', 'yearly'];
                        $sorted  = array_values(
                            array_filter($order, fn($p) => in_array($p, $periods))
                        );

                        // Nama file menyertakan tanggal dan periode yang dipilih
                        $labels   = [
                            'daily'   => 'Harian',
                            'weekly'  => 'Mingguan',
                            'monthly' => 'Bulanan',
                            'yearly'  => 'Tahunan',
                        ];
                        $suffix   = implode('-', array_map(fn($p) => $labels[$p], $sorted));
                        $filename = 'Rekap-Bandwidth-' . $suffix . '-' . now()->format('Ymd') . '.xlsx';

                        return Excel::download(
                            new RekapBandwidthExport($sorted),
                            $filename
                        );
                    }),
            ])
            ->actions([
                Tables\Actions\Action::make('detail')
                    ->label('Detail')
                    ->icon('heroicon-o-eye')
                    ->color('primary')
                    ->modalHeading(fn($record) => 'Detail Bandwidth - ' . $record->opd->nama_opd)
                    ->modalWidth('4xl')
                    ->modalSubmitAction(false)
                    ->modalCancelActionLabel('Tutup')
                    ->modalContent(function ($record) {
                        $opd = $record->opd;

                        $aggregate = $opd->logActivities()
                            ->selectRaw('
                                MAX(in_bps) as max_in,
                                AVG(in_bps) as avg_in,
                                MAX(out_bps) as max_out,
                                AVG(out_bps) as avg_out')
                            ->first();

                        $latest = $opd->logActivities()
                            ->latest('timestamp')
                            ->first();

                        $stats = [
                            'max_in' => $aggregate->max_in ?? 0,
                            'avg_in' => $aggregate->avg_in ?? 0,
                            'current_in' => $latest->in_bps ?? 0,

                            'max_out' => $aggregate->max_out ?? 0,
                            'avg_out' => $aggregate->avg_out ?? 0,
                            'current_out' => $latest->out_bps ?? 0,
                        ];

                        return view('filament.log-activity.detail', [
                            'opd' => $opd,
                            'record' => $record,
                            'stats' => $stats,
                        ]);
                    }),

            ])
            ->bulkActions([
                //
            ]);
    }

    public static function getRelations(): array
    {
        return [
            //
        ];
    }

    public static function getPages(): array
    {
        return [
            'index' => Pages\ListLogActivities::route('/'),
        ];
    }
}
