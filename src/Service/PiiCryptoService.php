<?php
namespace App\Service;


use Exception;

/**
 * Class PiiCryptoService
 */
class PiiCryptoService
{
    public const PII_KEY = 'aW33$rftTZG&hfggREz456$44';//This key should be passed as an ENV var and be kept in secret vault
    public const CIPHERING = "AES-128-CBC";


    /**
     * @param string $data
     * @return string
     */
    public function encryptData(string $data): string
    {
        $ivLen = openssl_cipher_iv_length(self::CIPHERING);
        $iv = openssl_random_pseudo_bytes($ivLen);
        $ciphertext_raw = openssl_encrypt($data, self::CIPHERING, self::PII_KEY, $options=OPENSSL_RAW_DATA, $iv);
        $hmac = hash_hmac('sha256', $ciphertext_raw, self::PII_KEY, $as_binary=true);
        return base64_encode( $iv.$hmac.$ciphertext_raw );
    }

    /**
     * @param string $encodedData
     * @return string
     */
    public function decryptData(string $encodedData): string
    {
        $c = base64_decode($encodedData);
        $ivLen = openssl_cipher_iv_length(self::CIPHERING);
        $iv = substr($c, 0, $ivLen);
        $hmac = substr($c, $ivLen, $sha2len=32);
        $ciphertext_raw = substr($c, $ivLen+$sha2len);
        return openssl_decrypt($ciphertext_raw, self::CIPHERING, self::PII_KEY, $options=OPENSSL_RAW_DATA, $iv);
    }

    /**
     * @param string $data
     * @return string
     */
    public function hashData(string $data): string
    {
        $hexString = unpack('H*', $data);
        $hex = array_shift($hexString);

        return base64_encode((string) $hex);
    }

    /**
     * @param string $encodedData
     * @return string
     */
    public function unHashData(string $encodedData): string
    {
        return hex2bin(base64_decode($encodedData));
    }

}
