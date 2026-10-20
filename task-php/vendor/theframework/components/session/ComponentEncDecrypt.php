<?php
/**
 * @author Eduardo Acevedo Farje.
 * @link eduardoaf.com
 * @version 1.5.1
 * @name ComponentEncdecrypt
 * @file ComponentEncDecrypt.php
 * @date 15-04-2017 10:04 (SPAIN)
 * @observations:
 * https://github.com/eacevedof/prj_theframework/blob/9192e1a90010792048a6383efbebbd85f105922a/the_application/components/appcomponent_encdecrypt.php
 * @requires:
 */

namespace TheFramework\Components\Session;

final class ComponentEncDecrypt
{
    public const ENCRYPT_TYPE_NORMAL = "normal";
    public const ENCRYPT_TYPE_NUMERIC = "numeric";

    private string $encryptType;
    private int $lenDirty = 5;
    private array $numberConfig;
    private array $excludeChars;

    private string $sslMethod;
    private string $sslKey;
    private string $sslIv;

    private bool $useSalt = true;
    private string $saltString;
    private bool $useTime = false;

    public function __construct()
    {
        $this->excludeChars = [];
        $this->numberConfig = [
            "0" => "e","1" => "F","2" => "g","3" => "H","4" => "i",
            "5" => "j","6" => "K","7" => "l","8" => "m","9" => "N"
        ];

        $this->encryptType = self::ENCRYPT_TYPE_NORMAL;
        $this->sslMethod = "AES-256-CBC";
        $this->sslKey = "@11111111@";
        $this->sslIv = "99326425";

        $this->saltString = "@#$.salt.$#@";

        if (defined("ENV_SSLENC_METHOD")) {
            $this->sslMethod = ENV_SSLENC_METHOD;
        }
        if (defined("ENV_SSLENC_KEY")) {
            $this->sslKey = ENV_SSLENC_KEY;
        }
        if (defined("ENV_SSLENC_IV")) {
            $this->sslIv = ENV_SSLENC_IV;
        }
        if (defined("ENV_MD5_SALT")) {
            $this->saltString = ENV_MD5_SALT;
        }
    }

    public static function getInstance(): self
    {
        return new self();
    }


    private function tryToApplySalt(string &$toBeSalted): void
    {
        if ($this->useSalt) {
            $toBeSalted = "$this->saltString.$toBeSalted";
        }
    }

    public function getSslEncrypted(string $toEncrypt): string
    {
        $sslKey = $this->sslKey;
        $this->tryToApplySalt($sslKey);
        $hashKey = hash("sha256", $sslKey);
        $hashIv = substr(hash("sha256", $this->sslIv), 0, 16);
        //print_r(openssl_get_cipher_methods());
        //pr("toenc:$sToEncrypt,method:$this->sSslMethod,hashkey:$sHashKey,0,hashiv:$sHashIv");
        if ($this->useTime) {
            $toEncrypt = $toEncrypt."-".date("YmdHis");
        }
        $encrypted = openssl_encrypt($toEncrypt, $this->sslMethod, $hashKey, 0, $hashIv);
        $encrypted = base64_encode($encrypted);
        return $encrypted;
    }//get_sslencrypted

    public function getSslDecrypted(string $encryptedString): string
    {
        $sslKey = $this->sslKey;
        $this->tryToApplySalt($sslKey);
        $hashKey = hash("sha256", $sslKey);
        $hashIv = substr(hash("sha256", $this->sslIv), 0, 16);
        $decrypted = base64_decode($encryptedString);
        $decrypted = openssl_decrypt($decrypted, $this->sslMethod, $hashKey, 0, $hashIv);
        if ($this->useTime) {
            $decrypted = explode("-", $decrypted);
            return $decrypted[0];
        }
        return $decrypted;
    }//get_ssldecrypted

    public function getMd5Password(string $toEncrypt): string
    {
        $this->tryToApplySalt($toEncrypt);
        $md5 = md5($toEncrypt);
        return $md5;
    }//get_md5password

    public function isValidMd5Password(string $plainPass, string $md5Pass): bool
    {
        $md5 = $this->getMd5Password($plainPass);
        return($md5 === $md5Pass);
    }//check_md5password

    public function getPasswordHashed(string $plainPassword): string
    {
        $this->tryToApplySalt($plainPassword);
        return password_hash($plainPassword, PASSWORD_DEFAULT);
    }//get_hashpassword

    public function isValidPassword(string $plainPass, string $hashPass): bool
    {
        $this->tryToApplySalt($plainPass);
        return password_verify($plainPass, $hashPass);
    }

    public function getRandomWord(int $len = 8): string
    {
        $len = $len - 4;
        $consonants = ["b","c","d","f","g","h","j","k","l","m","n","p","q","r","s","t","v","w","x","y","z",
            "B","C","D","F","G","H","J","K","L","M","N","P","Q","R","S","T","V","W","X","Y","Z"
        ];
        $vocals = ["a","e","i","o","u","A","E","I","O","U"];
        $chars = ["@","#","&","$"];
        $numbers = ["0","1","2","3","4","5","6","7","8","9"];

        $word = [];
        $word = [];
        for($i = 0; $i < $len; $i++) {
            if ($i % 2 === 0) {
                $pos = array_rand($consonants, 1);
                $word[] = $consonants[$pos];
            } else {
                $pos = array_rand($vocals, 1);
                $word[] = $vocals[$pos];
            }
        }

        $pos = array_rand($chars, 1);
        $word[] = $chars[$pos];
        shuffle($numbers);
        for($i = 0;$i < 3;$i++) {
            $pos = array_rand($numbers, 1);
            $word[] = $numbers[$pos];
        }
        return implode("", $word);
    }

