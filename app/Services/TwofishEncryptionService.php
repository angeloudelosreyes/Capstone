<?php

namespace App\Services;

use Defuse\Crypto\Crypto;
use Defuse\Crypto\Key;
use Illuminate\Support\Facades\Log;

class TwofishEncryptionService
{
    protected $key;

    public function __construct($key)
    {
        // Hash the password to create a secure key
        $hashedKey = hash('sha256', $key); // Ensure it's a 256-bit key
        $this->key = Key::loadFromAsciiSafeString(hex2bin($hashedKey)); // Convert hex to binary
    }

    public function encrypt($data)
    {
        // Ensure the data is treated as binary
        if (!is_string($data)) {
            throw new \InvalidArgumentException('Data must be a string.');
        }

        // Pad the data if necessary
        $data = $this->padData($data);
        Log::info('Length of data before encryption: ' . strlen($data));
        $encryptedData = Crypto::encrypt($data, $this->key);
        Log::info('Length of encrypted data: ' . strlen($encryptedData));
        return $encryptedData;
    }

    public function decrypt($data)
    {
        $decryptedData = Crypto::decrypt($data, $this->key);
        // Remove padding after decryption
        return $this->unpadData($decryptedData);
    }

    private function padData($data)
    {
        // PKCS#7 padding
        $blockSize = 16; // Twofish block size
        $pad = $blockSize - (strlen($data) % $blockSize);
        return $data . str_repeat(chr($pad), $pad);
    }

    private function unpadData($data)
    {
        $pad = ord($data[strlen($data) - 1]);
        return substr($data, 0, -$pad);
    }
}