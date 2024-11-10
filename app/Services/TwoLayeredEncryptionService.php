<?php

namespace App\Services;

use Illuminate\Support\Facades\Crypt;
use App\Services\TwofishEncryptionService;
use Illuminate\Support\Facades\Log;

class TwoLayeredEncryptionService
{
    protected $twofishService;

    public function __construct(TwofishEncryptionService $twofishService)
    {
        $this->twofishService = $twofishService;
    }

    public function encrypt($data)
    {
        // First layer: Laravel's Crypt for raw data
        $encryptedWithLaravel = Crypt::encrypt($data);

        // Second layer: Twofish encryption
        $encryptedWithTwofish = $this->twofishService->encrypt($encryptedWithLaravel);

        // Base64 encode to ensure safe storage
        return base64_encode($encryptedWithTwofish);
    }

    public function decrypt($data)
    {
        // Decode the base64 encoded data
        $decodedData = base64_decode($data);

        // First layer: Twofish decryption
        $decryptedWithTwofish = $this->twofishService->decrypt($decodedData);

        // Second layer: Laravel's Crypt decryption
        return Crypt::decrypt($decryptedWithTwofish);
    }
}