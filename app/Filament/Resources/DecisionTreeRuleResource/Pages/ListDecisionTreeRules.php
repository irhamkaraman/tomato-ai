<?php

namespace App\Filament\Resources\DecisionTreeRuleResource\Pages;

use App\Filament\Resources\DecisionTreeRuleResource;
use Filament\Actions;
use Filament\Resources\Pages\ListRecords;

class ListDecisionTreeRules extends ListRecords
{
    protected static string $resource = DecisionTreeRuleResource::class;

    protected function getHeaderActions(): array
    {
        return [
            Actions\CreateAction::make(),
        ];
    }
}
