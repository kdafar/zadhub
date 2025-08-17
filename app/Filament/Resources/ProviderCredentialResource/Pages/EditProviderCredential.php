<?php

namespace App\Filament\Resources\ProviderCredentialResource\Pages;

use App\Filament\Resources\ProviderCredentialResource;
use Filament\Actions;
use Filament\Resources\Pages\EditRecord;
use Illuminate\Support\Facades\Crypt;

class EditProviderCredential extends EditRecord
{
    protected static string $resource = ProviderCredentialResource::class;

    protected function getHeaderActions(): array
    {
        return [
            Actions\ViewAction::make(),
            Actions\DeleteAction::make(),
        ];
    }

    protected function mutateFormDataBeforeSave(array $data): array
    {
        // Only update secret_encrypted if a new write-only secret was provided
        $secret = $this->form->getState()['secret'] ?? null;
        if (! empty($secret)) {
            $data['secret_encrypted'] = Crypt::encryptString($secret);
        }

        unset($data['secret']);

        return $data;
    }
}
