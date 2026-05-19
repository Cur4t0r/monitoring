<?php

namespace App\Filament\Resources;

use App\Filament\Resources\MaintenanceResource\Pages;
use App\Models\Maintenance;
use Filament\Forms;
use Filament\Forms\Form;
use Filament\Resources\Resource;
use Filament\Tables;
use Filament\Tables\Table;

class MaintenanceResource extends Resource
{
    protected static ?string $model = Maintenance::class;

    protected static ?string $navigationIcon   = 'heroicon-o-wrench-screwdriver';

    protected static ?string $navigationLabel  = 'Maintenance';

    protected static ?string $modelLabel       = 'Maintenance';

    protected static ?string $pluralModelLabel = 'Maintenance';

    protected static ?int    $navigationSort   = 2;


    public static function form(Form $form): Form
    {
        return $form->schema([
            Forms\Components\Section::make('Informasi Maintenance')
                ->schema([
                    Forms\Components\TextInput::make('title')
                        ->label('Judul')
                        ->required()
                        ->maxLength(255)
                        ->columnSpanFull(),

                    Forms\Components\Textarea::make('description')
                        ->label('Deskripsi')
                        ->rows(3)
                        ->columnSpanFull(),

                    Forms\Components\DateTimePicker::make('scheduled_at')
                        ->label('Jadwal Mulai')
                        ->required()
                        ->seconds(false),

                    Forms\Components\TextInput::make('duration_minutes')
                        ->label('Durasi (menit)')
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
                ])
                ->columns(3),

            Forms\Components\Section::make('OPD yang Terdampak')
                ->description('Pilih satu atau lebih OPD yang akan terdampak pada maintenance ini')
                ->schema([
                    Forms\Components\MultiSelect::make('opds')
                        ->label('')
                        ->relationship('opds', 'nama_opd')
                        ->searchable()
                        ->preload()
                        ->options(function () {
                            return \App\Models\Opd::orderBy('nama_opd')
                                ->pluck('nama_opd', 'id');
                        })
                        ->columnSpanFull(),
                ]),
        ]);
    }

    public static function table(Table $table): Table
    {
        return $table
            ->columns([
                Tables\Columns\TextColumn::make('title')
                    ->label('Judul')
                    ->searchable()
                    ->sortable()
                    ->wrap(),

                Tables\Columns\TextColumn::make('scheduled_at')
                    ->label('Jadwal')
                    ->dateTime('d M Y H:i')
                    ->sortable(),

                Tables\Columns\TextColumn::make('formatted_duration')
                    ->label('Durasi'),

                Tables\Columns\TextColumn::make('opds_count')
                    ->label('OPD Terdampak')
                    ->counts('opds')
                    ->badge()
                    ->color('warning'),

                Tables\Columns\BadgeColumn::make('status')
                    ->label('Status')
                    ->formatStateUsing(fn(string $state): string => match ($state) {
                        'planned'     => 'Direncanakan',
                        'in_progress' => 'Berjalan',
                        'completed'   => 'Selesai',
                        'cancelled'   => 'Dibatalkan',
                        default       => $state,
                    })
                    ->colors([
                        'info'    => 'planned',
                        'warning' => 'in_progress',
                        'success' => 'completed',
                        'danger'  => 'cancelled',
                    ]),
            ])
            ->defaultSort('scheduled_at', 'desc')
            ->filters([
                Tables\Filters\SelectFilter::make('status')
                    ->label('Status')
                    ->options([
                        'planned'     => 'Direncanakan',
                        'in_progress' => 'Berjalan',
                        'completed'   => 'Selesai',
                        'cancelled'   => 'Dibatalkan',
                    ]),
            ])
            ->actions([
                Tables\Actions\EditAction::make(),
                Tables\Actions\DeleteAction::make(),
            ])
            ->bulkActions([
                Tables\Actions\BulkActionGroup::make([
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
            'index' => Pages\ListMaintenances::route('/'),
            'create' => Pages\CreateMaintenance::route('/create'),
            'edit' => Pages\EditMaintenance::route('/{record}/edit'),
            // 'calendar' => Pages\CalendarMaintenance::route('/calendar'),
        ];
    }
}
