<?php  if ( ! defined('BASEPATH')) exit('No direct script access allowed');

class Cart_model extends CI_Model
{
  public $table   = 'transaksi';
  public $table2  = 'transaksi_detail';
  public $id      = 'id_trans';
  public $id2     = 'id_transdet';

  public function create_invoiceCode(){
    $this->db->select("id_invoice");
    $this->db->where('MONTH(created_date)',date('m'));
    $this->db->where('YEAR(created_date)',date('Y'));
    $this->db->order_by('id_invoice','DESC');
    $this->db->limit(1);
    return $this->db->get($this->table)->row();
  }

  // BACKEND //
  function get_all()
  {
    // MODIFIKASI: Mengambil semua kolom penting dari tabel konfirmasi pembayaran
    $this->db->select('transaksi.*, users.id, users.name, users.email, 
                      konfirmasi_pembayaran.nama_pengirim, 
                      konfirmasi_pembayaran.bank_pengirim, 
                      konfirmasi_pembayaran.nominal AS nominal_konfirmasi, 
                      konfirmasi_pembayaran.bukti_transfer, 
                      konfirmasi_pembayaran.catatan AS catatan_konfirmasi, 
                      konfirmasi_pembayaran.created_at AS tgl_konfirmasi');
    $this->db->join('users', 'transaksi.user_id = users.id', 'left');
    
    // Melakukan LEFT JOIN ke tabel konfirmasi_pembayaran berdasarkan id_invoice
    $this->db->join('konfirmasi_pembayaran', 'transaksi.id_invoice = konfirmasi_pembayaran.id_invoice', 'left');
    $this->db->order_by('transaksi.id_trans', 'DESC');
    return $this->db->get($this->table)->result();
  }

  function top5_transaksi()
  {
    $this->db->select('transaksi.*, users.id, users.name, users.email');
    $this->db->join('users', 'transaksi.user_id = users.id', 'left');
    $this->db->limit(5);
    $this->db->order_by('transaksi.id_trans', 'DESC');
    return $this->db->get($this->table)->result();
  }

  function get_cart_per_customer_finished_back($id)
  {
    $this->db->select('
    lapangan.id_lapangan, lapangan.nama_lapangan,
    transaksi.id_trans, transaksi.id_invoice, transaksi.user_id, transaksi.subtotal, transaksi.diskon, transaksi.grand_total, transaksi.deadline, transaksi.status, transaksi.catatan, transaksi.created_date,
    transaksi.guest_name, transaksi.guest_email, transaksi.guest_phone, transaksi.guest_address, transaksi.guest_province_id, transaksi.guest_city_id,
    transaksi_detail.trans_id, transaksi_detail.lapangan_id, transaksi_detail.tanggal, transaksi_detail.jam_mulai, transaksi_detail.durasi, transaksi_detail.jam_selesai, transaksi_detail.harga_jual, transaksi_detail.total,
    provinsi.nama_provinsi, guest_provinsi.nama_provinsi as guest_nama_provinsi,
    kota.nama_kota, guest_kota.nama_kota as guest_nama_kota,
    users.id, users.name, users.address
    ');
    $this->db->join('lapangan', 'transaksi_detail.lapangan_id = lapangan.id_lapangan');
    $this->db->join('transaksi', 'transaksi_detail.trans_id = transaksi.id_trans');
    $this->db->join('users', 'transaksi.user_id = users.id', 'left');
    $this->db->join('provinsi', 'provinsi.id_provinsi = users.provinsi', 'left');
    $this->db->join('kota', 'kota.id_kota = users.kota', 'left');
    $this->db->join('provinsi as guest_provinsi', 'guest_provinsi.id_provinsi = transaksi.guest_province_id', 'left');
    $this->db->join('kota as guest_kota', 'guest_kota.id_kota = transaksi.guest_city_id', 'left');
    $this->db->where('transaksi.id_trans',$id);
    return $this->db->get($this->table2);
  }

  function get_data_customer_back($invoice)
  {
    $this->db->join('provinsi', 'provinsi.id_provinsi = users.provinsi');
    $this->db->join('kota', 'kota.id_kota = users.kota');
    $this->db->join('transaksi', 'users.id = transaksi.user_id');
    $this->db->where('transaksi.id_trans', $invoice);
    return $this->db->get('users')->row();
  }

  // FRONTEND
  function total_cart_navbar()
  {
    $this->db->join('transaksi_detail', 'transaksi.id_trans = transaksi_detail.trans_id');
    
    if ($this->session->userdata('user_id')) {
      $this->db->where('user_id', $this->session->userdata('user_id'));
    } else {
      $this->db->where('session_id', session_id());
    }
    
    $this->db->where('status','0');
    // status 0 + subtotal masih 0 = masih di cart. status 0 tapi subtotal > 0 = transaksi sudah dibuat (checkout), jangan dihitung lagi
    $this->db->where('(subtotal = 0 OR subtotal IS NULL)', NULL, FALSE);
    return $this->db->get($this->table)->num_rows();
  }

  function cek_transaksi()
  {
    if ($this->session->userdata('user_id')) {
      $this->db->where('user_id', $this->session->userdata('user_id'));
    } else {
      $this->db->where('session_id', session_id());
    }
    
    $this->db->where('status','0');
    // status 0 + subtotal masih 0 = masih di cart (boleh dipakai ulang). status 0 tapi subtotal > 0 = transaksi sudah dibuat, jangan dipakai ulang
    $this->db->where('(subtotal = 0 OR subtotal IS NULL)', NULL, FALSE);
    return $this->db->get($this->table)->row();
  }

  function get_notransdet($id)
  {
    $this->db->join('transaksi_detail', 'transaksi.id_trans = transaksi_detail.trans_id');
    $this->db->where('lapangan_id',$id);
    
    if ($this->session->userdata('user_id')) {
      $this->db->where('user_id', $this->session->userdata('user_id'));
    } else {
      $this->db->where('session_id', session_id());
    }
    
    $this->db->where('status','0');
    $this->db->where('(subtotal = 0 OR subtotal IS NULL)', NULL, FALSE);
    return $this->db->get($this->table)->row();
  }

  function get_cart_per_customer()
  {
    $this->db->join('lapangan', 'transaksi_detail.lapangan_id = lapangan.id_lapangan');
    $this->db->join('transaksi', 'transaksi_detail.trans_id = transaksi.id_trans');
    $this->db->join('users', 'transaksi.user_id = users.id', 'left'); 
    
    if ($this->session->userdata('user_id')) {
      $this->db->where('transaksi.user_id', $this->session->userdata('user_id'));
    } else {
      $this->db->where('transaksi.session_id', session_id());
    }
    
    $this->db->where('status','0');
    $this->db->where('(subtotal = 0 OR subtotal IS NULL)', NULL, FALSE);
    return $this->db->get($this->table2);
  }

  function get_cart_per_customer_finished($id)
  {
    $this->db->select('
    lapangan.id_lapangan, lapangan.nama_lapangan,
    transaksi.id_trans, transaksi.id_invoice, transaksi.user_id, transaksi.session_id, transaksi.subtotal, transaksi.diskon, transaksi.grand_total, transaksi.deadline, transaksi.status, transaksi.catatan, transaksi.created_date, transaksi.created_time,
    transaksi_detail.trans_id, transaksi_detail.lapangan_id, transaksi_detail.tanggal, transaksi_detail.jam_mulai, transaksi_detail.durasi, transaksi_detail.jam_selesai, transaksi_detail.harga_jual, transaksi_detail.total,
    users.id
    ');
    $this->db->join('lapangan', 'transaksi_detail.lapangan_id = lapangan.id_lapangan');
    $this->db->join('transaksi', 'transaksi_detail.trans_id = transaksi.id_trans');
    $this->db->join('users', 'transaksi.user_id = users.id', 'left'); 
    $this->db->where('transaksi.id_trans', $id);
    
    if ($this->session->userdata('user_id')) {
      $this->db->where('transaksi.user_id', $this->session->userdata('user_id'));
    } else {
      $this->db->where('transaksi.session_id', session_id());
    }
    
    $this->db->order_by('transaksi.id_trans', 'DESC');
    return $this->db->get($this->table2);
  }

  function get_cart_per_customer_latest()
  {
    $this->db->select('id_trans');
    
    if ($this->session->userdata('user_id')) {
      $this->db->where('user_id', $this->session->userdata('user_id'));
    } else {
      $this->db->where('session_id', session_id());
    }
    
    $this->db->limit('1');
    $this->db->order_by('id_trans', 'DESC');
    return $this->db->get($this->table)->row();
  }

  function get_subtotal_per_customer_latest($id)
  {
    $this->db->select_sum('total');
    $this->db->where('trans_id', $id);
    return $this->db->get($this->table2)->row();
  }

  function get_data_customer()
  {
    $this->db->join('provinsi', 'provinsi.id_provinsi = users.provinsi');
    $this->db->join('kota', 'kota.id_kota = users.kota');
    $this->db->join('transaksi', 'users.id = transaksi.user_id');
    $this->db->order_by('transaksi.id_trans', 'DESC');
    $this->db->limit('1');
    $this->db->where('user_id', $this->session->userdata('user_id'));
    return $this->db->get('users')->row();
  }

  function get_all_finished_back($invoice)
  {
    $this->db->join('lapangan', 'transaksi_detail.lapangan_id = lapangan.id_lapangan');
    $this->db->join('transaksi', 'transaksi_detail.trans_id = transaksi.id_trans');
    $this->db->join('users', 'transaksi.user_id = users.id');
    $this->db->where('transaksi.id_trans', $invoice);
    return $this->db->get($this->table2);
  }

  function get_all_finished($invoice)
  {
    $this->db->join('lapangan', 'transaksi_detail.lapangan_id = lapangan.id_lapangan');
    $this->db->join('transaksi', 'transaksi_detail.trans_id = transaksi.id_trans');
    $this->db->join('users', 'transaksi.user_id = users.id');
    $this->db->where('transaksi.id_trans', $invoice);
    $this->db->where('user', $this->session->userdata('user_id'));
    $this->db->where('status','1');
    return $this->db->get($this->table2);
  }

  function total_berat_finished_back($invoice)
  {
    $this->db->select_sum('total_berat');
    $this->db->select_sum('subtotal');
    $this->db->join('transaksi', 'transaksi_detail.trans_id = transaksi.id_trans');
    $this->db->where('status','1');
    $this->db->or_where('status','2');
    $this->db->where('transaksi.id_trans', $invoice);
    return $this->db->get($this->table2)->row();
  }

  function get_by_id($id)
  {
    $this->db->where($this->id, $id);
    return $this->db->get($this->table)->row();
  }

  function get_by_id_detail($id)
  {
    $this->db->join('transaksi', 'transaksi_detail.trans_id = transaksi.id_trans');
    $this->db->where('id_transdet',$id);
    $this->db->where('user_id', $this->session->userdata('user_id'));
    $this->db->where('status','0');
    return $this->db->get($this->table2)->row();
  }

  function total_rows() {
    return $this->db->get($this->table)->num_rows();
  }

  // insert data
  function insert($data)
  {
    $this->db->insert($this->table, $data);
  }

  function insert_detail($data2)
  {
    $this->db->insert($this->table2, $data2);
  }

  function update_detail($id, $data)
  {
    $this->db->where('trans_ids',$id);
    $this->db->update($this->table2, $data);
  }

  function checkout($id, $data)
  {
    $this->db->where($this->id,$id);
    $this->db->where('user_id', $this->session->userdata('user_id'));
    $this->db->where('status','0');
    $this->db->update($this->table, $data);
  }

  function update($id, $data)
  {
    $this->db->where($this->id,$id);
    $this->db->update($this->table, $data);
  }

  function update_transdet($id, $data)
  {
    $this->db->where('id_transdet',$id);
    $this->db->update($this->table2, $data);
  }

  function delete($id)
  {
    $this->db->where('id_transdet', $id);
    $this->db->delete($this->table2);
  }

  function kosongkan_keranjang($id_trans)
  {
    $this->db->where('trans_id', $id_trans);
    $this->db->delete($this->table2);
  }

  function cart_history()
  {
    $this->db->where('user_id', $this->session->userdata('user_id'));
    $this->db->where_not_in('status','0');
    $this->db->order_by('id_trans', 'DESC');
    return $this->db->get($this->table);
  }

  function cart_history_detail()
  {
    $this->db->join('lapangan', 'transaksi_detail.lapangan_id = lapangan.id_lapangan');
    $this->db->join('transaksi', 'transaksi_detail.trans_id = transaksi.id_trans');
    $this->db->join('users', 'transaksi_detail.user = users.id');
    $this->db->where('user', $this->session->userdata('user_id'));
    $this->db->where('status','0');
    return $this->db->get($this->table2);
  }

  function history_detail($id)
  {
    $this->db->join('lapangan', 'transaksi_detail.lapangan_id = lapangan.id_lapangan');
    $this->db->join('transaksi', 'transaksi_detail.trans_id = transaksi.id_trans');
    $this->db->join('users', 'transaksi.user_id = users.id');
    $this->db->where($this->id, $id);
    $this->db->where('user_id', $this->session->userdata('user_id'));
    return $this->db->get($this->table2);
  }

  function history_total_berat($id)
  {
    $this->db->select_sum('total_berat');
    $this->db->join($this->table, 'transaksi_detail.trans_id = transaksi.id_trans');
    $this->db->where($this->id2, $id);
    $this->db->where('user', $this->session->userdata('user_id'));
    $this->db->where('status','1');
    return $this->db->get($this->table2)->row();
  }

  function get_bulan()
  {
    $this->db->select('judul_lapangan, transaksi.created as tanggal');
    $this->db->select_sum('total_qty');
    $this->db->join('transaksi_detail', 'transaksi.id_trans = transaksi_detail.trans_id');
    $this->db->join('lapangan', 'transaksi_detail.lapangan_id = lapangan.id_lapangan');
    $this->db->where('month('.$this->db->dbprefix.'transaksi.created)', date('m'));
    $this->db->group_by('lapangan_id');
    $this->db->order_by('tanggal', 'DESC');
    $this->db->limit(5);
    return $this->db->get($this->table)->result();
  }

  function total_penjualan()
  {
    $this->db->join($this->table2, $this->table.'.id_trans = '.$this->table2.'.trans_id');
    return $this->db->get($this->table)->result();
  }

  public function get_data_penjualan_periode()
  {
    $tgl_awal   = $this->input->post('tgl_awal'); 
    $tgl_akhir  = $this->input->post('tgl_akhir'); 

    $this->db->join('users', 'transaksi.user_id = users.id');
    $this->db->where('created >=', $tgl_awal.' 00:00:00');
    $this->db->where('created <=', $tgl_akhir.' 23:59:59');
    return $this->db->get($this->table)->result();
  }

  public function ambil_jam()
  {
    $this->db->where('tanggal','');
    $data=$this->db->get('transaksi_detail');
    if($data->num_rows()>0)
    {
      foreach ($data->result_array() as $row)
      {
        $result['']= '- Pilih Kategori -';
        $result[$row['id_kat']]= ucwords(strtolower($row['judul_kat']));
      }
      return $result;
    }
  }

  public function get_gtotal($id)
  {
    $this->db->select_sum('subtotal');
    $this->db->join($this->table, 'transaksi_detail.trans_id = transaksi.id_trans');
    $this->db->where($this->id, $id);
    $this->db->where('user', $this->session->userdata('user_id'));
    $this->db->where('status','1');
    return $this->db->get($this->table)->row();
  }

  public function get_omset_harian()
  {
    $date = new DateTime("now");
    $curr_date = $date->format('Y-m-d ');
    $this->db->select_sum('subtotal');
    $this->db->where('DATE(created_date)',$curr_date);
    $this->db->where('status','2');
    $query = $this->db->get($this->table);
    return $query->row()->subtotal;
  }

  public function get_omset_bulanan()
  {
    $this->db->select_sum('subtotal');
    $this->db->where('MONTH(created_date)', date('m'));
    $this->db->where('YEAR(created_date)', date('Y'));
    $this->db->where('status','2');
    $query = $this->db->get($this->table);
    return $query->row()->subtotal;
  }

  public function get_omset_tahunan()
  {
    $this->db->select_sum('subtotal');
    $this->db->where('YEAR (created_date) = YEAR(CURDATE()) ');
    $this->db->where('status','2');
    $query = $this->db->get($this->table);
    return $query->row()->subtotal;
  }

  public function stats_omset_bulanan(){
    $this->db->select("created_date, date(created_date)");
    $this->db->select_sum('subtotal');
    $this->db->where('MONTH(created_date) = MONTH(CURDATE()) ');
    $this->db->where('status','2');
    $this->db->group_by('created_date');
    $this->db->order_by('created_date', 'ASC');
    return $this->db->get($this->table)->result();
  }

  public function stats_omset_tahunan(){
    $this->db->select('MONTHNAME(created_date) as nama_bulan');
    $this->db->select_sum('subtotal');
    $this->db->where('YEAR (created_date) = YEAR(CURDATE()) ');
    $this->db->where('status','2');
    $this->db->group_by('MONTH(created_date)');
    $this->db->order_by('created_date', 'ASC');
    return $this->db->get($this->table)->result();
  }

  public function get_omset_total()
  {
    $this->db->select_sum('subtotal');
    $this->db->where('status','2');
    $query = $this->db->get($this->table);
    return $query->row()->subtotal;
  }
  
  public function get_booking_by_email_code($email, $booking_code)
  {
    $this->db->where('guest_email', $email);
    $this->db->where('id_invoice', $booking_code);
    return $this->db->get($this->table)->row();
  }
  
  public function get_booking_details($trans_id)
  {
    $this->db->select('lapangan.nama_lapangan, transaksi_detail.*');
    $this->db->join('lapangan', 'transaksi_detail.lapangan_id = lapangan.id_lapangan');
    $this->db->where('trans_id', $trans_id);
    return $this->db->get($this->table2)->result();
  }
}