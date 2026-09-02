<?php if (!defined('BASEPATH')) exit('No direct script access allowed');

class Transaksi extends CI_Controller
{
  // Daftar status untuk dropdown filter & label di seluruh halaman transaksi admin
  public static $status_list = array(
    '1' => 'Belum Lunas',
    '2' => 'Lunas Deposit',
    '3' => 'Expired',
    '4' => 'Lunas Pembayaran',
    '5' => 'Refund',
    '6' => 'Dibatalkan',
  );

  public function __construct()
  {
    parent::__construct();
    $this->load->helper('tgl_indo');
    $this->load->model('Cart_model');
    $this->load->model('Company_model');
    $this->load->model('Konfirmasi_model');
    $this->load->model('Npwp_model');

    $this->data['module']         = 'Transaksi';
    $this->data['button_submit']  = 'Simpan';
    $this->data['button_reset']   = 'Reset';

    $this->data['company_data']   = $this->Company_model->get_by_company();

    // Cek apakah user sudah login
    if (!$this->ion_auth->logged_in()) {
      redirect('admin/auth/login', 'refresh');
    }
    // Izinkan akses untuk Superadmin, Admin, Finance, ICT, PM Admin, dan PM All
    elseif (
      !$this->ion_auth->is_superadmin() &&
      !$this->ion_auth->is_admin() &&
      !$this->ion_auth->is_finance() &&
      !$this->ion_auth->is_ict() &&
      !$this->ion_auth->is_pm_admin() &&
      !$this->ion_auth->is_pm_all()
    ) {
      redirect(base_url());
    }

    // Auto-expire booking yang belum dibayar > 3 hari (kecuali sudah Lunas Deposit)
    $this->Cart_model->expire_old_transactions();
  }

  public function index()
  {
    // Filter status. Default (tanpa ?status di URL, mis. pertama kali buka menu Transaksi) =
    // "Perlu Tindak Lanjut": Belum Lunas (1) DAN Lunas Deposit (2) sekaligus. Keduanya sama-sama
    // transaksi yang masih "berjalan" dan butuh perhatian admin, jadi harus tetap kelihatan di
    // tampilan utama - bukan cuma Belum Lunas saja. Kalau ini dibiarkan cuma status 1, transaksi
    // yang barusan di-set Lunas Deposit akan "hilang" dari tampilan default begitu admin
    // reload/buka ulang halaman (sekalipun datanya di database tetap ada, cuma ketutup filter).
    // "" = semua status (kecuali Belum Checkout, selalu disembunyikan).
    $status = $this->input->get('status');
    if ($status === null || $status === 'active') {
      $status_filter = 'active';
      $status_query  = array('1', '2');
    } else {
      $status_filter = $status;
      $status_query  = $status;
    }

    $this->data['title']         = 'Data ' . $this->data['module'];
    $this->data['status_list']   = self::$status_list;
    $this->data['status_filter'] = $status_filter;
    $this->data['get_all']       = $this->Cart_model->get_all($status_query);

    $this->load->view('back/transaksi/transaksi_list', $this->data);
  }

  public function create()
  {
    $this->load->model('Jam_model');
    $this->load->model('Lapangan_model');
    $this->load->model('Transaksi_detail_model');

    $this->data['title'] = 'Tambah ' . $this->data['module'] . ' Baru';
    $this->data['action'] = site_url('transaksi/create_action');

    $this->data['tanggal'] = array(
      'name'        => 'tanggal[]',
      'id'          => 'tanggal',
      'class'       => 'tanggal',
      'required'    => '',
      'autocomplete'    => 'off',
    );
    $this->data['jam_mulai'] = array(
      'name'        => 'jam_mulai[]',
      'id'          => 'jam_mulai',
      'class'       => 'jam_mulai',
      'required'    => '',
    );

    $this->data['get_all']  = $this->Lapangan_model->get_all();

    $this->load->view('back/transaksi/transaksi_add', $this->data);
  }

