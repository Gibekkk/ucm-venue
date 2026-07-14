<?php
defined('BASEPATH') OR exit('No direct script access allowed');

/**
 * Detect user's preferred language from browser headers
 * Returns language code: 'english', 'indonesian', 'chinese_simplified', 'chinese_traditional'
 */
if (!function_exists('detect_user_language')) {
    function detect_user_language() {
        $CI =& get_instance();
        
        // Check if language is already set in session
        if ($CI->session->userdata('site_lang')) {
            return $CI->session->userdata('site_lang');
        }
        
        // Check if language is stored in cookie
        if (isset($_COOKIE['site_lang'])) {
            $CI->session->set_userdata('site_lang', $_COOKIE['site_lang']);
            return $_COOKIE['site_lang'];
        }
        
        // Detect from browser Accept-Language header
        if (isset($_SERVER['HTTP_ACCEPT_LANGUAGE'])) {
            $browser_lang = substr($_SERVER['HTTP_ACCEPT_LANGUAGE'], 0, 2);
            
            switch (strtolower($browser_lang)) {
                case 'id':
                    return 'indonesian';
                case 'zh':
                    // Try to detect simplified vs traditional
                    $full_lang = strtolower($_SERVER['HTTP_ACCEPT_LANGUAGE']);
                    if (strpos($full_lang, 'zh-tw') !== false || strpos($full_lang, 'zh-hk') !== false) {
                        return 'chinese_traditional';
                    }
                    return 'chinese_simplified';
                case 'en':
                default:
                    return 'english';
            }
        }
        
        // Default to English
        return 'english';
    }
}

/**
 * Switch language and store in session and cookie
 */
if (!function_exists('switch_language')) {
    function switch_language($lang) {
        $CI =& get_instance();
        
        // Validate language
        $allowed_langs = ['english', 'indonesian', 'chinese_simplified', 'chinese_traditional'];
        if (!in_array($lang, $allowed_langs)) {
            $lang = 'english';
        }
        
        // Store in session
        $CI->session->set_userdata('site_lang', $lang);
        
        // Store in cookie (30 days)
        setcookie('site_lang', $lang, time() + (30 * 24 * 60 * 60), '/');
        
        return true;
    }
}

/**
 * Get language code for display (en, id, zh-CN, zh-TW)
 */
if (!function_exists('get_language_code')) {
    function get_language_code($lang = null) {
        if ($lang === null) {
            $CI =& get_instance();
            $lang = $CI->session->userdata('site_lang') ?: 'english';
        }
        
        switch ($lang) {
            case 'indonesian':
                return 'id';
            case 'chinese_simplified':
                return 'zh-CN';
            case 'chinese_traditional':
                return 'zh-TW';
            case 'english':
            default:
                return 'en';
        }
    }
}

/**
 * Get language name for display
 */
if (!function_exists('get_language_name')) {
    function get_language_name($lang) {
        switch ($lang) {
            case 'indonesian':
                return 'Bahasa Indonesia';
            case 'chinese_simplified':
                return '简体中文';
            case 'chinese_traditional':
                return '繁體中文';
            case 'english':
            default:
                return 'English';
        }
    }
}
