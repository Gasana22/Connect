<?php
/**
 * Site-wide configuration. Values come from environment variables in production;
 * sane local-dev defaults are used when unset so the app runs out of the box.
 */

if (!function_exists('env')) {
    function env(string $key, $default = null) {
        $value = getenv($key);
        return $value === false ? $default : $value;
    }
}

define('DB_HOST', env('CONNECT_DB_HOST', 'localhost'));
define('DB_NAME', env('CONNECT_DB_NAME', 'connect'));
define('DB_USER', env('CONNECT_DB_USER', 'connect'));
define('DB_PASS', env('CONNECT_DB_PASS', 'connect_dev_pw'));

define('SITE_URL', rtrim(env('CONNECT_SITE_URL', 'http://localhost:8000'), '/'));
define('SITE_NAME', 'Connect');

define('UPLOAD_MAX_VIDEO_BYTES', 50 * 1024 * 1024); // 50MB
define('UPLOAD_MAX_IMAGE_BYTES', 5 * 1024 * 1024);  // 5MB

define('UPLOAD_DIR_VIDEOS', __DIR__ . '/../uploads/videos/');
define('UPLOAD_DIR_PROFILE_PICS', __DIR__ . '/../uploads/profile_pics/');
define('UPLOAD_DIR_ADS', __DIR__ . '/../uploads/ads/');
define('UPLOAD_DIR_LIVE', __DIR__ . '/../uploads/live/');

define('UPLOAD_URL_VIDEOS', 'uploads/videos/');
define('UPLOAD_URL_PROFILE_PICS', 'uploads/profile_pics/');
define('UPLOAD_URL_ADS', 'uploads/ads/');
define('UPLOAD_URL_LIVE', 'uploads/live/');

define('BUSINESS_CATEGORIES', [
    'Restaurant'    => 'Restaurant & Food',
    'Retail'        => 'Retail & Shopping',
    'Health'        => 'Health & Wellness',
    'Beauty'        => 'Beauty & Salon',
    'Tech'          => 'Technology',
    'Finance'       => 'Finance & Banking',
    'RealEstate'    => 'Real Estate',
    'Education'     => 'Education',
    'Entertainment' => 'Entertainment',
    'Automotive'    => 'Automotive',
    'Professional'  => 'Professional Services',
    'HomeServices'  => 'Home Services',
    'Travel'        => 'Travel & Hospitality',
    'Fitness'       => 'Fitness & Sports',
    'Art'           => 'Art & Creative',
    'Other'         => 'Other',
]);

error_reporting(E_ALL);
ini_set('display_errors', env('CONNECT_DEBUG', '0') === '1' ? '1' : '0');
date_default_timezone_set('UTC');