    private function replaceNumbersWithLetters(string &$numeric): void
    {
        $numbers = array_keys($this->numberConfig);
        $nums = str_split($numeric);
        foreach($nums as $i => $number) {
            if (in_array($number, $numbers)) {
                $nums[$i] = $this->numberConfig[$number];
            }
        }
        $numeric = implode("", $nums);
    }

    private function replaceLettersWithNumbers(string &$letters): void
    {
        $lettersArray = array_values($this->numberConfig);
        $chars = str_split($letters);
        foreach($chars as $i => $letter) {
            if (in_array($letter, $lettersArray)) {
                $chars[$i] = array_search($letter, $this->numberConfig);
            }
        }
        $letters = implode("", $chars);
    }//num_replace

    private function getRandomString(?int $lenDirty = null): string
    {
        if (!$lenDirty) {
            $lenDirty = $this->lenDirty;
        }

        $chars = str_split(
            "abcdefghijklmnopqrstuvwxyz"
            ."ABCDEFGHIJKLMNOPQRSTUVWXYZ"
            ."0123456789!@#$%^&*()"
        );
        //quito los caracteres que no me interesan, por ejemplo %,$ para las urls
        if ($this->excludeChars) {
            foreach($chars as $i => $char) {
                if (in_array($char, $this->excludeChars)) {
                    unset($chars[$i]);
                }
            }
        }

        shuffle($chars);
        $random = "";
        foreach(array_rand($chars, $lenDirty) as $pos) {
            $random .= $chars[$pos];
        }
        return $random;
    }//getRandomString

    private function getDirtyString(string $string): string
    {
        $string = strrev($string);
        $chars = str_split($string);
        $dirty = array();
        foreach($chars as $c) {
            $dirty[] = $c;
            $dirty[] = $this->getRandomString();
        }
        $dirty = implode("", $dirty);
        $dirty = $this->getRandomString().$dirty;
        return $dirty;
    }//dirty

    private function getDirtyNumber(string $numeric): string
    {
        //cambio los numeros por letras segun arNumberConfig
        $this->replaceNumbersWithLetters($numeric);
        return $this->getDirtyString($numeric);
    }//getDirtyNumber

    private function cleanInventedChars(string $string, ?int $lenDirty = null): string
    {
        if (!$lenDirty) {
            $lenDirty = $this->lenDirty;
        }
        $chars = str_split($string);
        $cleaned = [];

        $dirty = 0;
        $i = 0;
        foreach($chars as $c) {
            if ($i == $lenDirty) {
                $cleaned[] = $c;
                $i = 0;
            } else {
                $i++;
            }
        }
        $cleaned = implode("", $cleaned);
        $cleaned = strrev($cleaned);
        return $cleaned;
    }//clean

    private function cleanAndLeaveOnlyNumbers(string $string, ?int $lenDirty = null)
    {
        //limpio los caracteres inventados
        $cleaned = $this->cleanInventedChars($string, $lenDirty);
        $this->replaceLettersWithNumbers($cleaned);
        return $cleaned;
    }//clean_number

    public function getEncrypted(string $text): string
    {
        switch($this->encryptType) {
            case self::ENCRYPT_TYPE_NUMERIC:
                return $this->getDirtyNumber($text);
            default://normal
                return $this->getDirtyString($text);
        }
    }//encrypt

    public function getDecrypted(string $string): string
    {
        switch($this->encryptType) {
            case self::ENCRYPT_TYPE_NUMERIC:
                return $this->cleanAndLeaveOnlyNumbers($string);
            default://normal
                return $this->cleanInventedChars($string);
        }
    }//get_decrypted

    public function getCsrf(): string
    {
        $sessionId = session_id();
        if ($sessionId) {
            $sessionId = $this->getPasswordHashed($sessionId);
        }
        return $sessionId;
    }//get_csrf

    public function getUniqid($toUnique, $inMd5 = 0)
    {
        if ($inMd5) {
            return md5(uniqid($toUnique, true));
        }
        return uniqid($toUnique, true);
    }//get_uniqid

    public function getSalted(string $passStr): string
    {
        return "{$this->saltString}{$passStr}";
    }

    public function setEncryptType(string $encType): void
    {
        $this->encryptType = $encType;
    }

    public function setLenOfDirty(int $lenDirty): void
    {
        $this->lenDirty = $lenDirty;
    }

    public function setCharsToExclude(string | array $chars): void
    {
        if (is_string($chars)) {
            $this->excludeChars = str_split($chars);
        } elseif (is_array($chars)) {
            $this->excludeChars = $chars;
        }
        $this->excludeChars = array_unique($this->excludeChars);
    }

    public function addCharToBeExcluded(string $char): void
    {
        $this->excludeChars[] = $char;
        $this->excludeChars = array_unique($this->excludeChars);
    }

    public function setSslKey(string $sslKey): void
    {
        $this->sslKey = $sslKey;
    }

    public function setSaltString(string $saltString): void
    {
        $this->saltString = $saltString;
    }

    public function setSslMethod(string $sslMethod): void
    {
        $this->sslMethod = $sslMethod;
    }

    public function setSslIv(int $sslIv): void
    {
        $this->sslIv = $sslIv;
    }

    public function enableSaltString(): void
    {
        $this->useSalt = true;
    }

    public function disableSaltString(): void
    {
        $this->useSalt = false;
    }

    //se usa para añadir miga a los valores numericos de modo que siempre devuelva un hash distinto
    public function enableTimeEntropyForNumericType(): void
    {
        $this->useTime = true;
    }

    public function disableTimeEntropyForNumericType(): void
    {
        $this->useTime = false;
    }
}//ComponentEncdecrypt
