<?php

namespace App\Filament\Resources;

use App\Filament\Resources\ClassificationResource\Pages;
use App\Filament\Resources\ClassificationResource\RelationManagers;
use App\Models\Classification;
use Filament\Forms;
use Filament\Forms\Form;
use Filament\Resources\Resource;
use Filament\Tables;
use Filament\Tables\Table;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\SoftDeletingScope;
use Filament\Forms\Components\TextInput;
use Filament\Forms\Components\Select;
use Filament\Forms\Components\Textarea;
use Filament\Forms\Components\Toggle;
use Filament\Forms\Components\Section;
use Filament\Forms\Components\Grid;
use Filament\Tables\Columns\TextColumn;
use Filament\Tables\Columns\BadgeColumn;
use Filament\Tables\Columns\ToggleColumn;
use Filament\Tables\Filters\SelectFilter;
use Filament\Tables\Filters\TernaryFilter;
use Filament\Support\Enums\FontWeight;
use Filament\Tables\Columns\ColorColumn;

class ClassificationResource extends Resource
{
    protected static ?string $model = Classification::class;

    protected static ?string $navigationIcon = 'heroicon-o-cpu-chip';
    
    protected static ?string $navigationLabel = 'Klasifikasi AI';
    
    protected static ?string $navigationGroup = 'Klasifikasi AI';
    
    protected static ?string $modelLabel = 'Klasifikasi';
    
    protected static ?string $pluralModelLabel = 'Data Klasifikasi';
    
    protected static ?int $navigationSort = 1;

    public static function form(Form $form): Form
    {
        return $form
            ->schema([
                Section::make('Data Sensor RGB')
                    ->description('Input nilai RGB dan Clear dari sensor TCS34725')
                    ->schema([
                        Grid::make(4)
                            ->schema([
                                TextInput::make('red_value')
                                    ->label('Nilai Merah (R)')
                                    ->numeric()
                                    ->minValue(0)
                                    ->maxValue(255)
                                    ->required()
                                    ->helperText('Nilai RGB Merah (0-255)'),
                                    
                                TextInput::make('green_value')
                                    ->label('Nilai Hijau (G)')
                                    ->numeric()
                                    ->minValue(0)
                                    ->maxValue(255)
                                    ->required()
                                    ->helperText('Nilai RGB Hijau (0-255)'),
                                    
                                TextInput::make('blue_value')
                                    ->label('Nilai Biru (B)')
                                    ->numeric()
                                    ->minValue(0)
                                    ->maxValue(255)
                                    ->required()
                                    ->helperText('Nilai RGB Biru (0-255)'),
                                    
                                TextInput::make('clear_value')
                                    ->label('Nilai Clear')
                                    ->numeric()
                                    ->minValue(0)
                                    ->required()
                                    ->helperText('Nilai Clear dari sensor TCS34725'),
                            ]),
                    ])->columns(1),
                    
                Section::make('Status Kematangan')
                    ->description('Pilih status kematangan aktual dan prediksi AI')
                    ->schema([
                        Grid::make(2)
                            ->schema([
                                Select::make('actual_status')
                                    ->label('Status Aktual')
                                    ->options(Classification::getStatusOptions())
                                    ->required()
                                    ->helperText('Status kematangan tomat yang sebenarnya')
                                    ->reactive()
                                    ->afterStateUpdated(function ($state, callable $set, callable $get) {
                                        $predicted = $get('predicted_status');
                                        if ($state && $predicted) {
                                            $result = $state === $predicted ? 'Benar' : 'Salah';
                                            $set('classification_result_preview', $result);
                                        }
                                    }),
                                    
                                Select::make('predicted_status')
                                    ->label('Prediksi AI')
                                    ->options(Classification::getStatusOptions())
                                    ->required()
                                    ->helperText('Status yang diprediksi oleh sistem AI')
                                    ->reactive()
                                    ->afterStateUpdated(function ($state, callable $set, callable $get) {
                                        $actual = $get('actual_status');
                                        if ($state && $actual) {
                                            $result = $state === $actual ? 'Benar' : 'Salah';
                                            $set('classification_result_preview', $result);
                                        }
                                    }),
                            ]),
                    ])->columns(1),
                    
                Section::make('Hasil Klasifikasi')
                    ->description('Hasil evaluasi prediksi AI')
                    ->schema([
                        Grid::make(2)
                            ->schema([
                                Forms\Components\Placeholder::make('classification_result_preview')
                                    ->label('Hasil Klasifikasi (Otomatis)')
                                    ->content(function (callable $get) {
                                        $actualStatus = $get('actual_status');
                                        $predictedStatus = $get('predicted_status');
                                        
                                        if ($actualStatus && $predictedStatus) {
                                            $result = $actualStatus === $predictedStatus ? 'Benar' : 'Salah';
                                            $color = $result === 'Benar' ? 'success' : 'danger';
                                            return "<span class='inline-flex items-center px-2 py-1 rounded-full text-xs font-medium bg-{$color}-100 text-{$color}-800'>{$result}</span>";
                                        }
                                        
                                        return '<span class="text-gray-500">Pilih status aktual dan prediksi terlebih dahulu</span>';
                                    })
                                    ->extraAttributes(['class' => 'text-sm']),
                                    
                                Toggle::make('is_verified')
                                    ->label('Data Terverifikasi')
                                    ->helperText('Tandai jika data sudah diverifikasi')
                                    ->default(false),
                            ]),
                    ])->columns(1),
                    
                Section::make('Informasi Tambahan')
                    ->description('Catatan dan informasi perangkat')
                    ->schema([
                        Grid::make(2)
                            ->schema([
                                Textarea::make('notes')
                                    ->label('Catatan')
                                    ->rows(3)
                                    ->helperText('Catatan tambahan tentang klasifikasi ini'),
                                    
                                TextInput::make('device_id')
                                    ->label('ID Perangkat')
                                    ->helperText('ID perangkat yang melakukan pengukuran'),
                            ]),
                    ])->columns(1),
            ]);
    }

