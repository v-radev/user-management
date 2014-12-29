<?php


class Hash {

    public static $saltLength = 20;

    public static function make( $string, $salt = '' )
    {
        return hash('sha256', $string . $salt);
    }

    public static function salt()
    {
        $salt = md5(uniqid(rand(), TRUE));
        return substr($salt, 0, static::$saltLength);
    }

    public static function unique()
    {
        return self::make(uniqid());
    }

} 
