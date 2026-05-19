<?php

namespace App\Filament\Widgets;

// use Filament\Widgets\Widget;
use App\Models\Maintenance;
use Filament\Forms;
use Filament\Forms\Form;
use Illuminate\Database\Eloquent\Model;
use Saade\FilamentFullCalendar\Actions\CreateAction;
use Saade\FilamentFullCalendar\Actions\DeleteAction;
use Saade\FilamentFullCalendar\Actions\EditAction;
use Saade\FilamentFullCalendar\Data\EventData;
use Saade\FilamentFullCalendar\Widgets\FullCalendarWidget;

class MaintenanceCalendarWidget extends FullCalendarWidget
{
    public Model | string | null $model = Maintenance::class;

    // =========================================================================
    // Events
    // =========================================================================

    public function fetchEvents(array $fetchInfo): array
    {
        return Maintenance::with('opds:id,nama_opd')
            ->whereBetween('scheduled_at', [$fetchInfo['start'], $fetchInfo['end']])
            ->get()
            ->map(
                fn(Maintenance $m) => EventData::make()
                    ->id($m->id)
                    ->title($m->title)
                    ->start($m->scheduled_at)
                    ->end($m->scheduled_at->addMinutes($m->duration_minutes))
                    ->backgroundColor($this->statusColor($m->status))
                    ->borderColor($this->statusColor($m->status))
            )
            ->toArray();
    }

    // =========================================================================
    // Form schema — dipakai CreateAction dan EditAction
    // =========================================================================

    public function getFormSchema(): array
    {
        return [
            Forms\Components\TextInput::make('title')
                ->label('Judul')
                ->required()
                ->maxLength(255)
                ->columnSpanFull(),

            Forms\Components\Textarea::make('description')
                ->label('Deskripsi')
                ->rows(2)
                ->columnSpanFull(),

            Forms\Components\DateTimePicker::make('scheduled_at')
                ->label('Jadwal Mulai')
                ->required()
                ->seconds(false),

            Forms\Components\TextInput::make('duration_minutes')
                ->label('Durasi')
                ->required()
                ->numeric()
                ->minValue(1)
                ->suffix('menit'),

            Forms\Components\Select::make('status')
                ->label('Status')
                ->options([
                    'planned'     => 'Direncanakan',
                    'in_progress' => 'Berjalan',
                    'completed'   => 'Selesai',
                    'cancelled'   => 'Dibatalkan',
                ])
                ->default('planned')
                ->required(),

            Forms\Components\MultiSelect::make('opds')
                ->label('OPD Terdampak')
                ->relationship('opds', 'nama_opd')
                ->searchable()
                ->preload()
                ->columnSpanFull(),
        ];
    }

    // =========================================================================
    // Tombol tambah di header kalender
    // =========================================================================

    protected function headerActions(): array
    {
        return [
            CreateAction::make()
                ->label('Tambah Maintenance')
                ->mountUsing(function (Form $form, array $arguments) {
                    $form->fill([
                        // Pre-fill tanggal dari tanggal yang diklik di kalender
                        'scheduled_at' => $arguments['start'] ?? now(),
                    ]);
                }),
        ];
    }

    // =========================================================================
    // Aksi saat klik event (edit & delete)
    // =========================================================================

    protected function modalActions(): array
    {
        return [
            EditAction::make()
                ->mountUsing(function (Maintenance $record, Form $form) {
                    $form->fill([
                        'title'            => $record->title,
                        'description'      => $record->description,
                        'scheduled_at'     => $record->scheduled_at,
                        'duration_minutes' => $record->duration_minutes,
                        'status'           => $record->status,
                        'opds'             => $record->opds->pluck('id')->toArray(),
                    ]);
                }),
            DeleteAction::make(),
        ];
    }

    public function resolveEventRecord(array $data): Maintenance
    {
        return Maintenance::with('opds')->find($data['id']);
    }

    // =========================================================================
    // Warna per status — single source of truth
    // =========================================================================

    private function statusColor(string $status): string
    {
        return match ($status) {
            'planned'     => '#3b82f6',
            'in_progress' => '#f59e0b',
            'completed'   => '#22c55e',
            'cancelled'   => '#6b7280',
            default       => '#3b82f6',
        };
    }
}
