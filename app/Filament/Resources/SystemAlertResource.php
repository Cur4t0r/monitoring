<?php

namespace App\Filament\Resources;

use App\Filament\Resources\SystemAlertResource\Pages;
use App\Models\SystemAlert;
use Filament\Forms;
use Filament\Forms\Form;
use Filament\Notifications\Notification;
use Filament\Resources\Resource;
use Filament\Tables;
use Filament\Tables\Table;

class SystemAlertResource extends Resource
{
    protected static ?string $model = SystemAlert::class;

    protected static ?string $navigationIcon   = 'heroicon-o-bell-alert';

    protected static ?string $navigationLabel  = 'System Alerts';

    protected static ?string $modelLabel       = 'Alert';

    protected static ?string $pluralModelLabel = 'System Alerts';

    protected static ?int $navigationSort   = 3;

    /** Badge jumlah alert aktif di sidebar navigasi */
    public static function getNavigationBadge(): ?string
    {
        $count = SystemAlert::where('status', 'active')->count();
        return $count > 0 ? (string) $count : null;
    }

    public static function getNavigationBadgeColor(): string
    {
        return SystemAlert::where('severity', 'critical')
            ->where('status', 'active')
            ->exists() ? 'danger' : 'warning';
    }

    public static function form(Form $form): Form
    {
        return $form
            ->schema([
                //
                Forms\Components\Section::make()->schema([
                    Forms\Components\Select::make('severity')
                        ->label('Tingkat Keparahan')
                        ->options([
                            'critical' => 'Critical',
                            'warning'  => 'Warning',
                            'info'     => 'Info',
                        ])
                        ->required(),

                    Forms\Components\Select::make('status')
                        ->label('Status')
                        ->options([
                            'active'       => 'Aktif',
                            'acknowledged' => 'Diakui',
                            'resolved'     => 'Selesai',
                        ])
                        ->default('active')
                        ->required(),

                    Forms\Components\Select::make('opd_id')
                        ->label('OPD Terkait')
                        ->relationship('opd', 'nama_opd')
                        ->searchable()
                        ->preload()
                        ->nullable(),

                    Forms\Components\DateTimePicker::make('occurred_at')
                        ->label('Waktu Kejadian')
                        ->required()
                        ->default(now()),

                    Forms\Components\Textarea::make('message')
                        ->label('Pesan Alert')
                        ->required()
                        ->rows(2)
                        ->columnSpanFull(),
                ])->columns(2),
            ]);
    }

    public static function table(Table $table): Table
    {
        return $table
            ->columns([
                Tables\Columns\BadgeColumn::make('severity')
                    ->label('Level')
                    ->formatStateUsing(fn(string $state): string => match ($state) {
                        'critical' => 'Critical',
                        'warning'  => 'Warning',
                        'info'     => 'Info',
                        default    => $state,
                    })
                    ->colors([
                        'danger'  => 'critical',
                        'warning' => 'warning',
                        'info'    => 'info',
                    ])
                    ->icons([
                        'heroicon-o-x-circle'             => 'critical',
                        'heroicon-o-exclamation-triangle' => 'warning',
                        'heroicon-o-information-circle'   => 'info',
                    ]),

                Tables\Columns\TextColumn::make('occurred_at')
                    ->label('Waktu')
                    ->dateTime('d M Y H:i:s')
                    ->sortable(),

                Tables\Columns\TextColumn::make('message')
                    ->label('Pesan')
                    ->searchable()
                    ->wrap(),

                Tables\Columns\TextColumn::make('opd.nama_opd')
                    ->label('OPD')
                    ->default('—')
                    ->sortable(),

                Tables\Columns\BadgeColumn::make('status')
                    ->label('Status')
                    ->formatStateUsing(fn(string $state): string => match ($state) {
                        'active'       => 'Aktif',
                        'acknowledged' => 'Diakui',
                        'resolved'     => 'Selesai',
                        default        => $state,
                    })
                    ->colors([
                        'danger'  => 'active',
                        'warning' => 'acknowledged',
                        'success' => 'resolved',
                    ]),

                Tables\Columns\TextColumn::make('acknowledgedBy.name')
                    ->label('Diakui Oleh')
                    ->default('—')
                    ->toggleable(),
            ])
            ->defaultSort('occurred_at', 'desc')
            ->filters([
                Tables\Filters\SelectFilter::make('severity')
                    ->label('Level')
                    ->options([
                        'critical' => 'Critical',
                        'warning'  => 'Warning',
                        'info'     => 'Info',
                    ]),

                Tables\Filters\SelectFilter::make('status')
                    ->label('Status')
                    ->options([
                        'active'       => 'Aktif',
                        'acknowledged' => 'Diakui',
                        'resolved'     => 'Selesai',
                    ]),

                Tables\Filters\SelectFilter::make('opd_id')
                    ->label('OPD')
                    ->relationship('opd', 'nama_opd')
                    ->searchable()
                    ->preload(),
            ])
            ->actions([
                Tables\Actions\Action::make('acknowledge')
                    ->label('Akui')
                    ->icon('heroicon-o-check')
                    ->color('warning')
                    ->visible(fn(SystemAlert $record): bool => $record->status === 'active')
                    ->requiresConfirmation()
                    ->modalHeading('Akui Alert Ini?')
                    ->modalDescription(fn(SystemAlert $record): string => $record->message)
                    ->action(function (SystemAlert $record): void {
                        $record->update([
                            'status'          => 'acknowledged',
                            'acknowledged_at' => now(),
                            'acknowledged_by' => auth()->id(),
                        ]);

                        Notification::make()->title('Alert diakui')->success()->send();
                    }),

                Tables\Actions\Action::make('resolve')
                    ->label('Selesaikan')
                    ->icon('heroicon-o-check-circle')
                    ->color('success')
                    ->visible(fn(SystemAlert $record): bool => $record->status !== 'resolved')
                    ->requiresConfirmation()
                    ->modalHeading('Tandai Alert Selesai?')
                    ->action(function (SystemAlert $record): void {
                        $record->update(['status' => 'resolved']);

                        Notification::make()->title('Alert diselesaikan')->success()->send();
                    }),

                Tables\Actions\DeleteAction::make(),
            ])
            ->bulkActions([
                Tables\Actions\BulkActionGroup::make([
                    Tables\Actions\BulkAction::make('bulk_acknowledge')
                        ->label('Akui Semua')
                        ->icon('heroicon-o-check')
                        ->color('warning')
                        ->requiresConfirmation()
                        ->action(function ($records): void {
                            $records->each(fn(SystemAlert $r) => $r->update([
                                'status'          => 'acknowledged',
                                'acknowledged_at' => now(),
                                'acknowledged_by' => auth()->id(),
                            ]));

                            Notification::make()->title('Alert diakui')->success()->send();
                        }),

                    Tables\Actions\DeleteBulkAction::make(),
                ]),
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
            'index' => Pages\ListSystemAlerts::route('/'),
            'create' => Pages\CreateSystemAlert::route('/create'),
            // 'edit' => Pages\EditSystemAlert::route('/{record}/edit'),
        ];
    }
}
