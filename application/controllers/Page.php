<?php
defined('BASEPATH') or exit('No direct script access allowed');

class Page extends CI_Controller
{

	function __construct()
	{
		parent::__construct();
		/* memanggil model untuk ditampilkan pada masing2 modul */
		$this->load->model('Company_model');
		$this->load->model('Event_model');
		$this->load->model('Foto_model');
		$this->load->model('Kategori_model');
		$this->load->model('Kontak_model');
		$this->load->model('Konfirmasi_model');

		$this->data['company_data'] 	= $this->Company_model->get_by_company();
		$this->data['event_sidebar'] 			= $this->Event_model->get_all_sidebar();
		$this->data['kategori_sidebar'] 	= $this->Kategori_model->get_all();
		$this->data['kontak_sidebar'] 		= $this->Kontak_model->get_all();
		$this->data['kontak'] 				= $this->Kontak_model->get_all();
		$this->data['konfirmasi'] 			= $this->Konfirmasi_model->get_all();

		// Load language system
		$this->load->helper('language_helper');
		$user_lang = detect_user_language();
		$this->lang->load('site', $user_lang);
		$this->data['current_lang'] = $user_lang;
	}

	public function about()
	{
		$this->data['title'] 							= 'Tentang Kami';

		/* melakukan pengecekan data, apabila ada maka akan ditampilkan */
		$this->data['company']            = $this->Company_model->get_by_company();

		/* memanggil view yang telah disiapkan dan passing data dari model ke view*/
		$this->load->view('front/page/about', $this->data);
	}

	public function contact()
	{
		$this->data['title'] 							= 'Contact';

		$this->data['name'] = array(
			'name'  => 'name',
			'id'    => 'name',
			'class' => 'form-control',
			'placeholder'    => 'Nama',
			'value' => $this->form_validation->set_value('name'),
		);
		$this->data['email'] = array(
			'name'  => 'email',
			'id'    => 'email',
			'class' => 'form-control',
			'placeholder'    => 'Email',
			'value' => $this->form_validation->set_value('email'),
		);
		$this->data['subject'] = array(
			'name'  => 'subject',
			'id'    => 'subject',
			'class' => 'form-control',
			'placeholder'    => 'Judul, ex: konfirmasi pembayaran',
			'value' => $this->form_validation->set_value('subject'),
		);
		$this->data['message'] = array(
			'name'  => 'message',
			'id'    => 'message',
			'class' => 'form-control',
			'placeholder'    => 'Isi Pesan',
			'value' => $this->form_validation->set_value('message'),
		);

		$this->load->view('front/page/contact', $this->data);
	}
	public function send()
	{
		// 1. Validasi Input Form Konfirmasi
		$this->form_validation->set_rules('id_invoice', 'No. Invoice', 'trim|required|max_length[15]'); // Memaksa input persis 15 karakter sesuai CHAR(15)
		$this->form_validation->set_rules('nama_pengirim', 'Nama Pengirim', 'trim|required');
		$this->form_validation->set_rules('bank_pengirim', 'Bank Pengirim', 'trim|required');
		$this->form_validation->set_rules('nominal', 'Nominal Transfer', 'trim|required|numeric');

		$this->form_validation->set_message('required', '{field} wajib diisi');
		$this->form_validation->set_message('numeric', '{field} harus berupa angka tanpa titik/koma');
		$this->form_validation->set_message('max_length', '{field} maksimal {param} karakter (Format: J-YYMMDD-XXXX)');
		$this->form_validation->set_error_delimiters('<div class="alert alert-danger alert">', '</div>');

		if ($this->form_validation->run() == FALSE) {
			$this->contact();
		} else {
			$id_invoice = $this->input->post('id_invoice', TRUE);

			// 2. CEK KETERSEDIAAN INVOICE MENGGUNAKAN MODEL
			$cek_invoice = $this->Konfirmasi_model->cek_invoice_valid($id_invoice);

			if ($cek_invoice == 0) {
				$this->session->set_flashdata('message', '<div class="row"><div class="col-lg-12"><div class="alert alert-danger alert"><i class="fa fa-exclamation-triangle"></i> No. Invoice <b>' . htmlspecialchars($id_invoice) . '</b> tidak ditemukan atau transaksi sudah Lunas. Silakan periksa kembali.</div></div></div>');
				redirect(site_url('contact'));
				return;
			}

			// 3. Konfigurasi Upload File Bukti Transfer
			$config['upload_path']   = './assets/images/';
			$config['allowed_types'] = 'jpg|jpeg|png';
			$config['max_size']      = 2048;
			$config['file_name']     = 'Bukti_' . time();

			$this->load->library('upload', $config);

			// 4. Eksekusi upload file
			if ($this->upload->do_upload('bukti_transfer')) {
				$upload_data = $this->upload->data();
				$file_name   = $upload_data['file_name'];

				$nama_pengirim = $this->input->post('nama_pengirim', TRUE);
				$bank_pengirim = $this->input->post('bank_pengirim', TRUE);
				$nominal       = $this->input->post('nominal', TRUE);
				$catatan       = $this->input->post('catatan', TRUE);

				// 5. SIMPAN KE DATABASE MENGGUNAKAN MODEL
				$data_konfirmasi = array(
					'id_invoice'     => $id_invoice,
					'nama_pengirim'  => $nama_pengirim,
					'bank_pengirim'  => $bank_pengirim,
					'nominal'        => $nominal,
					'bukti_transfer' => $file_name,
					'catatan'        => $catatan,
					'created_at'     => date('Y-m-d H:i:s')
				);

				$insert = $this->Konfirmasi_model->insert($data_konfirmasi);

				if ($insert) {
					$this->session->set_flashdata('message', '<div class="row"><div class="col-lg-12"><div class="alert alert-success alert"><i class="fa fa-check-circle"></i> Konfirmasi Pembayaran Anda telah Terkirim dan sedang menunggu validasi Admin. Terima Kasih!</div></div></div>');
					redirect(site_url('contact'));
				} else {
					$this->session->set_flashdata('message', '<div class="row"><div class="col-lg-12"><div class="alert alert-danger alert"><i class="fa fa-exclamation-triangle"></i> Terjadi kesalahan pada database. Konfirmasi Gagal.</div></div></div>');
					redirect(site_url('contact'));
				}
			} else {
				$error_upload = $this->upload->display_errors('', '');
				$this->session->set_flashdata('message', '<div class="row"><div class="col-lg-12"><div class="alert alert-danger alert"><i class="fa fa-exclamation-triangle"></i> Upload Bukti Transfer Gagal: ' . $error_upload . '</div></div></div>');
				redirect(site_url('contact'));
			}
		}
	}

