<?php

/**
 * Class Session
 * @author V.Radev <mail@radev.info>
 */
class Session {

    /**
     * @var string Namespace
     */
    protected static $_n = 'sessionClass';

    /**
     * @param string $name
     * @param string $value
     *
     * @return bool
     */
    public static function put( $name, $value )
    {
        $_SESSION[self::$_n][$name] = $value;
        return true;
    }

    /**
     * @param string $name
     *
     * @return null
     */
    public static function get( $name )
    {
        return self::exists( $name ) ?
                $_SESSION[self::$_n][$name] :
                NULL;
    }

    /**
     * @param string $name
     *
     * @return bool
     */
    public static function exists( $name )
    {
        return isset($_SESSION[self::$_n][$name]);
    }

    /**
     * @param string $name
     */
    public static function delete( $name )
    {
        if ( self::exists($name) ){
            $_SESSION[self::$_n][$name] = '';
            unset($_SESSION[self::$_n][$name]);
        }
    }

    /**
     * @param string $name
     * @param string $string
     *
     * @return null|string
     */
    public static function flash( $name, $string = '' )
    {
        $session = self::get($name);

        //If you have this session
        if ( static::exists($name) && !$string ){
            self::delete($name);
        } elseif ( $string ) {
            self::put($name, $string);
        }

        return $session;
    }


    public static function getAll()
    {
        if ( isset($_SESSION[self::$_n]) ) return $_SESSION[self::$_n];

        return NULL;
    }
}