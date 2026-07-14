<?php
defined('BASEPATH') OR exit('No direct script access allowed');

class Language extends CI_Controller {
    
    public function __construct() {
        parent::__construct();
        $this->load->helper('language_helper');
    }
    
    /**
     * Switch language
     * Usage: /language/switch/english or /language/switch/indonesian
     */
    public function switch_lang($lang = 'english') {
        switch_language($lang);
        
        // Redirect back to previous page or home
        $redirect_url = $this->input->server('HTTP_REFERER') ?: base_url();
        redirect($redirect_url);
    }
}