  public function create_action()
  {
    $this->load->helper('clean_helper');
    $this->_rules();

    if ($this->form_validation->run() == FALSE) {
      $this->create();
    } else {
      // mengambil 1 data terakhir dari tabel untuk pengecekan id_invoice
      $hasil_cek = $this->Transaksi_model->create_invoiceCode();

      // jika data tidak sama NULL atau tidak kosong atau datanya sudah ada di tabel maka buat id_invoice yang selanjutnya
      if ($hasil_cek != NULL) {
        // mengganti string dengan fungsi substr dari hasil_cek data terakhir
        $kode_akhir = substr($hasil_cek->id_invoice, 10, 6);
        // membuat id_invoice
        $kode2      = str_pad($kode_akhir + 1, 4, '0', STR_PAD_LEFT);
      }
      // jika datanya masih kosong maka buat id_invoice baru
      else {
        $kode2 = "0001";
      }

      // pembuatan tanggal
      $kode1  = date('ymd');
      /*$kode   = "J-".$kode1."-".$kode2;*/
      $kode   = "J-" . $kode1 . "-" . $kode2;

      $transaksi = array(
        'id_invoice'      => $kode,
        'customer'        => $this->input->post('customer'),
        'grand_total'     => clean($this->input->post('grand_total')),
        'bayar'           => clean($this->input->post('bayar')),
        'kembalian'       => clean($this->input->post('kembalian')),
        'catatan'         => $this->input->post('catatan'),
        'cabang'          => $this->session->userdata('cabang'),
        'company'         => $this->session->userdata('company'),
        'created_by'      => $this->session->userdata('username'),
        'created_date'    => date('Y-m-d'),
        'created_time'    => date("h:i:s")
      );
      $this->Transaksi_model->insert($transaksi);

      $this->db->select_max('id_transaksi');
      $result = $this->db->get('transaksi')->row_array();

      // menghitung total data yang dientry berdasarkan nama_barang
      $count = count($this->input->post('nama_barang'));

      // looping data yang diinput dan disimpan dalam variabel $data_detail[$i]
      for ($i = 0; $i < $count; $i++) {
        $data_detail[$i] = array(
          'transaksi_id'    => $result['id_transaksi'],
          'barang_id'       => $this->input->post('nama_barang[' . $i . ']'),
          'qty'             => $this->input->post('qty[' . $i . ']'),
          'ket'             => $this->input->post('ket[' . $i . ']'),
          'harga_jual'      => $this->input->post('harga_jual[' . $i . ']'),
          'total'           => $this->input->post('total[' . $i . ']'),
          'created_by'      => $this->session->userdata('username'),
          'created_date'    => date('Y-m-d'),
          'created_time'    => date("h:i:s")
        );
      }
      // eksekusi query INSERT dan UPDATE + looping
      foreach ($data_detail as $transaksi_detail) {
        $this->Transaksi_model->insert2($transaksi_detail);
      }

      // set pesan data berhasil dibuat
      $this->session->set_flashdata('message', '<div class="alert alert-block alert-success"><button type="button" class="close" data-dismiss="alert"><i class="ace-icon fa fa-times"></i></button>Data berhasil dibuat</div>');
      redirect(site_url('transaksi'));
    }
  }

  public function detail($id)
  {
    $this->load->model('Bank_model');

    $invoice = $this->uri->segment(4);
    $row      = $this->Cart_model->get_by_id($invoice);

    if ($row) {
      $this->data['title']              = 'Detail ' . $this->data['module'];

      $this->data['cart_finished']            = $this->Cart_model->get_cart_per_customer_finished_back($invoice)->result();
      $this->data['cart_finished_row']         = $this->Cart_model->get_cart_per_customer_finished_back($invoice)->row();
      $this->data['data_bank']                 = $this->Bank_model->get_all();
      $this->data['konfirmasi_list']           = $this->Konfirmasi_model->get_by_invoice($row->id_invoice);
      $this->data['npwp']                      = $this->Npwp_model->get_by_trans_id($invoice);
      // Deposit (Jaminan) dan Pelunasan (Sewa/Grand Total) adalah dua kewajiban TERPISAH:
      // Total yang harus dibayar tamu = Grand Total + 25% dari Grand Total (Deposit).
      $target_deposit = round($row->grand_total * 0.25);
      $total_dibayar_pelunasan = $this->Konfirmasi_model->get_total_paid_by_jenis($row->id_invoice, 'pelunasan');
      $total_dibayar_deposit   = $this->Konfirmasi_model->get_total_paid_by_jenis($row->id_invoice, 'deposit');
      $this->data['total_dibayar']             = $total_dibayar_pelunasan + $total_dibayar_deposit; // dipakai di beberapa tempat lama sbg total gabungan
      $this->data['payment_info'] = array(
        'grand_total'             => $row->grand_total,
        'target_deposit'          => $target_deposit,
        'total_dibayar_pelunasan' => $total_dibayar_pelunasan,
        'total_dibayar_deposit'   => $total_dibayar_deposit,
        'sisa_tagihan'            => max(0, $row->grand_total - $total_dibayar_pelunasan),
        'sisa_deposit'            => max(0, $target_deposit - $total_dibayar_deposit),
        'status_pelunasan'        => ($total_dibayar_pelunasan >= $row->grand_total) ? 'Lunas' : 'Belum Lunas',
        'status_deposit'          => ($total_dibayar_deposit >= $target_deposit) ? 'Lunas' : 'Belum Lunas',
        'total_keseluruhan_tagihan' => $row->grand_total + $target_deposit,
        'total_keseluruhan_dibayar' => $total_dibayar_pelunasan + $total_dibayar_deposit,
      );
      $this->data['payment_info']['sisa_keseluruhan'] = max(0, $this->data['payment_info']['total_keseluruhan_tagihan'] - $this->data['payment_info']['total_keseluruhan_dibayar']);
      $this->data['row']                       = $row;

      // Dipakai untuk menampilkan/menyembunyikan tombol Reschedule (maks 1x & minimal H-14)
      $this->load->model('Transaksi_detail_model');
      $details_for_reschedule_check = $this->Transaksi_detail_model->get_by_trans_id($invoice);
      $this->data['bisa_reschedule'] = $this->_cek_reschedule_allowed($invoice, $details_for_reschedule_check)['allowed'];

      $this->load->view('back/transaksi/transaksi_detail', $this->data);
    } else {
      $this->session->set_flashdata('message', '<div class="alert alert-warning alert">Data tidak ditemukan</div>');
      redirect(site_url('admin/transaksi'));
    }
  }