	public function tes()
	{
		// setingan default tanpa smtp
		$this->load->library('email');

		$this->email->from('mail@azmicolejr.com', 'fungsimail');
		$this->email->to('azmi2793@gmail.com');
		$this->email->subject('kirimemailpakemail');
		$this->email->message('asdsadasd');

		if ($this->email->send()) {
			echo "berhasil";
		} else {
			echo "gasgal";
		}

		// Konfigurasi email dengan smtp
		// $config = [
		//    'smtp_host' => 'ssl://smtp.gmail.com',
		//    'smtp_user' => 'azmicolejr@gmail.com',   // Ganti dengan email gmail Anda.
		//    'smtp_pass' => 'Revoiu2550',             // Password gmail Anda.
		//    'smtp_port' => 465,
		// ];
		//
		// // Load library email dan konfigurasinya.
		// $this->load->library('email', $config);
		//
		// // Pengirim dan penerima email.
		// $this->email->from('mail@azmicolejr.com', 'kirimpakesmtp');    // Email dan nama pegirim.
		// $this->email->to('azmi2793@gmail.com');                       // Penerima email.
		//
		// // Subject email.
		// $this->email->subject('Kirim Email pada CodeIgniter');
		//
		// // Isi email. Bisa dengan format html.
		// $this->email->message('Halo bos, ada konfirmasi pembayaran baru dengan rincian sebagai berikut: <br>');
		//
		// if ($this->email->send())
		// {
		//   echo 'Sukses! email berhasil dikirim.';
		// }
		// else
		// {
		//   echo 'Error! email tidak dapat dikirim.';
		// }
	}
}