<?php
defined('BASEPATH') or exit('No direct script access allowed');

class Home extends CI_Controller
{

	/**
	 * Data array for views
	 *
	 * @var array
	 */
	public $data = array();

	public function __construct()
	{
		parent::__construct();
		
		// Load language helper and detect user language
		$this->load->helper('language_helper');
		$user_lang = detect_user_language();
		$this->lang->load('site', $user_lang);
		$this->data['current_lang'] = $user_lang;
	}

	public function index()
	{
		$this->data['title'] = 'Home';

		$this->load->model('Company_model');
		$this->load->model('Event_model');
		$this->load->model('Foto_model');
		$this->load->model('Kontak_model');
		$this->load->model('Lapangan_model');
		$this->load->model('Slider_model');

		$this->data['company_data'] 	= $this->Company_model->get_by_company();
		$this->data['event_new'] 			= $this->Event_model->get_all_new_home();
		$this->data['event_sidebar'] 	= $this->Event_model->get_all_sidebar();
		$this->data['foto_data'] 			= $this->Foto_model->get_all_new_home();
		$this->data['slider_data'] 		= $this->Slider_model->get_all_home();
		$this->data['kontak'] 				= $this->Kontak_model->get_all();
		$this->data['kontak_sidebar'] = $this->Kontak_model->get_all();
		$this->data['lapangan_new'] 	= $this->Lapangan_model->get_all_home();

		$this->load->view('front/home/body', $this->data);
	}
}
