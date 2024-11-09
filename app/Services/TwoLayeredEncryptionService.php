<?php

namespace App\Services;

use Illuminate\Support\Facades\Crypt;
use App\Services\TwofishEncryptionService;

class TwoLayeredEncryptionService
{
    protected $twofishService;

    public function __construct(TwofishEncryptionService $twofishService)
    {
        $this->twofishService = $twofishService;
    }

    public function encrypt($data)
    {
        // First layer: Laravel's Crypt
        $encryptedWithLaravel = Crypt::encryptString($data);

        // Second layer: Twofish encryption
        return $this->twofishService->encrypt($encryptedWithLaravel);
    }

    public function decrypt($data)
    {
        // First layer: Twofish decryption
        $decryptedWithTwofish = $this->twofishService->decrypt($data);

        // Second layer: Laravel's Crypt decryption
        return Crypt::decryptString($decryptedWithTwofish);
    }
}