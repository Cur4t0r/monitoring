<?php

namespace App\Filament\Resources;

use App\Filament\Resources\OpdResource\Pages;
// use App\Filament\Resources\OpdResource\RelationManagers;
use App\Models\Opd;
use Filament\Forms;
use Filament\Forms\Form;
use Filament\Resources\Resource;
use Filament\Tables;
use Filament\Tables\Table;
// use Illuminate\Database\Eloquent\Builder;
// use Illuminate\Database\Eloquent\SoftDeletingScope;

class OpdResource extends Resource
{
    protected static ?string $model = Opd::class;

    protected static ?string $navigationLabel = 'OPD';

    protected static ?string $modelLabel = 'OPD';

    protected static ?string $pluralModelLabel = 'OPD';

    protected static ?string $navigationIcon = 'heroicon-o-building-office-2';

    public static function form(Form $form): Form
    {
        return $form
            ->schema([
                //
                Forms\Components\TextInput::make('nama_opd')
                    ->label('Nama OPD')
                    ->required(),

                Forms\Components\TextInput::make('nama_perangkat')
                    ->label('Nama Perangkat')
                    ->required(),

                Forms\Components\TextInput::make('ip_address')
                    ->label('IP Address')
                    ->required()
                    ->maxLength(45),

                Forms\Components\TextInput::make('interface')
                    ->label('Interface')
                    ->placeholder('eth0 / wlan0'),

                Forms\Components\Textarea::make('keterangan')
                    ->label('Keterangan')

            ]);
    }

    public static function table(Table $table): Table
    {
        return $table
            ->columns([
                //
                Tables\Columns\TextColumn::make('nama_opd')
                    ->label('OPD')
                    ->searchable()
                    ->sortable(),

                Tables\Columns\TextColumn::make('nama_perangkat')
                    ->label('Perangkat'),

                Tables\Columns\TextColumn::make('ip_address')
                    ->label('IP Address'),

                Tables\Columns\TextColumn::make('interface')
                    ->label('Interface'),

                Tables\Columns\TextColumn::make('created_at')
                    ->label('Dibuat')
                    ->dateTime('d M Y'),
            ])
            ->defaultSort('nama_opd')
            ->filters([
                //
            ])
            ->actions([
                Tables\Actions\EditAction::make(),
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
            'index' => Pages\ListOpds::route('/'),
            'create' => Pages\CreateOpd::route('/create'),
            'edit' => Pages\EditOpd::route('/{record}/edit'),
        ];
    }
}