  public function print($id)
  {
    $row = $this->Transaksi_model->get_by_id($id);

    if (!$row) {
      $this->session->set_flashdata('message', "<script>alert('Data tidak ditemukan');</script>");
      redirect(site_url('transaksi'));
    } elseif ($this->ion_auth->is_superadmin() && $this->session->userdata('company') != $row->company) {
      $this->session->set_flashdata('message', "<script>alert('Data tidak ditemukan');</script>");
      redirect(site_url('transaksi'));
    } elseif ($this->ion_auth->is_admin() && $this->session->userdata('cabang') != $row->cabang) {
      $this->session->set_flashdata('message', "<script>alert('Data tidak ditemukan');</script>");
      redirect(site_url('transaksi'));
    } else {
      if ($this->ion_auth->is_superadmin()) {
        $data['cek_data']   = $this->Transaksi_model->get_by_id_print_superadmin($id);
        $data['transaksi_detail'] = $this->Transaksi_model->get_by_id_print_detail_superadmin($id);
      } elseif ($this->ion_auth->is_admin()) {
        $data['cek_data']   = $this->Transaksi_model->get_by_id_print_admin($id);
        $data['transaksi_detail'] = $this->Transaksi_model->get_by_id_print_detail_admin($id);
      }

      if ($data['cek_data']) {
        $this->load->view('back/transaksi/print', $data);
      } else {
        $this->session->set_flashdata('message', "<script>alert('Data tidak ditemukan');</script>");
        redirect(site_url('transaksi'));
      }
    }
  }

  // Redirect balik ke halaman asal (mempertahankan filter status/tab yang lagi aktif
  // di daftar transaksi), supaya transaksi yang baru diubah statusnya nggak "hilang"
  // gara-gara ke-reset ke tab default. Fallback ke daftar transaksi polos kalau
  // referer nggak ada / bukan dari aplikasi ini.
  private function _redirect_back($fallback = 'admin/transaksi')
  {
    $referer = $this->input->server('HTTP_REFERER');
    if ($referer && strpos($referer, base_url()) === 0) {
      redirect($referer);
    } else {
      redirect(site_url($fallback));
    }
  }

  // Menandai transaksi sudah Lunas Pembayaran (lunas penuh) - status 4
  public function set_lunas($id)
  {
    $row = $this->Cart_model->get_by_id($id);

    if ($row) {
      // Wajib ada konfirmasi kategori PELUNASAN dengan bukti transfer sebelum
      // transaksi boleh ditandai Lunas Pembayaran.
      $konfirmasi = $this->Konfirmasi_model->get_latest_by_invoice($row->id_invoice, 'pelunasan');
      if (!$konfirmasi || empty($konfirmasi->bukti_transfer)) {
        $this->session->set_flashdata('message', '<div class="alert alert-warning alert">Transaksi belum memiliki bukti pembayaran Pelunasan. Tidak bisa di-set LUNAS PEMBAYARAN.</div>');
        $this->_redirect_back();
        return;
      }

      $this->db->where('id_trans', $id);
      $this->db->update('transaksi', array(
        'status'      =>  '4',
      ));

      // Redirect ke tab "Lunas Pembayaran" (bukan tab asal), sama alasannya
      // dengan set_lunas_deposit di atas.
      $this->session->set_flashdata('message', '<div class="alert alert-success alert">Transaksi berhasil dinyatakan LUNAS PEMBAYARAN. Transaksi otomatis pindah ke tab "Lunas Pembayaran".</div>');
      redirect(site_url('admin/transaksi') . '?status=4');
    }
    // Jika data tidak ada
    else {
      $this->session->set_flashdata('message', '<div class="alert alert-warning alert">Transaksi tidak ditemukan</div>');
      $this->_redirect_back();
    }
  }

