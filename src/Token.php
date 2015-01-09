<?php


/**
 * Class Token
 * @author V.Radev <mail@radev.info>
 */
class Token {

    private function __construct() {}

    /**
     * @var int Max allowed time to use token
     */
    public static $maxTime = 3600;//1 hour

    /**
     * @param bool $saveInSession If true it will add the generated token inside the session
     *
     * @return string
     */
    public static function generate( $saveInSession = FALSE )
    {
        $token = md5( uniqid(mt_rand(), true) . Config::APP_NAME);
        if ( $saveInSession ) static::saveInSession( $token );
        return $token;
    }


    public static function check( $token )
    {
        $name = Config::SESSION_TOKEN_NAME;
        $sessionToken = Session::get( $name );

        if ( $sessionToken === $token && static::isRecent() ){
            Session::delete( $name );
            return TRUE;
        }

        return FALSE;
    }

    public static function saveInSession( $token )
    {
        Session::put(Config::SESSION_TOKEN_NAME, $token);
        Session::put(Config::SESSION_TOKEN_TIME, time());
    }

    /**
     * Checks if the token is generated recently
     *
     * @return bool
     */
    public static function isRecent()
    {
        $time = Config::SESSION_TOKEN_TIME;

        if( Session::exists( $time ) ) {
            $stored_time = Session::get( $time );
            return ($stored_time + static::$maxTime) >= time();
        } else {
            static::destroy();
            return FALSE;
        }
    }

    public static function destroy() {
        Session::delete( Config::SESSION_TOKEN_NAME );
        Session::delete( Config::SESSION_TOKEN_TIME );
    }

} 
