<?php

namespace App;

class Constants
{

    // DO NOT CHANGES VALUES UNLESS YOU KNOW WHAT YOUR DOING
    // SOME FUNCTIONALITY DEPENDS ON THESE VALUES
    // RANDOM CHANGES MIGHT BREAK FUNCTIONALITY OF THE CODE

    const ADMINS_ROLE_ID = 1;
    const LOGIN_REDIRECT_PATH = '/';
    const DEFAULT_PAGINATION_COUNT = 5;
    const MAIN_PAGINATION_COUNT = 10;
    const DEFAULT_LANGUAGE = 'ar';
    const AVAILABLE_LANGUAGES = ['en', 'ar'];
    const UPLOAD_DIR = 'upload/';
    const PUBLIC_DIR = '';
    const PUBLIC_UPLOAD = self::UPLOAD_DIR.'/'.self::UPLOAD_DIR;
    const PUBLIC_TRAVEL_DIR = self::PUBLIC_UPLOAD . '/' . 'travel/';

}