  // Menandai transaksi sudah Lunas Deposit (baru bayar sebagian) - status 2
  public function set_lunas_deposit($id)
  {
    $row = $this->Cart_model->get_by_id($id);

    if ($row) {
      // Wajib ada konfirmasi kategori DEPOSIT dengan bukti transfer sebelum
      // transaksi boleh ditandai Lunas Deposit.
      $konfirmasi = $this->Konfirmasi_model->get_latest_by_invoice($row->id_invoice, 'deposit');
      if (!$konfirmasi || empty($konfirmasi->bukti_transfer)) {
        $this->session->set_flashdata('message', '<div class="alert alert-warning alert">Transaksi belum memiliki bukti pembayaran Deposit. Tidak bisa di-set LUNAS DEPOSIT.</div>');
        $this->_redirect_back();
        return;
      }

      $this->db->where('id_trans', $id);
      $this->db->update('transaksi', array(
        'status'      =>  '2',
      ));

      // Redirect ke tab "Lunas Deposit" (bukan tab asal), karena transaksi ini
      // barusan PINDAH status jadi 2 - kalau balik ke tab lama (mis. "Belum Lunas")
      // baris ini otomatis ketutup filter dan keliatan kayak "hilang".
      $this->session->set_flashdata('message', '<div class="alert alert-success alert">Transaksi berhasil dinyatakan LUNAS DEPOSIT. Transaksi otomatis pindah ke tab "Lunas Deposit".</div>');
      redirect(site_url('admin/transaksi') . '?status=2');
    } else {
      $this->session->set_flashdata('message', '<div class="alert alert-warning alert">Transaksi tidak ditemukan</div>');
      $this->_redirect_back();
    }
  }

  // Form input refund deposit (admin isi nominal & catatan sesuai SOP kampus secara manual)
  public function refund_deposit($id)
  {
    $row = $this->Cart_model->get_by_id($id);

    if (!$row) {
      $this->session->set_flashdata('message', '<div class="alert alert-warning alert">Transaksi tidak ditemukan</div>');
      redirect(site_url('admin/transaksi'));
      return;
    }

    $this->data['title']  = 'Refund Deposit';
    $this->data['row']    = $row;
    // Deposit (Jaminan) dan Pelunasan (Sewa/Grand Total) adalah dua kewajiban TERPISAH -
    // ditampilkan terpisah supaya admin tahu persis komposisi dana yang sudah masuk saat
    // menentukan nominal refund sesuai SOP.
    $target_deposit_rd = round($row->grand_total * 0.25);
    $total_dibayar_pelunasan_rd = $this->Konfirmasi_model->get_total_paid_by_jenis($row->id_invoice, 'pelunasan');
    $total_dibayar_deposit_rd   = $this->Konfirmasi_model->get_total_paid_by_jenis($row->id_invoice, 'deposit');
    $this->data['total_dibayar'] = $total_dibayar_pelunasan_rd + $total_dibayar_deposit_rd;
    $this->data['payment_info'] = array(
      'grand_total'             => $row->grand_total,
      'target_deposit'          => $target_deposit_rd,
      'total_dibayar_pelunasan' => $total_dibayar_pelunasan_rd,
      'total_dibayar_deposit'   => $total_dibayar_deposit_rd,
      'sisa_tagihan'            => max(0, $row->grand_total - $total_dibayar_pelunasan_rd),
      'sisa_deposit'            => max(0, $target_deposit_rd - $total_dibayar_deposit_rd),
      'status_pelunasan'        => ($total_dibayar_pelunasan_rd >= $row->grand_total) ? 'Lunas' : 'Belum Lunas',
      'status_deposit'          => ($total_dibayar_deposit_rd >= $target_deposit_rd) ? 'Lunas' : 'Belum Lunas',
    );

    $this->load->view('back/transaksi/refund_deposit', $this->data);
  }

  public function refund_deposit_action()
  {
    $id = $this->input->post('id_trans');
    $row = $this->Cart_model->get_by_id($id);

    if (!$row) {
      $this->session->set_flashdata('message', '<div class="alert alert-warning alert">Transaksi tidak ditemukan</div>');
      redirect(site_url('admin/transaksi'));
      return;
    }

    $this->db->where('id_trans', $id);
    $this->db->update('transaksi', array(
      'status'        => '5', // 5 = Refund
      'refund_amount' => $this->input->post('refund_amount'),
      'refund_note'   => $this->input->post('refund_note'),
      'refund_date'   => date('Y-m-d H:i:s'),
      'refund_by'     => $this->session->userdata('username'),
    ));

    $this->session->set_flashdata('message', '<div class="alert alert-success alert">Refund deposit berhasil dicatat</div>');
    redirect(site_url('admin/transaksi'));
  }

  // Halaman "Riwayat Refund" - daftar semua transaksi yang sudah di-refund (status 5),
  // beserta nominal, catatan, tanggal, dan admin yang memprosesnya.
  public function refund_history()
  {
    $this->data['title']    = 'Riwayat Refund';
    $this->data['get_all']  = $this->Cart_model->get_refund_history();

    $this->load->view('back/transaksi/refund_history', $this->data);
  }

  // Form pembatalan booking oleh admin. Alasan pembatalan WAJIB diisi.
  public function cancel($id)
  {
    $row = $this->Cart_model->get_by_id($id);

    if (!$row) {
      $this->session->set_flashdata('message', '<div class="alert alert-warning alert">Transaksi tidak ditemukan</div>');
      redirect(site_url('admin/transaksi'));
      return;
    }

    if (in_array($row->status, array('5', '6'))) {
      $this->session->set_flashdata('message', '<div class="alert alert-warning alert">Transaksi ini sudah ' . (self::$status_list[$row->status]) . ', tidak bisa dibatalkan lagi.</div>');
      $this->_redirect_back();
      return;
    }

    $this->data['title']  = 'Batalkan Booking';
    $this->data['row']    = $row;

    $this->load->view('back/transaksi/cancel_booking', $this->data);
  }

