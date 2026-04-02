<?php

namespace App\Filament\Resources\ClaimRequestResource\Pages;

use App\Filament\Resources\ClaimRequestResource;
use Filament\Actions;
use Filament\Resources\Pages\EditRecord;

class EditClaimRequest extends EditRecord
{
    protected static string $resource = ClaimRequestResource::class;

    protected function getHeaderActions(): array
    {
        return [
            Actions\DeleteAction::make(),
        ];
    }
}
