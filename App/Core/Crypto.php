<?php
declare (strict_types = 1);
namespace App\Core;

class Crypto {
    private static function Chunk(string $str, int $number):string
    {
        $chunk = intval(strlen($str) / 4);

        return substr($str, $number * $chunk, $chunk);
    }

    private static function Salt():string{
        return strtoupper(md5((string) random_int(1, time())));
    }

    public static function Encrypt($str):string
    {
        $str = base64_encode($str);

        $salt = self::Salt();

        $p1 = self::Chunk($str, 0) . self::Chunk($salt, 0);
        $p2 = self::Chunk($str, 1) . self::Chunk($salt, 0);
        $p3 = self::Chunk($str, 2) . self::Chunk($salt, 0);
        $p4 = self::Chunk($str, 3) . self::Chunk($salt, 0);

        $str = strrev($p4) . strrev($p2) . strrev($p1) . strrev($p3);

        return $str;
    }

    public static function Decrypt($str):string
    {
        $saltLen = 32/4;

        $p4 = substr(self::Chunk($str, 0), $saltLen);
        $p2 = substr(self::Chunk($str, 1), $saltLen);
        $p1 = substr(self::Chunk($str, 2), $saltLen);
        $p3 = substr(self::Chunk($str, 3), $saltLen);

        $str = strrev($p1) . strrev($p2) . strrev($p3) . strrev($p4);

        $str = base64_decode($str);

        return $str;
    }
}
?>