  public function cancel_action()
  {
    $id  = $this->input->post('id_trans');
    $row = $this->Cart_model->get_by_id($id);

    if (!$row) {
      $this->session->set_flashdata('message', '<div class="alert alert-warning alert">Transaksi tidak ditemukan</div>');
      redirect(site_url('admin/transaksi'));
      return;
    }

    if (in_array($row->status, array('5', '6'))) {
      $this->session->set_flashdata('message', '<div class="alert alert-warning alert">Transaksi ini sudah ' . (self::$status_list[$row->status]) . ', tidak bisa dibatalkan lagi.</div>');
      redirect(site_url('admin/transaksi'));
      return;
    }

    // Alasan pembatalan wajib diisi
    $this->form_validation->set_rules('cancel_note', 'Alasan Pembatalan', 'required|trim');
    $this->form_validation->set_message('required', '{field} wajib diisi');

    if ($this->form_validation->run() == FALSE) {
      $this->session->set_flashdata('message', '<div class="alert alert-danger alert">Alasan pembatalan wajib diisi.</div>');
      redirect(site_url('admin/transaksi/cancel/') . $id);
      return;
    }

    $this->db->where('id_trans', $id);
    $update_data = array('status' => '6'); // 6 = Dibatalkan
    // Kolom cancel_note/cancel_date/cancel_by baru ada setelah migration
    // db/migration_2026_08_update_2.sql dijalankan. Kalau belum, tetap update status
    // saja supaya fitur pembatalan tidak fatal error - tapi catatan alasannya tidak
    // akan tersimpan sampai migration dijalankan.
    if ($this->db->field_exists('cancel_note', 'transaksi')) {
      $update_data['cancel_note'] = $this->input->post('cancel_note');
      $update_data['cancel_date'] = date('Y-m-d H:i:s');
      $update_data['cancel_by']   = $this->session->userdata('username');
    } else {
      log_message('error', 'Kolom cancel_note/cancel_date/cancel_by belum ada di tabel transaksi - jalankan migration db/migration_2026_08_update_2.sql. Alasan pembatalan tidak tersimpan.');
    }
    $this->db->update('transaksi', $update_data);

    $this->session->set_flashdata('message', '<div class="alert alert-success alert">Booking berhasil dibatalkan.</div>');
    redirect(site_url('admin/transaksi') . '?status=6');
  }

  // Cek apakah booking ini masih boleh di-reschedule, sesuai kebijakan:
  // - Reschedule cuma boleh dilakukan 1 KALI SAJA per booking (dicek dari riwayat log).
  // - Reschedule cuma boleh diajukan kalau tanggal booking (yang paling dekat, kalau ada
  //   beberapa baris venue) masih tersisa MINIMAL 2 minggu (H-14) dari hari ini. Kalau
  //   tanggal booking sudah kurang dari 2 minggu lagi, reschedule ditolak.
  // Mengembalikan array('allowed' => bool, 'reason' => string|null, 'earliest_date' => string|null)
  private function _cek_reschedule_allowed($id_trans, $details)
  {
    // Sudah pernah di-reschedule sebelumnya?
    if ($this->db->table_exists('transaksi_reschedule_log')) {
      $jumlah_reschedule = $this->db->where('trans_id', $id_trans)->count_all_results('transaksi_reschedule_log');
      if ($jumlah_reschedule > 0) {
        return array('allowed' => false, 'reason' => 'Booking ini sudah pernah di-reschedule sebelumnya. Reschedule hanya bisa dilakukan maksimal 1 kali per booking.', 'earliest_date' => null);
      }
    }

    // Cari tanggal booking paling dekat di antara baris venue (bukan addon)
    $earliest_date = null;
    foreach ($details as $d) {
      if ($d->is_addon == '1') continue;
      if ($earliest_date === null || $d->tanggal < $earliest_date) {
        $earliest_date = $d->tanggal;
      }
    }

    if ($earliest_date !== null) {
      $selisih_hari = (strtotime($earliest_date) - strtotime(date('Y-m-d'))) / 86400;
      if ($selisih_hari < 14) {
        return array('allowed' => false, 'reason' => 'Reschedule hanya bisa diajukan maksimal 2 minggu (H-14) sebelum tanggal booking. Tanggal booking (' . date('d-m-Y', strtotime($earliest_date)) . ') sudah terlalu dekat untuk di-reschedule.', 'earliest_date' => $earliest_date);
      }
    }

    return array('allowed' => true, 'reason' => null, 'earliest_date' => $earliest_date);
  }

