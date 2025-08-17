<?php

namespace App\Filament\Resources\ProviderCredentialResource\Pages;

use App\Filament\Resources\ProviderCredentialResource;
use Filament\Resources\Pages\CreateRecord;
use Illuminate\Support\Facades\Crypt;

class CreateProviderCredential extends CreateRecord
{
    protected static string $resource = ProviderCredentialResource::class;

    protected function mutateFormDataBeforeCreate(array $data): array
    {
        // If admin supplied a secret (write-only), encrypt it into secret_encrypted
        $secret = $this->form->getState()['secret'] ?? null;
        if (! empty($secret)) {
            $data['secret_encrypted'] = Crypt::encryptString($secret);
        }

        unset($data['secret']); // make sure we never try to persist this

        return $data;
    }
}
