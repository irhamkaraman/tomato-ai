<?php

namespace App\Filament\Resources;

use App\Filament\Resources\DecisionTreeRuleResource\Pages;
use App\Filament\Resources\DecisionTreeRuleResource\RelationManagers;
use App\Models\DecisionTreeRule;
use Filament\Forms;
use Filament\Forms\Form;
use Filament\Resources\Resource;
use Filament\Tables;
use Filament\Tables\Table;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\SoftDeletingScope;
use Illuminate\Database\Eloquent\Collection;

class DecisionTreeRuleResource extends Resource
{
    protected static ?string $model = DecisionTreeRule::class;

    protected static ?string $navigationIcon = 'heroicon-o-adjustments-horizontal';
    
    protected static ?string $navigationLabel = 'Aturan Decision Tree';
    
    protected static ?string $modelLabel = 'Aturan Decision Tree';
    
    protected static ?string $pluralModelLabel = 'Aturan Decision Tree';
    
    protected static ?string $navigationGroup = 'Sistem Pakar';
    
    protected static ?int $navigationSort = 2;

    public static function form(Form $form): Form
    {
        return $form
            ->schema([
                Forms\Components\Section::make('Informasi Aturan')
                    ->schema([
                        Forms\Components\TextInput::make('rule_name')
                            ->label('Nama Aturan')
                            ->required()
                            ->maxLength(255)
                            ->placeholder('Contoh: Evaluasi Warna Merah')
                            ->helperText('Nama yang mendeskripsikan aturan ini'),
                        
                        Forms\Components\Select::make('node_type')
                            ->label('Tipe Node')
                            ->required()
                            ->options([
                                'condition' => 'Kondisi (Condition)',
                                'leaf' => 'Hasil Akhir (Leaf)'
                            ])
                            ->reactive()
                            ->placeholder('Pilih tipe node')
                            ->helperText('Kondisi untuk evaluasi, Hasil Akhir untuk klasifikasi'),
                        
                        Forms\Components\TextInput::make('node_order')
                            ->label('Urutan Evaluasi')
                            ->required()
                            ->numeric()
                            ->default(0)
                            ->minValue(0)
                            ->helperText('Urutan evaluasi node (0 = pertama)'),
                        
                        Forms\Components\Textarea::make('description')
                            ->label('Deskripsi')
                            ->rows(3)
                            ->placeholder('Jelaskan tujuan dan cara kerja aturan ini')
                            ->helperText('Deskripsi detail tentang aturan ini'),
                        
                        Forms\Components\Toggle::make('is_active')
                            ->label('Status Aktif')
                            ->default(true)
                            ->helperText('Aktifkan atau nonaktifkan aturan ini')
                    ])
                    ->columns(2),
                
                Forms\Components\Section::make('Konfigurasi Kondisi')
                    ->schema([
                        Forms\Components\Select::make('condition_field')
                            ->label('Field Evaluasi')
                            ->options([
                                'red' => 'Nilai Merah (Red)',
                                'green' => 'Nilai Hijau (Green)',
                                'blue' => 'Nilai Biru (Blue)',
                                'ratio_red_green' => 'Rasio Merah/Hijau',
                                'ratio_red_blue' => 'Rasio Merah/Biru',
                                'ratio_green_blue' => 'Rasio Hijau/Biru'
                            ])
                            ->placeholder('Pilih field yang akan dievaluasi')
                            ->helperText('Field data RGB yang akan dievaluasi'),
                        
                        Forms\Components\Select::make('condition_operator')
                            ->label('Operator Kondisi')
                            ->options([
                                '>' => 'Lebih besar dari (>)',
                                '<' => 'Lebih kecil dari (<)',
                                '>=' => 'Lebih besar atau sama dengan (>=)',
                                '<=' => 'Lebih kecil atau sama dengan (<=)',
                                '==' => 'Sama dengan (==)'
                            ])
                            ->placeholder('Pilih operator perbandingan')
                            ->helperText('Operator untuk membandingkan nilai'),
                        
                        Forms\Components\TextInput::make('condition_value')
                            ->label('Nilai Threshold')
                            ->numeric()
                            ->step(0.01)
                            ->placeholder('Contoh: 150 atau 1.5')
                            ->helperText('Nilai batas untuk perbandingan')
                    ])
                    ->visible(fn (Forms\Get $get) => $get('node_type') === 'condition')
                    ->columns(3),
                
                Forms\Components\Section::make('Aksi dan Hasil')
                    ->schema([
                        Forms\Components\Select::make('true_action')
                            ->label('Aksi Jika Kondisi Benar')
                            ->options([
                                'next_node' => 'Lanjut ke Node Berikutnya',
                                'classify' => 'Klasifikasi Langsung'
                            ])
                            ->placeholder('Pilih aksi jika kondisi terpenuhi')
                            ->reactive(),
                        
                        Forms\Components\TextInput::make('true_result')
                            ->label('Hasil Jika Benar')
                            ->placeholder('ID node berikutnya atau kelas kematangan')
                            ->helperText('Masukkan ID node atau kelas (mentah, setengah_matang, matang, busuk)'),
                        
                        Forms\Components\Select::make('false_action')
                            ->label('Aksi Jika Kondisi Salah')
                            ->options([
                                'next_node' => 'Lanjut ke Node Berikutnya',
                                'classify' => 'Klasifikasi Langsung'
                            ])
                            ->placeholder('Pilih aksi jika kondisi tidak terpenuhi')
                            ->reactive(),
                        
                        Forms\Components\TextInput::make('false_result')
                            ->label('Hasil Jika Salah')
                            ->placeholder('ID node berikutnya atau kelas kematangan')
                            ->helperText('Masukkan ID node atau kelas (mentah, setengah_matang, matang, busuk)')
                    ])
                    ->visible(fn (Forms\Get $get) => $get('node_type') === 'condition')
                    ->columns(2),
                
                Forms\Components\Section::make('Klasifikasi Hasil')
                    ->schema([
                        Forms\Components\Select::make('maturity_class')
                            ->label('Kelas Kematangan')
                            ->required()
                            ->options([
                                'mentah' => 'Mentah',
                                'setengah_matang' => 'Setengah Matang',
                                'matang' => 'Matang',
                                'busuk' => 'Busuk'
                            ])
                            ->placeholder('Pilih kelas kematangan')
                            ->helperText('Hasil klasifikasi untuk leaf node ini')
                    ])
                    ->visible(fn (Forms\Get $get) => $get('node_type') === 'leaf')
            ]);
    }