  // Form reschedule (ubah tanggal, jam, dan/atau lapangan) booking yang sudah ada.
  // Hanya baris venue (bukan addon) yang bisa di-reschedule - addon dipakai lewat
  // Jumlah bukan slot jam, jadi tidak relevan untuk dijadwal ulang.
  public function reschedule($id)
  {
    $this->load->model('Transaksi_detail_model');
    $this->load->model('Lapangan_model');
    $this->load->model('Jam_model');

    $row = $this->Cart_model->get_by_id($id);

    if (!$row) {
      $this->session->set_flashdata('message', '<div class="alert alert-warning alert">Transaksi tidak ditemukan</div>');
      redirect(site_url('admin/transaksi'));
      return;
    }

    if (in_array($row->status, array('5', '6'))) {
      $this->session->set_flashdata('message', '<div class="alert alert-warning alert">Transaksi ini sudah ' . (self::$status_list[$row->status]) . ', tidak bisa di-reschedule.</div>');
      $this->_redirect_back();
      return;
    }

    $details = $this->Transaksi_detail_model->get_by_trans_id($id);

    $cek = $this->_cek_reschedule_allowed($id, $details);
    if (!$cek['allowed']) {
      $this->session->set_flashdata('message', '<div class="alert alert-warning alert">' . $cek['reason'] . '</div>');
      redirect(site_url('admin/transaksi/detail/') . $id);
      return;
    }

    $this->data['title']    = 'Reschedule Booking';
    $this->data['row']      = $row;
    $this->data['details']  = $details;
    // Hanya lapangan venue (bukan addon) yang bisa dipilih sebagai tujuan reschedule
    $this->data['lapangan_list'] = $this->Lapangan_model->get_all_non_addon();
    $this->data['jam_list']      = $this->Jam_model->get();

    $this->load->view('back/transaksi/reschedule', $this->data);
  }

  public function reschedule_action()
  {
    $this->load->model('Transaksi_detail_model');

    $id_trans = $this->input->post('id_trans');
    $row      = $this->Cart_model->get_by_id($id_trans);

    if (!$row) {
      $this->session->set_flashdata('message', '<div class="alert alert-warning alert">Transaksi tidak ditemukan</div>');
      redirect(site_url('admin/transaksi'));
      return;
    }

    // Cek ulang di server (jangan cuma percaya form) - jaga-jaga kalau ada yang buka 2 tab
    // atau submit ulang form lama setelah reschedule pertama berhasil.
    $details_lama = $this->Transaksi_detail_model->get_by_trans_id($id_trans);
    $cek = $this->_cek_reschedule_allowed($id_trans, $details_lama);
    if (!$cek['allowed']) {
      $this->session->set_flashdata('message', '<div class="alert alert-warning alert">' . $cek['reason'] . '</div>');
      redirect(site_url('admin/transaksi/detail/') . $id_trans);
      return;
    }

    $id_transdet_arr = $this->input->post('id_transdet');
    $lapangan_arr    = $this->input->post('lapangan_id');
    $tanggal_arr     = $this->input->post('tanggal');
    $jam_mulai_arr   = $this->input->post('jam_mulai');
    $durasi_arr      = $this->input->post('durasi');

    if (empty($id_transdet_arr)) {
      $this->session->set_flashdata('message', '<div class="alert alert-warning alert">Tidak ada baris booking untuk dijadwal ulang.</div>');
      redirect(site_url('admin/transaksi/reschedule/') . $id_trans);
      return;
    }

    $count       = count($id_transdet_arr);
    $log_entries = array();

    for ($i = 0; $i < $count; $i++) {
      $id_transdet      = $id_transdet_arr[$i];
      $lapangan_id_baru = $lapangan_arr[$i];
      $tanggal_baru     = $tanggal_arr[$i];
      $jam_mulai_baru   = $jam_mulai_arr[$i];
      $durasi_baru      = max(1, intval($durasi_arr[$i]));

      $old = $this->db->where('id_transdet', $id_transdet)->get('transaksi_detail')->row();
      if (!$old) {
        continue;
      }

      // Tanggal baru maksimal 3 bulan setelah tanggal yang sedang terbooking (tanggal lama)
      $tanggal_maks = date('Y-m-d', strtotime('+3 months', strtotime($old->tanggal)));
      if ($tanggal_baru > $tanggal_maks) {
        $this->session->set_flashdata('message', '<div class="alert alert-danger alert">Tanggal baru untuk salah satu baris booking melebihi batas maksimal 3 bulan setelah tanggal booking saat ini (' . date('d-m-Y', strtotime($old->tanggal)) . '). Tanggal maksimal yang boleh dipilih: ' . date('d-m-Y', strtotime($tanggal_maks)) . '.</div>');
        redirect(site_url('admin/transaksi/reschedule/') . $id_trans);
        return;
      }

      // Cek bentrok jadwal baru dengan booking aktif lain (kecuali baris ini sendiri)
      if ($this->Transaksi_detail_model->is_slot_conflict($lapangan_id_baru, $tanggal_baru, $jam_mulai_baru, $durasi_baru, $id_trans, $id_transdet)) {
        $this->session->set_flashdata('message', '<div class="alert alert-danger alert">Jadwal baru bentrok dengan booking aktif lain di venue &amp; tanggal tersebut. Reschedule dibatalkan, silakan pilih jadwal lain.</div>');
        redirect(site_url('admin/transaksi/reschedule/') . $id_trans);
        return;
      }

      $jam_selesai_baru = date('H:i:s', strtotime($jam_mulai_baru) + ($durasi_baru * 3600));

      // Kalau venue diganti, harga ikut harga lapangan yang baru
      $lapangan_row     = $this->db->where('id_lapangan', $lapangan_id_baru)->get('lapangan')->row();
      $harga_jual_baru  = $lapangan_row ? $lapangan_row->harga : $old->harga_jual;
      $total_baru       = $harga_jual_baru * $durasi_baru;

      $this->db->where('id_transdet', $id_transdet);
      $this->db->update('transaksi_detail', array(
        'lapangan_id'   => $lapangan_id_baru,
        'tanggal'       => $tanggal_baru,
        'jam_mulai'     => $jam_mulai_baru,
        'durasi'        => $durasi_baru,
        'jam_selesai'   => $jam_selesai_baru,
        'harga_jual'    => $harga_jual_baru,
        'total'         => $total_baru,
      ));

      $log_entries[] = array(
        'trans_id'          => $id_trans,
        'transdet_id'       => $id_transdet,
        'lapangan_id_lama'  => $old->lapangan_id,
        'tanggal_lama'      => $old->tanggal,
        'jam_mulai_lama'    => $old->jam_mulai,
        'durasi_lama'       => $old->durasi,
        'lapangan_id_baru'  => $lapangan_id_baru,
        'tanggal_baru'      => $tanggal_baru,
        'jam_mulai_baru'    => $jam_mulai_baru,
        'durasi_baru'       => $durasi_baru,
        'created_by'        => $this->session->userdata('username'),
        'created_at'        => date('Y-m-d H:i:s'),
      );
    }

    // Subtotal & grand_total transaksi bisa berubah kalau venue/durasi berubah - hitung ulang.
    // Diskon member sudah tidak dipakai lagi (selalu 0).
    $this->db->select_sum('total');
    $this->db->where('trans_id', $id_trans);
    $sum           = $this->db->get('transaksi_detail')->row();
    $subtotal_baru = ($sum && $sum->total !== null) ? floatval($sum->total) : 0;

    $this->db->where('id_trans', $id_trans);
    $this->db->update('transaksi', array(
      'subtotal'    => $subtotal_baru,
      'grand_total' => $subtotal_baru,
    ));

    if (!empty($log_entries)) {
      // Kalau migration db/migration_2026_08_update_2.sql belum dijalankan, tabel log
      // ini belum ada. Reschedule tetap harus berhasil (data booking sudah terupdate
      // di atas) - logging riwayat cukup di-skip dengan aman, jangan sampai fatal error.
      if ($this->db->table_exists('transaksi_reschedule_log')) {
        $this->db->insert_batch('transaksi_reschedule_log', $log_entries);
      } else {
        log_message('error', 'Tabel transaksi_reschedule_log belum ada - jalankan migration db/migration_2026_08_update_2.sql. Riwayat reschedule tidak tercatat.');
      }
    }

    $this->session->set_flashdata('message', '<div class="alert alert-success alert">Reschedule booking berhasil disimpan.</div>');
    redirect(site_url('admin/transaksi/detail/') . $id_trans);
  }

