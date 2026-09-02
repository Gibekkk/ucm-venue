<?php
defined('BASEPATH') or exit('No direct script access allowed');

require_once FCPATH . 'vendor/autoload.php';

use Spipu\Html2Pdf\Html2Pdf;

class Cart extends CI_Controller
{

    // Batas minimal jarak hari antara HARI INI dan tanggal booking (tanggal event).
    // Konvensi label "H-4" di sini dihitung INKLUSIF hari ini sebagai hari ke-1, jadi
    // kalau hari ini tanggal 28, tanggal yang tersedia paling cepat adalah tanggal 31
    // (28=hari ke-1, 29=hari ke-2, 30=hari ke-3, 31=hari ke-4) -> selisih kalender = +3 hari.
    const MIN_BOOKING_DAYS = 3;

    function __construct()
    {
        parent::__construct();

        $this->load->model('Bank_model');
        $this->load->model('Cart_model');
        $this->load->model('Company_model');
        $this->load->model('Event_model');
        $this->load->model('Kontak_model');
        $this->load->model('Jam_model');
        $this->load->model('Transaksi_detail_model');
        $this->load->model('Lapangan_model');
        $this->load->model('Wilayah_model');
        $this->load->model('Npwp_model');

        // Auto-expire booking yang belum dibayar > 3 hari sejak checkout (kecuali sudah Lunas Deposit)
        $this->Cart_model->expire_old_transactions();

        $this->data['company_data']             = $this->Company_model->get_by_company();
        $this->data['kontak']                       = $this->Kontak_model->get_all();
        $this->data['event_sidebar']            = $this->Event_model->get_all_sidebar();
        $this->data['kontak_sidebar']       = $this->Kontak_model->get_all();
        $this->data['total_cart_navbar']    = $this->Cart_model->total_cart_navbar();

        $this->load->helper('tgl_indo');
        
        // Load language helper and detect user language
        $this->load->helper('language_helper');
        $user_lang = detect_user_language();
        $this->lang->load('site', $user_lang);
        $this->data['current_lang'] = $user_lang;

        // Tidak ada sistem login/akun pelanggan sama sekali. Semua booking = tamu,
        // diidentifikasi lewat PHP session_id() biasa. Admin punya login terpisah (admin/Auth).
        $this->data['min_booking_date'] = date('Y-m-d', strtotime('+' . self::MIN_BOOKING_DAYS . ' days'));
    }

    public function index()
    {
        $this->data['title']                                        = 'Keranjang Belanja';

        $this->data['tanggal'] = array(
            'name'        => 'tanggal[]',
            'id'          => 'tanggal',
            'class'             => 'tanggal',
            'required'    => '',
            'autocomplete'    => 'off',
        );
        $this->data['jam_mulai'] = array(
            'name'        => 'jam_mulai[]',
            'id'          => 'jam_mulai',
            'class'       => 'jam_mulai',
            'required'    => '',
        );

        // Diskon member sudah tidak dipakai lagi (fitur login member dihapus), selalu 0.
        $this->data['diskon'] = array('harga' => 0);

        // ambil data keranjang (venue & addon dipisah oleh view berdasarkan is_addon)
        $this->data['cart_data']              = $this->Cart_model->get_cart_per_customer()->result();
        $this->data['cek_keranjang']        = $this->Cart_model->get_cart_per_customer()->row();

        // data addon yang tersedia untuk ditambahkan ke keranjang
        $this->data['addons']                 = $this->Lapangan_model->get_all_addons();

        // Tombol "Tambah Addon" hanya muncul jika keranjang sudah punya minimal 1 lapangan (venue, bukan addon)
        $this->data['ada_venue']              = $this->Cart_model->has_venue_in_cart();

        // Semua booking = tamu, tidak ada akun/login pelanggan.
        $this->data['customer_data'] = null;
        $this->data['ambil_provinsi'] = $this->Wilayah_model->get_provinsi();

        $this->load->view('front/cart/body', $this->data);
    }

