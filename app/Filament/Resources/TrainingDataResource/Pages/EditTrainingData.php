<?php

namespace App\Filament\Resources\TrainingDataResource\Pages;

use App\Filament\Resources\TrainingDataResource;
use Filament\Actions;
use Filament\Resources\Pages\EditRecord;

class EditTrainingData extends EditRecord
{
    protected static string $resource = TrainingDataResource::class;

    protected function getHeaderActions(): array
    {
        return [
            Actions\DeleteAction::make(),
        ];
    }
}