  public function getJamMulai()
  {
    $tanggal = $this->input->post('tanggal');
    $lapangan_id = $this->input->post('lapangan_id');

    if ($tanggal === FALSE || $lapangan_id === FALSE) {
      echo json_encode(array());
      die();
    }

    $list_jam_mulai_terpakai = $this->Transaksi_detail_model->get_jam_mulai_terpakai($tanggal, $lapangan_id);

    $list_jam_mulai_terpakai_arr = array();
    foreach ($list_jam_mulai_terpakai as $a_jam) {

      if (intval($a_jam->durasi) > 1) {
        $list_jam_range = $this->Jam_model->get_jam_range($a_jam->jam_mulai, $a_jam->jam_selesai);
        foreach ($list_jam_range as $a_jam_from_range) {
          if (!in_array($a_jam_from_range->jam, $list_jam_mulai_terpakai_arr))
            array_push($list_jam_mulai_terpakai_arr, $a_jam_from_range->jam);
        }
      } else {
        if (!in_array($a_jam->jam_mulai, $list_jam_mulai_terpakai_arr))
          array_push($list_jam_mulai_terpakai_arr, $a_jam->jam_mulai);
      }
    }

    $list_jam = $this->Jam_model->get();

    $list_jam_arr = array();
    foreach ($list_jam as $a_jam) {
      array_push($list_jam_arr, $a_jam->jam);
    }

    $result = array();

    foreach ($list_jam_arr as $a_jam) {
      if (!in_array($a_jam, $list_jam_mulai_terpakai_arr)) {
        $a_jam_row = new stdClass();
        $a_jam_row->durasi = '1';
        $a_jam_row->jam_mulai = $a_jam;

        array_push($result, $a_jam_row);
      }
    }

    echo json_encode($result);
  }