    public function buy($id)
    {
        // ambil data produk
        $row = $this->Lapangan_model->get_by_id($id);

        // cek id produk
        if ($row) {
            // Addon hanya boleh ditambahkan kalau keranjang sudah punya minimal 1 venue
            if ($row->is_addon == 1 && !$this->Cart_model->has_venue_in_cart()) {
                $this->session->set_flashdata('message', '<div class="alert alert-danger alert">Tambahkan Venue terlebih dahulu sebelum menambahkan Addon.</div>');
                redirect(site_url('cart'));
                return;
            }

            // cek transaksi per user (logged in or guest)
            $cek_transaksi  = $this->Cart_model->cek_transaksi();
            
            if ($cek_transaksi) {
                $id_trans = $cek_transaksi->id_trans;
            }

            // cek data barang yang dibeli dan masuk ke tabel transaksi_detail
            $notransdet                 = $this->Cart_model->get_notransdet($id);

            // jika transaksi sudah ada
            if ($cek_transaksi) {
                // jika barang yang dibeli sudah ada di cart == update
                if ($notransdet) {
                    $this->index();
                }
                // jika barang yang dibeli belum ada di cart == tambahkan
                else {
                    $data2 = array(
                        'trans_id'    => $id_trans,
                        'lapangan_id' => $id,
                        'harga_jual'  => $row->harga,
                        'total'       => $row->harga,
                    );

                    $this->Cart_model->insert_detail($data2);

                    // set pesan data berhasil dibuat
                    $this->session->set_flashdata('message', '<div class="alert alert-success alert">Booking berhasil ditambahkan</div>');
                    redirect(site_url('cart'));
                }
            }
            // jika belum ada transaksi
            else {
                // mengambil 1 data terakhir dari tabel untuk pengecekan id_invoice
                $hasil_cek = $this->Cart_model->create_invoiceCode();

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
                $kode   = "J-" . $kode1 . "-" . $kode2;

                $data = array(
                    'id_invoice'      => $kode,
                    'session_id'      => session_id(),
                    'created_date'    => date('Y-m-d'),
                    'created_time'    => date("h:i:s")
                );

                // eksekusi query INSERT
                $this->Cart_model->insert($data);

                $cek_transaksi  = $this->Cart_model->cek_transaksi();

                $data2 = array(
                    'trans_id'      => $cek_transaksi->id_trans,
                    'lapangan_id' => $id,
                    'harga_jual'  => $row->harga,
                    'total'     => $row->harga,
                );

                $this->Cart_model->insert_detail($data2);

                // set pesan data berhasil dibuat
                $this->session->set_flashdata('message', '<div class="alert alert-success alert">Barang berhasil ditambahkan</div>');
                redirect(site_url('cart'));
            }
        } else {
            $this->session->set_flashdata('message', '
                <div class="alert alert-block alert-warning"><button type="button" class="close" data-dismiss="alert"><i class="ace-icon fa fa-times"></i></button>
                    <i class="ace-icon fa fa-bullhorn green"></i> Data tidak ditemukan
                </div>');
            redirect(base_url());
        }
    }

    public function delete($id)
    {
        $id = $this->uri->segment(3);

        $row            = $this->Cart_model->get_by_id_detail($id);

        if ($row) {
            $id_transdet            = $row->id_transdet;

            $this->Cart_model->delete($id_transdet);
            $this->session->set_flashdata('message', '<div class="alert alert-success alert">Booking Anda Berhasil dihapus</div>');
            redirect(site_url('cart'));
        }
        // Jika data tidak ada
        else {
            $this->session->set_flashdata('message', '<div class="alert alert-warning alert">Booking tidak ditemukan</div>');
            redirect(site_url('cart'));
        }
    }

    public function empty_cart($id_trans)
    {
        $id_trans = $this->uri->segment(3);

        // Pastikan id_trans ini benar milik session yang sedang mengakses
        $row = $this->Cart_model->get_by_id($id_trans);
        if ($row && $row->session_id == session_id()) {
            $this->Cart_model->kosongkan_keranjang($id_trans);
            $this->session->set_flashdata('message', '<div class="alert alert-block alert-success"><i class="ace-icon fa fa-bullhorn green"></i> Keranjang Anda telah dikosongkan</div>');
        } else {
            $this->session->set_flashdata('message', '<div class="alert alert-warning alert">Keranjang tidak ditemukan</div>');
        }

        redirect(site_url('cart'));
    }

    // Cek apakah jam yang diminta (jam_mulai s/d jam_mulai+durasi jam) bentrok dengan
    // booking lain yang masih aktif di venue & tanggal yang sama.
    // (Logika pengecekan bentrok sekarang ada di Transaksi_detail_model::is_slot_conflict()
    // supaya dipakai bersama dengan reschedule admin - lihat admin/Transaksi::reschedule_action)
    private function _is_slot_conflict($lapangan_id, $tanggal, $jam_mulai, $durasi, $exclude_trans_id)
    {
        return $this->Transaksi_detail_model->is_slot_conflict($lapangan_id, $tanggal, $jam_mulai, $durasi, $exclude_trans_id);
    }

    public function checkout()
    {
        $id_trans   = $this->input->post('id_trans');
        $is_addon_arr = $this->input->post('is_addon');
        $count      = count($this->input->post('lapangan'));

        $today_plus_4 = date('Y-m-d', strtotime('+' . self::MIN_BOOKING_DAYS . ' days'));

        // NPWP sekarang wajib diisi untuk semua booking (nomor + upload foto/scan).
        if (empty($this->input->post('nomor_npwp')) || empty($_FILES['npwp_file']['name'])) {
            $this->session->set_flashdata('message', '<div class="alert alert-danger alert">NPWP wajib diisi (nomor NPWP dan upload foto/scan NPWP).</div>');
            redirect(site_url('cart'));
            return;
        }

        // Proses upload file NPWP di awal (sebelum transaksi di-commit), supaya kalau upload
        // gagal (tipe file salah / kelebihan ukuran), checkout dibatalkan dan keranjang tidak berubah.
        $npwp_upload_path = './assets/images/npwp/';
        if (!is_dir($npwp_upload_path)) {
            mkdir($npwp_upload_path, 0755, true);
        }

        $npwp_upload_config['upload_path']   = $npwp_upload_path;
        $npwp_upload_config['allowed_types'] = 'jpg|jpeg|png|pdf';
        $npwp_upload_config['max_size']      = 2048;
        $npwp_upload_config['file_name']     = 'NPWP_' . $id_trans . '_' . time();

        $this->load->library('upload', $npwp_upload_config);
        $this->upload->initialize($npwp_upload_config);

        if (!$this->upload->do_upload('npwp_file')) {
            $this->session->set_flashdata('message', '<div class="alert alert-danger alert">Upload NPWP gagal: ' . strip_tags($this->upload->display_errors('', '')) . '</div>');
            redirect(site_url('cart'));
            return;
        }

        $npwp_upload_data = $this->upload->data();

        for ($i = 0; $i < $count; $i++) {
            $lapangan_id = $this->input->post('lapangan[' . $i . ']');
            $tanggal     = $this->input->post('tanggal[' . $i . ']');
            $durasi      = $this->input->post('durasi[' . $i . ']');
            $harga_jual  = $this->input->post('harga_jual[' . $i . ']');
            $id_transdet = $this->input->post('id_transdet[' . $i . ']');
            $is_addon    = isset($is_addon_arr[$i]) && $is_addon_arr[$i] == '1';

            if ($is_addon) {
                // Addon: field durasi dipakai sebagai JUMLAH, jam selalu 00:00:00
                $jam_mulai   = '00:00:00';
                $jam_selesai = '00:00:00';
            } else {
                $jam_mulai = $this->input->post('jam_mulai[' . $i . ']');

                // Validasi booking minimal H-4
                if (empty($tanggal) || $tanggal < $today_plus_4) {
                    $this->session->set_flashdata('message', '<div class="alert alert-danger alert">Booking minimal H-4 (tanggal acara paling cepat ' . date('d-m-Y', strtotime($today_plus_4)) . '). Mohon pilih tanggal lain.</div>');
                    redirect(site_url('cart'));
                    return;
                }

                // Validasi bentrok jam & venue dengan booking aktif lain
                if ($this->_is_slot_conflict($lapangan_id, $tanggal, $jam_mulai, $durasi, $id_trans)) {
                    $this->session->set_flashdata('message', '<div class="alert alert-danger alert">Maaf, jam & venue tersebut sudah dibooking pihak lain. Mohon pilih jam/tanggal lain.</div>');
                    redirect(site_url('cart'));
                    return;
                }

                $jam_selesai = date('H:i:s', strtotime($jam_mulai) + (max(1, intval($durasi)) * 3600));
            }

            $data_detail[$i] = array(
                'id_transdet'   => $id_transdet,
                'tanggal'       => $tanggal,
                'jam_mulai'     => $jam_mulai,
                'durasi'        => $durasi,
                'harga_jual'    => $harga_jual,
                'jam_selesai'   => $jam_selesai,
                'total'         => $harga_jual * $durasi,
            );
        }

        $this->db->update_batch('transaksi_detail', $data_detail, 'id_transdet');

        // Diskon member sudah tidak ada lagi (fitur login member dihapus)
        $diskon = 0;

        $this->db->select_sum('total');
        $this->db->join('transaksi_detail', 'transaksi.id_trans = transaksi_detail.trans_id');
        $this->db->where('id_trans', $id_trans);
        $this->db->where('session_id', session_id());
        $query = $this->db->get('transaksi')->row();

        $gtotal = $query->total - $diskon;

        // Deadline pembayaran: 3 hari sejak checkout, lewat itu otomatis EXPIRED
        // (kecuali sudah Lunas Deposit - lihat Cart_model::expire_old_transactions)
        $transaksi_data = array(
            'subtotal'      => $query->total,
            'diskon'        => $diskon,
            'grand_total'   => $gtotal,
            'deadline'      => date('Y-m-d H:i:s', strtotime('+3 days')),
            'catatan'       => $this->input->post('catatan'),
            'nama_acara'    => $this->input->post('nama_acara'),
            'status'        => '1', // 1 = Belum Lunas (sudah checkout, menunggu pembayaran)
            'guest_name'          => $this->input->post('guest_name'),
            'guest_email'         => $this->input->post('guest_email'),
            'guest_phone'         => $this->input->post('guest_phone'),
            'guest_address'       => $this->input->post('guest_address'),
            'guest_province_id'   => $this->input->post('guest_province_id'),
            'guest_city_id'       => $this->input->post('guest_city_id'),
        );

        $this->db->where('id_trans', $id_trans);
        $this->db->where('session_id', session_id());
        $this->db->update('transaksi', $transaksi_data);

        // Simpan data NPWP (wajib diisi, sudah divalidasi & diupload di awal fungsi ini).
        $this->Npwp_model->insert(array(
            'trans_id'      => $id_trans,
            'nomor_npwp'    => $this->input->post('nomor_npwp'),
            'npwp_image'    => 'assets/images/npwp/' . $npwp_upload_data['file_name'],
            'created_at'    => date('Y-m-d H:i:s'),
        ));

        // Send confirmation email
        $this->load->helper('email_helper');

        // Get complete booking data for email
        $booking_details = $this->Cart_model->get_cart_per_customer_finished($id_trans);
        $booking_row = $booking_details->row();

        if ($booking_row) {
            $customer_email = $this->input->post('guest_email');
            $customer_name = $this->input->post('guest_name');

            // Prepare booking data for email
            $email_data = array(
                'invoice_number' => $booking_row->id_invoice,
                'customer_name' => $customer_name,
                'customer_email' => $customer_email,
                'booking_date' => $booking_row->created_date . ' ' . $booking_row->created_time,
                'subtotal' => $booking_row->subtotal,
                'discount' => $booking_row->diskon,
                'grand_total' => $booking_row->grand_total,
                'deadline' => $booking_row->deadline,
                'notes' => $booking_row->catatan,
                'items' => array(),
                'banks' => $this->Bank_model->get_all()
            );

            // Get all booking items
            foreach ($booking_details->result() as $item) {
                $email_data['items'][] = array(
                    'venue_name' => $item->nama_lapangan,
                    'date' => $item->tanggal,
                    'start_time' => $item->jam_mulai,
                    'end_time' => $item->jam_selesai,
                    'duration' => $item->durasi,
                    'total' => $item->total
                );
            }

            // Send email
            send_booking_confirmation($email_data);
        }

        redirect(site_url('cart/finished'));
    }

    public function finished()
    {
        $this->data['title'] = 'Transaksi Selesai';

        $cart_latest = $this->Cart_model->get_cart_per_customer_latest();
        
        // Check if cart_latest exists
        if (!$cart_latest) {
            $this->session->set_flashdata('message', '<div class="alert alert-danger">Transaksi tidak ditemukan.</div>');
            redirect(site_url('cart'));
            return;
        }
        
        $this->data['cart_latest'] = $cart_latest;
        $this->data['cart_finished'] = $this->Cart_model->get_cart_per_customer_finished($cart_latest->id_trans)->result();
        $this->data['cart_finished_row'] = $this->Cart_model->get_cart_per_customer_finished($cart_latest->id_trans)->row();
        $this->data['data_bank'] = $this->Bank_model->get_all();
        $this->data['npwp'] = $this->Npwp_model->get_by_trans_id($cart_latest->id_trans);

        $this->load->view('front/cart/finished', $this->data);
    }

    public function download_invoice($id)
    {
        $row                        = $this->Cart_model->get_by_id($id);

        // Booking = tamu semua, akses invoice diverifikasi lewat session_id saja
        if (!$row || session_id() != $row->session_id) {
            $this->session->set_flashdata('message', '<div class="alert alert-danger alert">Invoice tidak ditemukan</div>');
            redirect(site_url('cart'));
            return;
        }

        if ($row) {
            ob_start();

            $this->data['cart_finished']                    = $this->Cart_model->get_cart_per_customer_finished($id)->result();
            $this->data['cart_finished_row']            = $this->Cart_model->get_cart_per_customer_finished($id)->row();

            $this->data['data_bank']                                = $this->Bank_model->get_all();
            $this->data['npwp']                                     = $this->Npwp_model->get_by_trans_id($id);

            $this->load->view('front/cart/download_invoice', $this->data);

            $html = ob_get_contents();
            $html = '<title style="font-family: freeserif">' . nl2br($html) . '</title>';
            ob_end_clean();

            $pdf = new Html2Pdf('P', 'A4', 'en', true, 'UTF-8', array(10, 0, 10, 0));
            $pdf->setDefaultFont('Arial');
            $pdf->setTestTdInOnePage(false);
            $pdf->WriteHTML($html);
            $pdf->Output('download_invoice.pdf');
        } else {
            $this->session->set_flashdata('message', "<script>alert('Data tidak ditemukan');</script>");
            redirect(site_url());
        }
    }

    public function history()
    {
        $this->data['title']                            = 'Daftar Transaksi';
        $this->data['cek_cart_history']   = $this->Cart_model->cart_history()->row();
        $this->data['cart_history']         = $this->Cart_model->cart_history()->result();

        $this->load->view('front/cart/history', $this->data);
    }

    public function history_detail($id)
    {
        $row = $this->Cart_model->get_by_id($id);

        if (!$row || session_id() != $row->session_id) {
            $this->session->set_flashdata('message', '<div class="alert alert-danger alert">Invoice tidak ditemukan</div>');
            redirect(site_url('cart/history'));
        } else {
            $this->data['title']                                = 'Detail Riwayat Transaksi';

            $this->data['history_detail']           = $this->Cart_model->get_cart_per_customer_finished($id)->result();
            $this->data['history_detail_row']       = $this->Cart_model->get_cart_per_customer_finished($id)->row();
            $this->data['data_bank']                                = $this->Bank_model->get_all();
            $this->data['npwp']                                     = $this->Npwp_model->get_by_trans_id($id);

            $this->load->view('front/cart/history_detail', $this->data);
        }
    }

    public function getJamMulai()
    {
        $tanggal = $this->input->post('tanggal');
        $lapangan_id = $this->input->post('lapangan_id');

        if ($tanggal === FALSE || $lapangan_id === FALSE) {
            echo json_encode(array());
            die();
        }

        // Jangan anggap baris milik keranjang session ini sendiri sebagai bentrok
        $cek_transaksi = $this->Cart_model->cek_transaksi();
        $exclude_trans_id = $cek_transaksi ? $cek_transaksi->id_trans : null;

        $list_jam_mulai_terpakai = $this->Transaksi_detail_model->get_jam_mulai_terpakai($tanggal, $lapangan_id, $exclude_trans_id);

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
    
    // AJAX: daftar tanggal yang SEMUA jam-nya sudah penuh terpakai booking aktif
    // untuk 1 venue tertentu -> dipakai front-end untuk disable tanggal di datepicker.
    public function getBookedDates()
    {
        $lapangan_id = $this->input->post('lapangan_id');

        if (!$lapangan_id) {
            echo json_encode(array());
            die();
        }

        $total_jam = count($this->Jam_model->get());
        if ($total_jam <= 0) {
            echo json_encode(array());
            die();
        }

        $bookings = $this->Transaksi_detail_model->get_active_bookings_for_lapangan($lapangan_id);

        // Jumlahkan jam terpakai per tanggal (asumsi antar booking aktif tidak saling
        // tumpang tindih, karena sudah dicegah saat checkout - lihat _is_slot_conflict()).
        $jam_terpakai_per_tanggal = array();
        foreach ($bookings as $b) {
            $tgl = $b->tanggal;
            if (!isset($jam_terpakai_per_tanggal[$tgl])) {
                $jam_terpakai_per_tanggal[$tgl] = 0;
            }
            $jam_terpakai_per_tanggal[$tgl] += max(1, intval($b->durasi));
        }

        $fully_booked_dates = array();
        foreach ($jam_terpakai_per_tanggal as $tgl => $jumlah_jam) {
            if ($jumlah_jam >= $total_jam) {
                $fully_booked_dates[] = $tgl;
            }
        }

        echo json_encode($fully_booked_dates);
    }

    // AJAX method for getting cities based on province
    public function pilih_kota()
    {
        $provinsi_id = $this->uri->segment(3);
        $data_kota = $this->Wilayah_model->get_kota($provinsi_id);
        
        echo '<option value="">- Pilih Kota -</option>';
        if ($data_kota) {
            foreach ($data_kota as $id_kota => $nama_kota) {
                echo '<option value="'.$id_kota.'">'.$nama_kota.'</option>';
            }
        }
    }
    
    // Guest Booking Tracking
    public function track_booking()
    {
        $this->data['title'] = 'Track Booking';
        
        if ($this->input->post('search')) {
            $email = $this->input->post('email');
            $booking_code = $this->input->post('booking_code');
            
            // Search booking by email and booking code
            $booking = $this->Cart_model->get_booking_by_email_code($email, $booking_code);
            
            if ($booking) {
                $this->data['booking_found'] = true;
                $this->data['booking'] = $booking;
                $this->data['booking_details'] = $this->Cart_model->get_booking_details($booking->id_trans);
                $this->data['npwp'] = $this->Npwp_model->get_by_trans_id($booking->id_trans);
                $this->load->model('Konfirmasi_model');
                // Deposit (Jaminan) dan Pelunasan (Sewa/Grand Total) adalah dua kewajiban
                // TERPISAH: Total yang harus dibayar tamu = Grand Total + 25% dari Grand Total
                // (Deposit). Masing-masing punya progres pembayaran & status sendiri.
                $target_deposit = round($booking->grand_total * 0.25);
                $total_dibayar_pelunasan = $this->Konfirmasi_model->get_total_paid_by_jenis($booking->id_invoice, 'pelunasan');
                $total_dibayar_deposit   = $this->Konfirmasi_model->get_total_paid_by_jenis($booking->id_invoice, 'deposit');
                $this->data['payment_info'] = array(
                    'grand_total'             => $booking->grand_total,
                    'target_deposit'          => $target_deposit,
                    'total_dibayar_pelunasan' => $total_dibayar_pelunasan,
                    'total_dibayar_deposit'   => $total_dibayar_deposit,
                    'sisa_tagihan'            => max(0, $booking->grand_total - $total_dibayar_pelunasan),
                    'sisa_deposit'            => max(0, $target_deposit - $total_dibayar_deposit),
                    'status_pelunasan'        => ($total_dibayar_pelunasan >= $booking->grand_total) ? 'Lunas' : 'Belum Lunas',
                    'status_deposit'          => ($total_dibayar_deposit >= $target_deposit) ? 'Lunas' : 'Belum Lunas',
                    'total_keseluruhan_tagihan' => $booking->grand_total + $target_deposit,
                    'total_keseluruhan_dibayar' => $total_dibayar_pelunasan + $total_dibayar_deposit,
                );
                $this->data['payment_info']['sisa_keseluruhan'] = max(0, $this->data['payment_info']['total_keseluruhan_tagihan'] - $this->data['payment_info']['total_keseluruhan_dibayar']);
                // Jenis konfirmasi yang sudah pernah disubmit (deposit/pelunasan) - dipakai untuk
                // menampilkan status "Menunggu Konfirmasi ...".
                $this->data['submitted_jenis'] = $this->Konfirmasi_model->get_submitted_jenis($booking->id_invoice);
                // Riwayat semua pembayaran yang sudah disubmit tamu untuk invoice ini, ditampilkan
                // apa adanya meskipun belum diverifikasi admin.
                $this->data['konfirmasi_list'] = $this->Konfirmasi_model->get_by_invoice($booking->id_invoice);
            } else {
                $this->session->set_flashdata('message', '<div class="alert alert-danger">Booking tidak ditemukan. Periksa kembali Email dan Kode Booking Anda.</div>');
                $this->data['booking_found'] = false;
            }
        } else {
            $this->data['booking_found'] = false;
        }
        
        $this->load->view('front/cart/track_booking', $this->data);
    }
}