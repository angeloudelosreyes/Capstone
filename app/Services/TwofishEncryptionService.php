<?php

namespace App\Services;

use Defuse\Crypto\Crypto;
use Defuse\Crypto\Key;

class TwofishEncryptionService
{
    protected $key;

    public function __construct($key)
    {
        // Make sure to set a secure key
        $this->key = Key::loadFromAsciiSafeString($key);
    }

    public function encrypt($data)
    {
        return Crypto::encrypt($data, $this->key);
    }

    public function decrypt($data)
    {
        return Crypto::decrypt($data, $this->key);
    }
}