    public static function table(Table $table): Table
    {
        return $table
            ->columns([
                Tables\Columns\TextColumn::make('id')
                    ->label('ID')
                    ->sortable()
                    ->searchable(),
                
                Tables\Columns\TextColumn::make('rule_name')
                    ->label('Nama Aturan')
                    ->sortable()
                    ->searchable()
                    ->limit(30)
                    ->tooltip(function (Tables\Columns\TextColumn $column): ?string {
                        $state = $column->getState();
                        return strlen($state) > 30 ? $state : null;
                    }),
                
                Tables\Columns\BadgeColumn::make('node_type')
                    ->label('Tipe Node')
                    ->colors([
                        'primary' => 'condition',
                        'success' => 'leaf'
                    ])
                    ->formatStateUsing(fn (string $state): string => match ($state) {
                        'condition' => 'Kondisi',
                        'leaf' => 'Hasil Akhir',
                        default => $state
                    }),
                
                Tables\Columns\TextColumn::make('node_order')
                    ->label('Urutan')
                    ->sortable()
                    ->alignCenter(),
                
                Tables\Columns\TextColumn::make('condition_field')
                    ->label('Field')
                    ->formatStateUsing(fn (?string $state): string => match ($state) {
                        'red' => 'Merah',
                        'green' => 'Hijau',
                        'blue' => 'Biru',
                        'ratio_red_green' => 'R/G',
                        'ratio_red_blue' => 'R/B',
                        'ratio_green_blue' => 'G/B',
                        default => $state ?? '-'
                    })
                    ->visible(fn ($record) => $record && $record->node_type === 'condition'),
                
                Tables\Columns\TextColumn::make('condition_operator')
                    ->label('Operator')
                    ->alignCenter()
                    ->visible(fn ($record) => $record && $record->node_type === 'condition'),
                
                Tables\Columns\TextColumn::make('condition_value')
                    ->label('Nilai')
                    ->alignCenter()
                    ->visible(fn ($record) => $record && $record->node_type === 'condition'),
                
                Tables\Columns\BadgeColumn::make('maturity_class')
                    ->label('Kelas')
                    ->colors([
                        'danger' => 'mentah',
                        'warning' => 'setengah_matang',
                        'success' => 'matang',
                        'secondary' => 'busuk'
                    ])
                    ->formatStateUsing(fn (?string $state): string => match ($state) {
                        'mentah' => 'Mentah',
                        'setengah_matang' => 'Setengah Matang',
                        'matang' => 'Matang',
                        'busuk' => 'Busuk',
                        default => $state ?? '-'
                    })
                    ->visible(fn ($record) => $record && $record->node_type === 'leaf'),
                
                Tables\Columns\IconColumn::make('is_active')
                    ->label('Status')
                    ->boolean()
                    ->trueIcon('heroicon-o-check-circle')
                    ->falseIcon('heroicon-o-x-circle')
                    ->trueColor('success')
                    ->falseColor('danger'),
                
                Tables\Columns\TextColumn::make('created_at')
                    ->label('Dibuat')
                    ->dateTime('d/m/Y H:i')
                    ->sortable()
                    ->toggleable(isToggledHiddenByDefault: true)
            ])
            ->filters([
                Tables\Filters\SelectFilter::make('node_type')
                    ->label('Tipe Node')
                    ->options([
                        'condition' => 'Kondisi',
                        'leaf' => 'Hasil Akhir'
                    ]),
                
                Tables\Filters\SelectFilter::make('maturity_class')
                    ->label('Kelas Kematangan')
                    ->options([
                        'mentah' => 'Mentah',
                        'setengah_matang' => 'Setengah Matang',
                        'matang' => 'Matang',
                        'busuk' => 'Busuk'
                    ]),
                
                Tables\Filters\TernaryFilter::make('is_active')
                    ->label('Status Aktif')
                    ->placeholder('Semua')
                    ->trueLabel('Aktif')
                    ->falseLabel('Tidak Aktif')
            ])
            ->actions([
                Tables\Actions\ViewAction::make(),
                Tables\Actions\EditAction::make(),
                Tables\Actions\DeleteAction::make()
            ])
            ->bulkActions([
                Tables\Actions\BulkActionGroup::make([
                    Tables\Actions\DeleteBulkAction::make(),
                    Tables\Actions\BulkAction::make('activate')
                        ->label('Aktifkan')
                        ->icon('heroicon-o-check-circle')
                        ->color('success')
                        ->action(function (Collection $records) {
                            $records->each(function ($record) {
                                $record->update(['is_active' => true]);
                            });
                        })
                        ->deselectRecordsAfterCompletion(),
                    
                    Tables\Actions\BulkAction::make('deactivate')
                        ->label('Nonaktifkan')
                        ->icon('heroicon-o-x-circle')
                        ->color('danger')
                        ->action(function (Collection $records) {
                            $records->each(function ($record) {
                                $record->update(['is_active' => false]);
                            });
                        })
                        ->deselectRecordsAfterCompletion()
                ]),
            ])
            ->defaultSort('node_order', 'asc');
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
            'index' => Pages\ListDecisionTreeRules::route('/'),
            'create' => Pages\CreateDecisionTreeRule::route('/create'),
            'edit' => Pages\EditDecisionTreeRule::route('/{record}/edit'),
        ];
    }
}
