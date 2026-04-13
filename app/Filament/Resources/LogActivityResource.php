<?php

namespace App\Filament\Resources;

use App\Filament\Resources\LogActivityResource\Pages;
// use App\Filament\Resources\LogActivityResource\RelationManagers;
use App\Models\LogActivity;
// use Filament\Forms;
use Filament\Forms\Form;
use Filament\Resources\Resource;
use Filament\Tables;
use Filament\Tables\Table;
use Illuminate\Database\Eloquent\Builder;
// use Illuminate\Database\Eloquent\SoftDeletingScope;

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
            ->schema([
                //
                // Forms\Components\Select::make('opd_id')
                //     ->label('OPD')
                //     ->relationship('opd', 'nama_opd')
                //     ->searchable()
                //     ->required(),

                // Forms\Components\TextInput::make('in_bps')
                //     ->label('Inbound (bps)')
                //     ->numeric()
                //     ->required(),

                // Forms\Components\TextInput::make('out_bps')
                //     ->label('Outbound (bps)')
                //     ->numeric()
                //     ->required(),

                // Forms\Components\DateTimePicker::make('timestamp')
                //     ->label('Waktu Pencatatan')
                //     ->required(),
            ]);
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
                // ->formatStateUsing(
                //     fn($state) =>
                //     number_format($state / 1_000_000, 2) . ' Mbps'
                // )
                // ->sortable(),

                Tables\Columns\TextColumn::make('out_mbps')
                    ->label('Outbound')
                    ->badge()
                    ->color('success')
                    ->sortable(query: fn(Builder $query, string $direction) => $query->orderBy('out_bps', $direction)),
                // ->formatStateUsing(
                //     fn($state) =>
                //     number_format($state / 1_000_000, 2) . ' Mbps'
                // )
                // ->sortable(),

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
            ->actions([
                // Tables\Actions\EditAction::make(),
                Tables\Actions\Action::make('detail')
                    ->label('Detail')
                    ->icon('heroicon-o-eye')
                    // ->modalHeading('Detail Pemakaian Bandwidth')
                    ->color('primary')
                    ->modalHeading(fn($record) => 'Detail Bandwidth - ' . $record->opd->nama_opd)
                    ->modalWidth('4xl')
                    ->modalSubmitAction(false)
                    ->modalCancelActionLabel('Tutup')
                    // ->modalContent(
                    //     fn($record) =>
                    //     view('filament.log-activity.detail', [
                    //         'opd' => $record->opd,
                    //     ])
                    // ),
                    ->modalContent(function ($record) {
                        $opd = $record->opd;

                        // $stats = [
                        //     'max_in' => $opd->logActivities()->max('in_bps') ?? 0,
                        //     'avg_in' => $opd->logActivities()->avg('in_bps') ?? 0,
                        //     'current_in' => optional(
                        //         $opd->logActivities()->latest('timestamp')->first()
                        //     )->in_bps ?? 0,

                        //     'max_out' => $opd->logActivities()->max('out_bps') ?? 0,
                        //     'avg_out' => $opd->logActivities()->avg('out_bps') ?? 0,
                        //     'current_out' => optional(
                        //         $opd->logActivities()->latest('timestamp')->first()
                        //     )->out_bps ?? 0,
                        // ];

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
                // Tables\Actions\BulkActionGroup::make([
                //     Tables\Actions\DeleteBulkAction::make(),
                // ]),
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
            // 'create' => Pages\CreateLogActivity::route('/create'),
            // 'edit' => Pages\EditLogActivity::route('/{record}/edit'),
        ];
    }
}