    public static function table(Table $table): Table
    {
        return $table
            ->columns([
                TextColumn::make('id')
                    ->label('ID')
                    ->sortable()
                    ->searchable(),
                    
                TextColumn::make('rgb_string')
                    ->label('Nilai RGB')
                    ->badge()
                    ->color(fn ($record) => 'gray')
                    ->formatStateUsing(fn ($record) => "R:{$record->red_value} G:{$record->green_value} B:{$record->blue_value}")
                    ->searchable(['red_value', 'green_value', 'blue_value']),
                    
                TextColumn::make('clear_value')
                    ->label('Clear')
                    ->sortable()
                    ->searchable(),
                    
                TextColumn::make('actual_status')
                    ->label('Status Aktual')
                    ->badge()
                    ->color(fn (string $state): string => match ($state) {
                        'Mentah' => 'success',
                        'Setengah Matang' => 'warning', 
                        'Matang' => 'danger',
                        'Busuk' => 'gray',
                        default => 'gray',
                    })
                    ->sortable()
                    ->searchable(),
                    
                TextColumn::make('predicted_status')
                    ->label('Prediksi AI')
                    ->badge()
                    ->color(fn (string $state): string => match ($state) {
                        'Mentah' => 'success',
                        'Setengah Matang' => 'warning',
                        'Matang' => 'danger', 
                        'Busuk' => 'gray',
                        default => 'gray',
                    })
                    ->sortable()
                    ->searchable(),
                    
                TextColumn::make('classification_result')
                    ->label('Hasil')
                    ->badge()
                    ->color(fn (string $state): string => match ($state) {
                        'Benar' => 'success',
                        'Salah' => 'danger',
                        default => 'gray',
                    })
                    ->sortable()
                    ->searchable(),
                    
                ToggleColumn::make('is_verified')
                    ->label('Terverifikasi')
                    ->sortable(),
                    
                TextColumn::make('device_id')
                    ->label('Device ID')
                    ->searchable()
                    ->toggleable(isToggledHiddenByDefault: true),
                    
                TextColumn::make('created_at')
                    ->label('Dibuat')
                    ->dateTime('d/m/Y H:i')
                    ->sortable()
                    ->toggleable(isToggledHiddenByDefault: true),
            ])
            ->filters([
                SelectFilter::make('actual_status')
                    ->label('Status Aktual')
                    ->options(Classification::getStatusOptions()),
                    
                SelectFilter::make('predicted_status')
                    ->label('Prediksi AI')
                    ->options(Classification::getStatusOptions()),
                    
                SelectFilter::make('classification_result')
                    ->label('Hasil Klasifikasi')
                    ->options(Classification::getClassificationResultOptions()),
                    
                TernaryFilter::make('is_verified')
                    ->label('Status Verifikasi')
                    ->placeholder('Semua Data')
                    ->trueLabel('Terverifikasi')
                    ->falseLabel('Belum Terverifikasi'),
            ])
            ->actions([
                Tables\Actions\ViewAction::make(),
                Tables\Actions\EditAction::make(),
                Tables\Actions\DeleteAction::make(),
            ])
            ->bulkActions([
                Tables\Actions\BulkActionGroup::make([
                    Tables\Actions\DeleteBulkAction::make(),
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
    
    public static function getWidgets(): array
    {
        return [
            ClassificationResource\Widgets\ClassificationStatsWidget::class,
        ];
    }

    public static function getPages(): array
    {
        return [
            'index' => Pages\ListClassifications::route('/'),
            'create' => Pages\CreateClassification::route('/create'),
            'edit' => Pages\EditClassification::route('/{record}/edit'),
        ];
    }
}
