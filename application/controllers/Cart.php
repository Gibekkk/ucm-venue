<?php
defined('BASEPATH') or exit('No direct script access allowed');

require_once FCPATH . 'vendor/autoload.php';

use Spipu\Html2Pdf\Html2Pdf;

class Cart extends CI_Controller
{

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

        // No longer require login - guests can use cart too
        // Login check removed to support guest bookings
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

        // ambil nilai diskon
        $this->db->select('harga');
        $this->db->where('id', '1');
        $query = $this->db->get('diskon')->row_array();
        $this->data['diskon'] = $query;

        // ambil data keranjang
        $this->data['cart_data']              = $this->Cart_model->get_cart_per_customer()->result();
        $this->data['cek_keranjang']        = $this->Cart_model->get_cart_per_customer()->row();
        
        // PERUBAHAN TAHAP 2: Mengambil data addon untuk ditampilkan di halaman keranjang
        $this->data['addons']                 = $this->Lapangan_model->get_all_addons();
        
        // ambil data customer (jika login) atau persiapkan form guest
        if ($this->ion_auth->logged_in()) {
            $this->data['customer_data']        = $this->Cart_model->get_data_customer();
            $this->data['is_guest'] = false;
        } else {
            $this->data['customer_data'] = null;
            $this->data['is_guest'] = true;
            
            // Prepare guest form data
            $this->data['ambil_provinsi'] = $this->Wilayah_model->get_provinsi();
        }

        $this->load->view('front/cart/body', $this->data);
    }

    public function buy($id)
    {
        // ambil data produk
        $row = $this->Lapangan_model->get_by_id($id);

        // cek id produk
        if ($row) {
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
                    'user_id'               => $this->ion_auth->logged_in() ? $this->session->userdata('user_id') : NULL,
                    'session_id'      => !$this->ion_auth->logged_in() ? session_id() : NULL,
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

        $this->Cart_model->kosongkan_keranjang($id_trans);

        $this->session->set_flashdata('message', '<div class="alert alert-block alert-success"><i class="ace-icon fa fa-bullhorn green"></i> Keranjang Anda telah dikosongkan</div>');

        redirect(site_url('cart'));
    }

    public function checkout()
    {
        $count = count($this->input->post('lapangan'));
        for ($i = 0; $i < $count; $i++) {
            $data_detail[$i] = array(
                'id_transdet'   => $this->input->post('id_transdet[' . $i . ']'),
            'tanggal'       => $this->input->post('tanggal[' . $i . ']'),
            'jam_mulai'     => $this->input->post('jam_mulai[' . $i . ']'),
            'durasi'        => $this->input->post('durasi[' . $i . ']'),
            'harga_jual'    => $this->input->post('harga_jual[' . $i . ']'),
            'jam_selesai'   => $this->input->post('jam_mulai[' . $i . ']') . ' ' . $this->input->post('durasi[' . $i . ']') . ":00:00",
            'total'             => $this->input->post('harga_jual[' . $i . ']') * $this->input->post('durasi[' . $i . ']'),
            );

            $this->db->update_batch('transaksi_detail', $data_detail, 'id_transdet');
        }

        // Check if user is logged in or guest
        $is_logged_in = $this->ion_auth->logged_in();
        
        if ($is_logged_in && $this->session->userdata('usertype') == "3") {
            // ambil nilai diskon
            $this->db->select('harga');
            $this->db->where('id', '1');
            $query = $this->db->get('diskon')->row();
            $diskon = $query->harga;
        } else {
            $diskon = '0';
        }

        $this->db->select_sum('total');
        $this->db->join('transaksi_detail', 'transaksi.id_trans = transaksi_detail.trans_id');
        $this->db->where('id_trans', $this->input->post('id_trans'));
        
        if ($is_logged_in) {
            $this->db->where('user_id', $this->session->userdata('user_id'));
        } else {
            $this->db->where('session_id', session_id());
        }
        
        $query = $this->db->get('transaksi')->row();

        $gtotal = $query->total - $diskon;

        // Prepare transaction update data
        $transaksi_data = array(
            'subtotal'      =>  $query->total,
            'diskon'            =>  $diskon,
            'grand_total'   =>  $gtotal,
            'deadline'      =>  date('Y-m-d H:i:s', strtotime('1 hour')),
            'catatan'     => $this->input->post('catatan'),
            'status'            =>  '0',
        );
        
        // Add guest information if not logged in
        if (!$is_logged_in) {
            $transaksi_data['guest_name'] = $this->input->post('guest_name');
            $transaksi_data['guest_email'] = $this->input->post('guest_email');
            $transaksi_data['guest_phone'] = $this->input->post('guest_phone');
            $transaksi_data['guest_address'] = $this->input->post('guest_address');
            $transaksi_data['guest_province_id'] = $this->input->post('guest_province_id');
            $transaksi_data['guest_city_id'] = $this->input->post('guest_city_id');
        }

        $this->db->where('id_trans', $this->input->post('id_trans'));
        
        if ($is_logged_in) {
            $this->db->where('user_id', $this->session->userdata('user_id'));
        } else {
            $this->db->where('session_id', session_id());
        }
        
        $this->db->update('transaksi', $transaksi_data);
        
        // Send confirmation email
        $this->load->helper('email_helper');
        $trans_id = $this->input->post('id_trans');
        
        // Get complete booking data for email
        $booking_details = $this->Cart_model->get_cart_per_customer_finished($trans_id);
        $booking_row = $booking_details->row();
        
        if ($booking_row) {
            // Determine customer email and name
            if ($is_logged_in) {
                $customer_email = $this->session->userdata('email');
                $customer_name = $this->session->userdata('username');
            } else {
                $customer_email = $this->input->post('guest_email');
                $customer_name = $this->input->post('guest_name');
            }
            
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
        $this->data['is_guest'] = !$this->session->userdata('user_id');

        $this->load->view('front/cart/finished', $this->data);
    }

    public function download_invoice($id)
    {
        $row                        = $this->Cart_model->get_by_id($id);

        // Check if user has access (either registered user or guest with matching session)
        if ($this->session->userdata('user_id')) {
            // Registered user - check user_id match
            if ($this->session->userdata('user_id') != $row->user_id) {
                $this->session->set_flashdata('message', '<div class="alert alert-danger alert">Invoice tidak ditemukan</div>');
                redirect(site_url('cart/history'));
            }
        } else {
            // Guest - check session_id match
            if (session_id() != $row->session_id) {
                $this->session->set_flashdata('message', '<div class="alert alert-danger alert">Invoice tidak ditemukan</div>');
                redirect(site_url('cart'));
            }
        }

        if ($row) {
            ob_start();

            $this->data['cart_finished']                    = $this->Cart_model->get_cart_per_customer_finished($id)->result();
            $this->data['cart_finished_row']            = $this->Cart_model->get_cart_per_customer_finished($id)->row();

            $this->data['data_bank']                                = $this->Bank_model->get_all();

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
        $row                        = $this->Cart_model->get_by_id($id);

        if ($this->session->userdata('user_id') != $row->user_id) {
            $this->session->set_flashdata('message', '<div class="alert alert-danger alert">Invoice tidak ditemukan</div>');
            redirect(site_url('cart/history'));
        } else {
            $this->data['title']                                = 'Detail Riwayat Transaksi';

            $this->data['history_detail']           = $this->Cart_model->history_detail($id)->result();
            $this->data['history_detail_row']       = $this->Cart_model->history_detail($id)->row();
            $this->data['data_bank']                                = $this->Bank_model->get_all();

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