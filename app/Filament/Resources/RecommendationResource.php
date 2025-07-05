<?php

namespace App\Filament\Resources;

use App\Filament\Resources\RecommendationResource\Pages;
use App\Filament\Resources\RecommendationResource\RelationManagers;
use App\Models\Recommendation;
use Filament\Forms;
use Filament\Forms\Form;
use Filament\Resources\Resource;
use Filament\Tables;
use Filament\Tables\Table;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\SoftDeletingScope;
use Illuminate\Database\Eloquent\Collection;

class RecommendationResource extends Resource
{
    protected static ?string $model = Recommendation::class;

    protected static ?string $navigationIcon = 'heroicon-o-light-bulb';
    
    protected static ?string $navigationLabel = 'Rekomendasi';
    
    protected static ?string $modelLabel = 'Rekomendasi';
    
    protected static ?string $pluralModelLabel = 'Rekomendasi';
    
    protected static ?string $navigationGroup = 'Manajemen Sistem Pakar';
    
    protected static ?int $navigationSort = 3;

    public static function form(Form $form): Form
    {
        return $form
            ->schema([
                Forms\Components\Section::make('Informasi Rekomendasi')
                    ->schema([
                        Forms\Components\Select::make('maturity_level')
                            ->label('Tingkat Kematangan')
                            ->options(Recommendation::MATURITY_LEVELS)
                            ->required()
                            ->searchable(),
                            
                        Forms\Components\Select::make('category')
                            ->label('Kategori')
                            ->options(Recommendation::CATEGORIES)
                            ->required()
                            ->searchable(),
                            
                        Forms\Components\TextInput::make('order')
                            ->label('Urutan')
                            ->numeric()
                            ->default(0)
                            ->required()
                            ->minValue(0),
                            
                        Forms\Components\Toggle::make('is_active')
                            ->label('Aktif')
                            ->default(true)
                            ->required(),
                    ])
                    ->columns(2),
                    
                Forms\Components\Section::make('Konten Rekomendasi')
                    ->schema([
                        Forms\Components\Textarea::make('content')
                            ->label('Isi Rekomendasi')
                            ->required()
                            ->rows(4)
                            ->columnSpanFull(),
                            
                        Forms\Components\Textarea::make('description')
                            ->label('Deskripsi Tambahan')
                            ->rows(3)
                            ->columnSpanFull(),
                    ])
            ]);
    }

    public static function table(Table $table): Table
    {
        return $table
            ->columns([
                Tables\Columns\TextColumn::make('id')
                    ->label('ID')
                    ->sortable(),
                    
                Tables\Columns\TextColumn::make('maturity_level')
                    ->label('Tingkat Kematangan')
                    ->formatStateUsing(fn (string $state): string => Recommendation::MATURITY_LEVELS[$state] ?? $state)
                    ->badge()
                    ->color(fn (string $state): string => match ($state) {
                        'mentah' => 'danger',
                        'setengah_matang' => 'warning',
                        'matang' => 'success',
                        'busuk' => 'gray',
                        default => 'primary',
                    })
                    ->searchable()
                    ->sortable(),
                    
                Tables\Columns\TextColumn::make('category')
                    ->label('Kategori')
                    ->formatStateUsing(fn (string $state): string => Recommendation::CATEGORIES[$state] ?? $state)
                    ->badge()
                    ->searchable()
                    ->sortable(),
                    
                Tables\Columns\TextColumn::make('content')
                    ->label('Isi Rekomendasi')
                    ->limit(50)
                    ->searchable(),
                    
                Tables\Columns\TextColumn::make('order')
                    ->label('Urutan')
                    ->sortable(),
                    
                Tables\Columns\IconColumn::make('is_active')
                    ->label('Status')
                    ->boolean()
                    ->sortable(),
                    
                Tables\Columns\TextColumn::make('created_at')
                    ->label('Dibuat')
                    ->dateTime('d/m/Y H:i')
                    ->sortable()
                    ->toggleable(isToggledHiddenByDefault: true),
            ])
            ->filters([
                Tables\Filters\SelectFilter::make('maturity_level')
                    ->label('Tingkat Kematangan')
                    ->options(Recommendation::MATURITY_LEVELS),
                    
                Tables\Filters\SelectFilter::make('category')
                    ->label('Kategori')
                    ->options(Recommendation::CATEGORIES),
                    
                Tables\Filters\TernaryFilter::make('is_active')
                    ->label('Status Aktif')
                    ->boolean()
                    ->trueLabel('Aktif')
                    ->falseLabel('Tidak Aktif')
                    ->native(false),
            ])
            ->actions([
                Tables\Actions\EditAction::make(),
                Tables\Actions\DeleteAction::make(),
            ])
            ->bulkActions([
                Tables\Actions\BulkActionGroup::make([
                    Tables\Actions\DeleteBulkAction::make(),
                    Tables\Actions\BulkAction::make('activate')
                        ->label('Aktifkan')
                        ->icon('heroicon-o-check-circle')
                        ->action(function (Collection $records) {
                            $records->each->update(['is_active' => true]);
                        })
                        ->deselectRecordsAfterCompletion(),
                    Tables\Actions\BulkAction::make('deactivate')
                        ->label('Nonaktifkan')
                        ->icon('heroicon-o-x-circle')
                        ->action(function (Collection $records) {
                            $records->each->update(['is_active' => false]);
                        })
                        ->deselectRecordsAfterCompletion(),
                ]),
            ])
            ->defaultSort('order', 'asc');
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
            'index' => Pages\ListRecommendations::route('/'),
            'create' => Pages\CreateRecommendation::route('/create'),
            'edit' => Pages\EditRecommendation::route('/{record}/edit'),
        ];
    }
}
