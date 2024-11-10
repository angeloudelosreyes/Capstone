<?php

namespace App\Services;

use Illuminate\Support\Facades\Crypt;

class DoubleEncryptionService
{
    protected $secondLayerKey;

    public function __construct()
    {
        $this->secondLayerKey = config('app.second_layer_key');
    }

    public function encrypt($data)
    {
        // First layer: Laravel's built-in AES encryption
        $firstLayerEncrypted = Crypt::encrypt($data);

        // Second layer: Custom Rijndael encryption
        $secondLayerEncrypted = $this->rijndaelEncrypt($firstLayerEncrypted, $this->secondLayerKey);

        return $secondLayerEncrypted;
    }

    public function decrypt($encryptedData)
    {
        // First layer: Custom Rijndael decryption
        $firstLayerDecrypted = $this->rijndaelDecrypt($encryptedData, $this->secondLayerKey);

        // Second layer: Laravel's built-in AES decryption
        $decryptedData = Crypt::decrypt($firstLayerDecrypted);

        return $decryptedData;
    }

    public function hashPassword(string $password): string

    {

        // Use a secure hashing algorithm like bcrypt or Argon2

        return password_hash($password, PASSWORD_BCRYPT);

    }

    protected function rijndaelEncrypt($data, $key)
    {
        // Convert key to a binary format
        $key = substr(hash('sha256', $key, true), 0, 32); // Ensure it's 256 bits
        $iv = openssl_random_pseudo_bytes(openssl_cipher_iv_length('aes-256-cbc'));

        $encrypted = openssl_encrypt($data, 'aes-256-cbc', $key, 0, $iv);
        return base64_encode($iv . $encrypted);
    }

    protected function rijndaelDecrypt($data, $key)
    {
        $data = base64_decode($data);
        $ivLength = openssl_cipher_iv_length('aes-256-cbc');
        $iv = substr($data, 0, $ivLength);
        $encrypted = substr($data, $ivLength);

        $key = substr(hash('sha256', $key, true), 0, 32); // Ensure it's 256 bits
        return openssl_decrypt($encrypted, 'aes-256-cbc', $key, 0, $iv);
    }
}