  // public function print($id)
  // {
  //   $this->load->helper('tgl_indo');
  //   $this->load->model('Company_model');
  //
  //   if($data['cek_data'])
  //   {
  //     Include the main TCPDF library (search for installation path).
  //     require_once('assets/plugins/tcpdf/tcpdf.php');
  //
  //     // create new PDF document
  //     $pdf = new TCPDF('P', 'mm', array('58','48'), true, 'UTF-8', false);
  //     // $pdf = new TCPDF('L', PDF_UNIT, PDF_PAGE_FORMAT, true, 'UTF-8', false);
  //
  //     // set document information
  //     $pdf->SetCreator('AzmiColeJr');
  //     $pdf->SetAuthor('AzmiColeJr');
  //     $pdf->SetTitle('Print '.$row->id_invoice);
  //
  //     // Set font
  //     // dejavusans is a UTF-8 Unicode font, if you only need to
  //     // print standard ASCII chars, you can use core fonts like
  //     // helvetica or times to reduce file size.
  //     $pdf->SetFont('helvetica', '', 5, '', true);
  //
  //     // remove default header/footer
  //     $pdf->setPrintHeader(false);
  //     $pdf->setPrintFooter(false);
  //
  //     // set margins
  //     $pdf->SetMargins('10', '10', '100');
  //
  //     // set auto page breaks
  //     $pdf->SetAutoPageBreak(TRUE, PDF_MARGIN_BOTTOM);
  //
  //     // set image scale factor
  //     $pdf->setImageScale(PDF_IMAGE_SCALE_RATIO);
  //
  //     // set some language-dependent strings (optional)
  //     if (@file_exists(dirname(__FILE__).'/lang/eng.php')) {
  //         require_once(dirname(__FILE__).'/lang/eng.php');
  //         $pdf->setLanguageArray($l);
  //     }
  //
  //     // Add a page
  //     // This method has several options, check the source code documentation for more information.
  //     $pdf->AddPage();
  //
  //     // set text shadow effect
  //     $pdf->setTextShadow(array('enabled'=>true, 'depth_w'=>0.2, 'depth_h'=>0.2, 'color'=>array(196,196,196), 'opacity'=>1, 'blend_mode'=>'Normal'));
  //
  //     // Set some content to print
  //     $html = $this->load->view('back/transaksi/print', $data, true);;
  //
  //     // Print text using writeHTMLCell()
  //     $pdf->writeHTMLCell(0, 0, '', '', $html, 0, 1, 0, true, '', true);
  //
  //     // ---------------------------------------------------------
  //
  //     // Close and output PDF document
  //     // This method has several options, check the source code documentation for more information.
  //     $pdf->Output('Print '.$row->id_invoice.'.pdf', 'I');
  //   }
  //     else
  //     {
  //       $this->session->set_flashdata('message', "<script>alert('Data tidak ditemukan');</script>");
  //       redirect(site_url('transaksi'));
  //     }
  // }

  public function update_diskon($id)
  {
    $this->db->select('id, harga');
    $this->db->where('id', '1');
    $row = $this->db->get('diskon')->row();

    $this->data['diskon'] = $row;

    if ($row) {
      $this->data['title']          = 'Ubah Data Diskon Member';
      $this->data['action']         = site_url('admin/transaksi/update_diskon_action');
      $this->data['button_submit']  = 'Simpan';
      $this->data['button_reset']   = 'Reset';

      $this->data['id'] = array(
        'name'  => 'id',
        'id'    => 'id',
        'type'  => 'hidden',
      );

      $this->data['harga'] = array(
        'name'  => 'harga',
        'id'    => 'harga',
        'type'  => 'number',
        'class' => 'form-control',
      );

      $this->load->view('back/transaksi/update_diskon', $this->data);
    } else {
      $this->session->set_flashdata('message', '<div class="alert alert-warning alert">Data tidak ditemukan</div>');
      redirect(site_url('admin/transaksi/update_diskon/1'));
    }
  }

  public function update_diskon_action()
  {
    $this->form_validation->set_rules('harga', 'Diskon Member', 'required');

    if ($this->form_validation->run() == FALSE) {
      $this->update($this->input->post('id'));
    } else {
      $data = array(
        'harga'   => $this->input->post('harga'),
      );

      $this->db->where('id', $this->input->post('id'));
      $this->db->update('diskon', $data);

      $this->session->set_flashdata('message', '<div class="alert alert-success alert">Edit Data Berhasil</div>');
      redirect(site_url('admin/transaksi/update_diskon/1'));
    }
  }

  public function _rules()
  {
    $this->form_validation->set_rules('nama_barang[]', 'Nama Barang', 'required');
    $this->form_validation->set_rules('bayar', 'Bayar', 'required');
    $this->form_validation->set_rules('kembalian', 'Kembalian', 'required');
    // set pesan form validasi error
    $this->form_validation->set_message('required', '{field} wajib diisi');

    $this->form_validation->set_rules('id_transaksi', 'id_transaksi', 'trim');
    $this->form_validation->set_error_delimiters('<div class="alert alert-danger alert">', '</div>');
  }
}
