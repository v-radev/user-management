<?php


class Cookie {

    public static function exists( $name )
    {
        return isset($_COOKIE[$name]);
    }

    public static function get( $name )
    {
        if ( !static::exists($name) ){
            return NULL;
        }

        return $_COOKIE[$name];
    }

    public static function put( $name, $value, $time = 3 )
    {
        return setcookie(
            $name, 			           //cookie name
            $value, 				   //cookie value
            time() + ($time * 86400),  //days valid
            '/'					       //path
        );
    }

    public static function delete( $name )
    {
        if ( static::exists($name) ){
            unset($_COOKIE[$name]);
        }
        self::put($name, '', -1);
    }

} 