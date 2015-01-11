<?php


class Config {

    const USERS_TABLE = 'users';
    const SESSIONS_TABLE = 'user_sessions';
    const ATTEMPTS_TABLE = 'login_attempts';
    const GROUPS_TABLE = 'groups';
    const PERMISSIONS_TABLE = 'permissions';
    const GROUP_PERM_TABLE = 'group_permissions';

    const USERS_SESSION_NAME = 'sesUserLogin';
    const COOKIE_SESSION_NAME = 'jarUserLogin';


    const LOGIN_ATTEMPTS = 4;
    const LOGIN_ATTEMPTS_TIME = 2;//minutes

    const DB_HOST = 'localhost';
    const DB_NAME = 'login-and-db';
    const DB_USER = 'root';
    const DB_PASS = 'password123';

    const SESSION_TOKEN_NAME = 'tokk_een';
    const SESSION_TOKEN_TIME = 'tokk_ttime';

    const APP_NAME = 'loginAndDb';

}
