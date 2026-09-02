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
		$this->load->model('Cart_model');

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
		$this->data['title'] 							= 'Konfirmasi Pembayaran';

		// Konfirmasi pembayaran HANYA boleh dilakukan lewat link dari halaman Track
		// Booking (yang membawa ?id_invoice=...). Kalau tidak ada id_invoice di URL,
		// jangan tampilkan form sama sekali - arahkan tamu untuk cari booking-nya dulu
		// di Track Booking, supaya No. Invoice tidak diketik manual (rawan salah ketik).
		// (Kalau dipanggil ulang dari send() karena validasi gagal, request-nya POST -
		// makanya id_invoice juga dicek dari POST supaya konteksnya tidak hilang.)
		$prefill_invoice = $this->input->get('id_invoice');
		if (empty($prefill_invoice)) {
			$prefill_invoice = $this->input->post('id_invoice');
		}
		$this->data['prefill_invoice']   = $prefill_invoice;
		$this->data['prefill_jenis']     = null;
		$this->data['can_confirm']       = false;
		$this->data['deposit_available']   = false;
		$this->data['pelunasan_available'] = false;
		$this->data['payment_info']      = null;

		if (!empty($prefill_invoice)) {
			$trans = $this->Cart_model->get_by_invoice($prefill_invoice);
			$status_booking = $trans ? $trans->status : null;
			$this->data['trans'] = $trans;

			if ($trans) {
				// Deposit (Jaminan) dan Pelunasan (Sewa/Grand Total) adalah DUA KEWAJIBAN
				// TERPISAH, masing-masing dengan progres pembayaran & status sendiri:
				//   Total yang harus dibayar tamu = Grand Total (Sewa) + 25% dari Grand Total
				//   (Deposit/Jaminan).
				$target_deposit          = round($trans->grand_total * 0.25);
				$total_dibayar_pelunasan = $this->Konfirmasi_model->get_total_paid_by_jenis($prefill_invoice, 'pelunasan');
				$total_dibayar_deposit   = $this->Konfirmasi_model->get_total_paid_by_jenis($prefill_invoice, 'deposit');

				$sisa_tagihan = max(0, $trans->grand_total - $total_dibayar_pelunasan); // sisa Sewa
				$sisa_deposit = max(0, $target_deposit - $total_dibayar_deposit);       // sisa Deposit

				$status_pelunasan = ($total_dibayar_pelunasan >= $trans->grand_total) ? 'Lunas' : 'Belum Lunas';
				$status_deposit   = ($total_dibayar_deposit >= $target_deposit) ? 'Lunas' : 'Belum Lunas';

				// Total keseluruhan kewajiban tamu & sisa yang masih harus dibayar (Sewa + Deposit)
				$total_keseluruhan_tagihan = $trans->grand_total + $target_deposit;
				$total_keseluruhan_dibayar = $total_dibayar_pelunasan + $total_dibayar_deposit;
				$sisa_keseluruhan          = max(0, $total_keseluruhan_tagihan - $total_keseluruhan_dibayar);

				// Jatuh tempo Deposit = 3 hari sejak booking (checkout) dibuat. Kolom `deadline`
				// di tabel transaksi sudah dihitung persis begitu saat checkout (lihat Cart::checkout()).
				$jatuh_tempo_deposit = !empty($trans->deadline) ? $trans->deadline : null;

				$this->data['payment_info'] = array(
					// Sewa / Pelunasan (Grand Total)
					'grand_total'             => $trans->grand_total,
					'total_dibayar_pelunasan' => $total_dibayar_pelunasan,
					'sisa_tagihan'            => $sisa_tagihan,
					'status_pelunasan'        => $status_pelunasan,
					// Deposit / Jaminan (25% dari Grand Total)
					'target_deposit'          => $target_deposit,
					'total_dibayar_deposit'   => $total_dibayar_deposit,
					'sisa_deposit'            => $sisa_deposit,
					'status_deposit'          => $status_deposit,
					'jatuh_tempo_deposit'     => $jatuh_tempo_deposit,
					// Ringkasan keseluruhan (Sewa + Deposit)
					'total_keseluruhan_tagihan' => $total_keseluruhan_tagihan,
					'total_keseluruhan_dibayar' => $total_keseluruhan_dibayar,
					'sisa_keseluruhan'          => $sisa_keseluruhan,
				);

				// Jenis apa saja yang masih boleh dikonfirmasi tamu (berdasarkan status &
				// apakah target nominal jenis tsb - dihitung TERPISAH per kategori - sudah tercukupi):
				$this->data['deposit_available']   = (!in_array($status_booking, array('0', '3', '5', '6')) && $total_dibayar_deposit < $target_deposit);
				$this->data['pelunasan_available'] = ($status_booking != '4' && !in_array($status_booking, array('0', '3', '5', '6')) && $total_dibayar_pelunasan < $trans->grand_total);

				$this->data['can_confirm'] = ($this->data['deposit_available'] || $this->data['pelunasan_available']);

				if ($this->data['deposit_available'] && $this->data['pelunasan_available']) {
					$this->data['prefill_jenis'] = ($status_booking == '2') ? 'pelunasan' : 'deposit';
				} elseif ($this->data['deposit_available']) {
					$this->data['prefill_jenis'] = 'deposit';
				} elseif ($this->data['pelunasan_available']) {
					$this->data['prefill_jenis'] = 'pelunasan';
				}

				// Kalau form ini dirender ulang karena validasi gagal (request POST dari
				// send()), pertahankan jenis yang tadi sudah dipilih tamu.
				$posted_jenis = $this->input->post('jenis_konfirmasi');
				if (!empty($posted_jenis)) {
					$this->data['prefill_jenis'] = $posted_jenis;
				}
			}
		}

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
		$this->form_validation->set_rules('jenis_konfirmasi', 'Jenis Konfirmasi', 'trim|required|in_list[deposit,pelunasan]');
		$this->form_validation->set_rules('nama_pengirim', 'Nama Pengirim', 'trim|required');
		$this->form_validation->set_rules('bank_pengirim', 'Bank Pengirim', 'trim|required');
		$this->form_validation->set_rules('nominal', 'Nominal Transfer', 'trim|required|numeric');

		$this->form_validation->set_message('required', '{field} wajib diisi');
		$this->form_validation->set_message('numeric', '{field} harus berupa angka tanpa titik/koma');
		$this->form_validation->set_message('max_length', '{field} maksimal {param} karakter (Format: J-YYMMDD-XXXX)');
		$this->form_validation->set_message('in_list', '{field} tidak valid');
		$this->form_validation->set_error_delimiters('<div class="alert alert-danger alert">', '</div>');

		if ($this->form_validation->run() == FALSE) {
			$this->contact();
		} else {
			$id_invoice = $this->input->post('id_invoice', TRUE);
			$jenis_konfirmasi = $this->input->post('jenis_konfirmasi', TRUE);

			// 2. CEK KETERSEDIAAN INVOICE MENGGUNAKAN MODEL
			$cek_invoice = $this->Konfirmasi_model->cek_invoice_valid($id_invoice, $jenis_konfirmasi);

			if ($cek_invoice == 0) {
				// Kasih pesan yang lebih spesifik kalau invoice-nya sebenarnya valid tapi
				// jenis konfirmasi yang dikirim sudah tidak relevan lagi dengan status
				// transaksi saat ini, atau target nominal jenis tersebut (dihitung terpisah
				// per kategori Deposit/Pelunasan) sudah tercukupi.
				$trans_sekarang = $this->Cart_model->get_by_invoice($id_invoice);

				if (!$trans_sekarang) {
					$this->session->set_flashdata('message', '<div class="row"><div class="col-lg-12"><div class="alert alert-danger alert"><i class="fa fa-exclamation-triangle"></i> No. Invoice <b>' . htmlspecialchars($id_invoice) . '</b> tidak ditemukan. Silakan periksa kembali.</div></div></div>');
				} elseif ($jenis_konfirmasi === 'deposit' && $this->Konfirmasi_model->get_total_paid_by_jenis($id_invoice, 'deposit') >= ($trans_sekarang->grand_total * 0.25)) {
					$this->session->set_flashdata('message', '<div class="row"><div class="col-lg-12"><div class="alert alert-warning alert"><i class="fa fa-info-circle"></i> Target Deposit (25% dari Grand Total) untuk invoice <b>' . htmlspecialchars($id_invoice) . '</b> sudah tercukupi dari pembayaran sebelumnya. Tidak perlu kirim konfirmasi Deposit lagi.</div></div></div>');
				} elseif ($jenis_konfirmasi === 'pelunasan' && $trans_sekarang->status === '4') {
					$this->session->set_flashdata('message', '<div class="row"><div class="col-lg-12"><div class="alert alert-warning alert"><i class="fa fa-info-circle"></i> Invoice <b>' . htmlspecialchars($id_invoice) . '</b> sudah <b>LUNAS PEMBAYARAN</b>. Tidak ada lagi Sewa yang perlu dikonfirmasi.</div></div></div>');
				} elseif ($jenis_konfirmasi === 'pelunasan' && $this->Konfirmasi_model->get_total_paid_by_jenis($id_invoice, 'pelunasan') >= $trans_sekarang->grand_total) {
					$this->session->set_flashdata('message', '<div class="row"><div class="col-lg-12"><div class="alert alert-warning alert"><i class="fa fa-info-circle"></i> Tagihan Sewa (Grand Total) untuk invoice <b>' . htmlspecialchars($id_invoice) . '</b> sudah <b>LUNAS</b>. Tidak ada lagi pembayaran Sewa yang perlu dikonfirmasi.</div></div></div>');
				} else {
					$this->session->set_flashdata('message', '<div class="row"><div class="col-lg-12"><div class="alert alert-danger alert"><i class="fa fa-exclamation-triangle"></i> No. Invoice <b>' . htmlspecialchars($id_invoice) . '</b> sudah tidak aktif (Expired/Refund/Dibatalkan) atau jenis konfirmasi tidak valid untuk status saat ini.</div></div></div>');
				}
				redirect(site_url('confirm') . '?id_invoice=' . urlencode($id_invoice));
				return;
			}

			// (Konfirmasi Pelunasan sekarang boleh dicicil juga, sama seperti Deposit - jadi
			// tidak ada lagi validasi "harus bayar penuh sekaligus" di sini.)

			// 3. Konfigurasi Upload File Bukti Transfer
			// Disimpan terpisah di folder bukti_transaksi/ (bukan langsung di
			// assets/images/) supaya file bukti transfer & NPWP mudah dibedakan
			// dari aset gambar lain (foto lapangan, event, dsb) - termasuk saat
			// mau dibersihkan/di-purge secara berkala.
			$bukti_upload_path = './assets/images/bukti_transaksi/';
			if (!is_dir($bukti_upload_path)) {
				mkdir($bukti_upload_path, 0755, true);
			}

			$config['upload_path']   = $bukti_upload_path;
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
					'id_invoice'       => $id_invoice,
					'jenis_konfirmasi' => $jenis_konfirmasi,
					'nama_pengirim'    => $nama_pengirim,
					'bank_pengirim'    => $bank_pengirim,
					'nominal'          => $nominal,
					'bukti_transfer'   => $file_name,
					'catatan'          => $catatan,
					'created_at'       => date('Y-m-d H:i:s')
				);

				$insert = $this->Konfirmasi_model->insert($data_konfirmasi);

				if ($insert) {
					$this->session->set_flashdata('message', '<div class="row"><div class="col-lg-12"><div class="alert alert-success alert"><i class="fa fa-check-circle"></i> Konfirmasi ' . ($jenis_konfirmasi == 'deposit' ? 'Deposit' : 'Pelunasan') . ' Anda telah Terkirim dan sedang menunggu validasi Admin. Terima Kasih!</div></div></div>');
					redirect(site_url('cart/track_booking'));
				} else {
					$this->session->set_flashdata('message', '<div class="row"><div class="col-lg-12"><div class="alert alert-danger alert"><i class="fa fa-exclamation-triangle"></i> Terjadi kesalahan pada database. Konfirmasi Gagal.</div></div></div>');
					redirect(site_url('confirm') . '?id_invoice=' . urlencode($id_invoice));
				}
			} else {
				$error_upload = $this->upload->display_errors('', '');
				$this->session->set_flashdata('message', '<div class="row"><div class="col-lg-12"><div class="alert alert-danger alert"><i class="fa fa-exclamation-triangle"></i> Upload Bukti Transfer Gagal: ' . $error_upload . '</div></div></div>');
				redirect(site_url('confirm') . '?id_invoice=' . urlencode($id_invoice));
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