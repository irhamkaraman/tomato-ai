<?php

namespace App\Filament\Widgets;

use App\Models\TrainingData;
use Filament\Tables\Table;
use Filament\Tables\Columns\TextColumn;
use Filament\Tables\Columns\ColorColumn;
use Filament\Widgets\TableWidget as BaseWidget;
use Illuminate\Database\Eloquent\Builder;

class TrainingDataSampleWidget extends BaseWidget
{
    protected static ?int $sort = 3;
    
    protected int | string | array $columnSpan = 'full';
    
    public function table(Table $table): Table
    {
        return $table
            ->query(
                TrainingData::query()->latest()->limit(10)
            )
            ->heading('📊 Sampel Data Training AI')
            ->description('Contoh dataset yang digunakan untuk melatih algoritma machine learning')
            ->headerActions([
                // Add any header actions if needed
            ])
            ->striped()
            ->defaultPaginationPageOption(10)
            ->columns([
                TextColumn::make('id')
                    ->label('ID')
                    ->sortable(),
                    
                ColorColumn::make('rgb_preview')
                    ->label('Preview')
                    ->getStateUsing(function ($record) {
                        return sprintf('#%02x%02x%02x', $record->red, $record->green, $record->blue);
                    })
                    ->tooltip(function ($record) {
                        return "RGB({$record->red}, {$record->green}, {$record->blue})";
                    }),
                    
                TextColumn::make('red')
                    ->label('R')
                    ->badge()
                    ->color('danger')
                    ->sortable(),
                    
                TextColumn::make('green')
                    ->label('G')
                    ->badge()
                    ->color('success')
                    ->sortable(),
                    
                TextColumn::make('blue')
                    ->label('B')
                    ->badge()
                    ->color('info')
                    ->sortable(),
                    
                TextColumn::make('maturity_level')
                    ->label('Tingkat Kematangan')
                    ->badge()
                    ->color(fn (string $state): string => match ($state) {
                        'mentah' => 'success',
                        'setengah_matang' => 'warning',
                        'matang' => 'danger',
                        'busuk' => 'gray',
                        default => 'secondary',
                    })
                    ->formatStateUsing(fn (string $state): string => ucfirst(str_replace('_', ' ', $state)))
                    ->sortable(),
                    
                TextColumn::make('confidence_score')
                    ->label('Confidence')
                    ->suffix('%')
                    ->numeric()
                    ->sortable()
                    ->color(fn ($state) => $state >= 90 ? 'success' : ($state >= 80 ? 'warning' : 'danger')),
                    
                TextColumn::make('source')
                    ->label('Sumber')
                    ->badge()
                    ->color('secondary')
                    ->formatStateUsing(fn (string $state): string => ucfirst($state)),
                    
                TextColumn::make('created_at')
                    ->label('Dibuat')
                    ->dateTime('d/m/Y H:i')
                    ->sortable()
                    ->toggleable(),
            ])
            ->defaultSort('created_at', 'desc')
            ->paginated(false)
            ->poll('30s')
            ->emptyStateHeading('Belum Ada Data Training')
            ->emptyStateDescription('Silakan tambahkan data training melalui menu Training Data')
            ->emptyStateIcon('heroicon-o-academic-cap')
            ->contentGrid([
                'md' => 1,
                'xl' => 1,
            ])
            ->extremePaginationLinks();
    }
    
    public function getTableRecordsPerPageSelectOptions(): array
    {
        return [10];
    }
}