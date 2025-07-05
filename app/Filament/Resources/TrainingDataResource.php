<?php

namespace App\Filament\Resources;

use App\Filament\Resources\TrainingDataResource\Pages;
use App\Filament\Resources\TrainingDataResource\RelationManagers;
use App\Models\TrainingData;
use Filament\Forms;
use Filament\Forms\Form;
use Filament\Resources\Resource;
use Filament\Tables;
use Filament\Tables\Table;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\SoftDeletingScope;

class TrainingDataResource extends Resource
{
    protected static ?string $model = TrainingData::class;

    protected static ?string $navigationIcon = 'heroicon-o-beaker';

    protected static ?string $navigationLabel = 'Data Training';

    protected static ?string $modelLabel = 'Data Training';

    protected static ?string $pluralModelLabel = 'Data Training';

    protected static ?string $navigationGroup = 'Sistem Pakar';

    protected static ?int $navigationSort = 1;

    public static function form(Form $form): Form
    {
        return $form
            ->schema([
                Forms\Components\Section::make('Data RGB')
                    ->description('Masukkan nilai RGB untuk data training')
                    ->schema([
                        Forms\Components\Grid::make(3)
                            ->schema([
                                Forms\Components\TextInput::make('red_value')
                                    ->label('Nilai Merah (R)')
                                    ->required()
                                    ->numeric()
                                    ->minValue(0)
                                    ->maxValue(255)
                                    ->placeholder('0-255')
                                    ->helperText('Nilai RGB merah (0-255)'),
                                
                                Forms\Components\TextInput::make('green_value')
                                    ->label('Nilai Hijau (G)')
                                    ->required()
                                    ->numeric()
                                    ->minValue(0)
                                    ->maxValue(255)
                                    ->placeholder('0-255')
                                    ->helperText('Nilai RGB hijau (0-255)'),
                                
                                Forms\Components\TextInput::make('blue_value')
                                    ->label('Nilai Biru (B)')
                                    ->required()
                                    ->numeric()
                                    ->minValue(0)
                                    ->maxValue(255)
                                    ->placeholder('0-255')
                                    ->helperText('Nilai RGB biru (0-255)'),
                            ]),
                    ]),
                
                Forms\Components\Section::make('Klasifikasi')
                    ->description('Tentukan kelas kematangan tomat')
                    ->schema([
                        Forms\Components\Select::make('maturity_class')
                            ->label('Kelas Kematangan')
                            ->required()
                            ->options([
                                'mentah' => 'Mentah',
                                'setengah_matang' => 'Setengah Matang',
                                'matang' => 'Matang',
                                'busuk' => 'Busuk',
                            ])
                            ->placeholder('Pilih kelas kematangan')
                            ->helperText('Pilih kelas kematangan berdasarkan kondisi tomat'),
                        
                        Forms\Components\Textarea::make('description')
                            ->label('Deskripsi')
                            ->placeholder('Deskripsi tambahan tentang sampel ini...')
                            ->rows(3)
                            ->columnSpanFull(),
                        
                        Forms\Components\Toggle::make('is_active')
                            ->label('Aktif untuk Training')
                            ->helperText('Data ini akan digunakan dalam proses training algoritma')
                            ->default(true),
                    ]),
            ]);
    }

    public static function table(Table $table): Table
    {
        return $table
            ->columns([
                Tables\Columns\TextColumn::make('id')
                    ->label('ID')
                    ->sortable(),
                
                Tables\Columns\ColorColumn::make('rgb_preview')
                    ->label('Preview Warna')
                    ->getStateUsing(function ($record) {
                        return sprintf('#%02x%02x%02x', $record->red_value, $record->green_value, $record->blue_value);
                    }),
                
                Tables\Columns\TextColumn::make('red_value')
                    ->label('R')
                    ->sortable()
                    ->alignCenter(),
                
                Tables\Columns\TextColumn::make('green_value')
                    ->label('G')
                    ->sortable()
                    ->alignCenter(),
                
                Tables\Columns\TextColumn::make('blue_value')
                    ->label('B')
                    ->sortable()
                    ->alignCenter(),
                
                Tables\Columns\BadgeColumn::make('maturity_class')
                    ->label('Kelas Kematangan')
                    ->colors([
                        'success' => 'matang',
                        'warning' => 'setengah_matang',
                        'primary' => 'mentah',
                        'danger' => 'busuk',
                    ])
                    ->formatStateUsing(function ($state) {
                        return match($state) {
                            'mentah' => 'Mentah',
                            'setengah_matang' => 'Setengah Matang',
                            'matang' => 'Matang',
                            'busuk' => 'Busuk',
                            default => $state
                        };
                    }),
                
                Tables\Columns\IconColumn::make('is_active')
                    ->label('Aktif')
                    ->boolean()
                    ->alignCenter(),
                
                Tables\Columns\TextColumn::make('created_at')
                    ->label('Dibuat')
                    ->dateTime('d/m/Y H:i')
                    ->sortable()
                    ->toggleable(isToggledHiddenByDefault: true),
            ])
            ->filters([
                Tables\Filters\SelectFilter::make('maturity_class')
                    ->label('Kelas Kematangan')
                    ->options([
                        'mentah' => 'Mentah',
                        'setengah_matang' => 'Setengah Matang',
                        'matang' => 'Matang',
                        'busuk' => 'Busuk',
                    ]),
                
                Tables\Filters\TernaryFilter::make('is_active')
                    ->label('Status Aktif')
                    ->placeholder('Semua data')
                    ->trueLabel('Hanya data aktif')
                    ->falseLabel('Hanya data non-aktif'),
            ])
            ->actions([
                Tables\Actions\ViewAction::make(),
                Tables\Actions\EditAction::make(),
                Tables\Actions\DeleteAction::make(),
            ])
            ->bulkActions([
                Tables\Actions\BulkActionGroup::make([
                    Tables\Actions\DeleteBulkAction::make(),
                    Tables\Actions\BulkAction::make('activate')
                        ->label('Aktifkan')
                        ->icon('heroicon-o-check-circle')
                        ->action(function ($records) {
                            $records->each(function ($record) {
                                $record->update(['is_active' => true]);
                            });
                        })
                        ->deselectRecordsAfterCompletion(),
                    
                    Tables\Actions\BulkAction::make('deactivate')
                        ->label('Non-aktifkan')
                        ->icon('heroicon-o-x-circle')
                        ->action(function ($records) {
                            $records->each(function ($record) {
                                $record->update(['is_active' => false]);
                            });
                        })
                        ->deselectRecordsAfterCompletion(),
                ]),
            ])
            ->defaultSort('created_at', 'desc');
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
            'index' => Pages\ListTrainingData::route('/'),
            'create' => Pages\CreateTrainingData::route('/create'),
            'edit' => Pages\EditTrainingData::route('/{record}/edit'),
        ];
    }
}
