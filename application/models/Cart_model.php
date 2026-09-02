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

  // Menandai transaksi yang sudah lewat deadline (3 hari sejak checkout) dan belum ada
  // pembayaran sama sekali (status masih '1' / Belum Lunas) sebagai EXPIRED (status '3').
  // Transaksi yang sudah Lunas Deposit ('2') atau Lunas Pembayaran ('4') TIDAK ikut expired.
  // Dipanggil otomatis setiap controller Cart / admin Transaksi diakses (lihat constructor),
  // supaya tidak perlu setup cron job terpisah, tapi tetap aman kalau mau dipasang cron
  // (lihat Cart::run_expire()).
  public function expire_old_transactions()
  {
    $this->db->where('status', '1');
    $this->db->where('deadline IS NOT NULL');
    $this->db->where('deadline <', date('Y-m-d H:i:s'));
    $this->db->update($this->table, array('status' => '3'));
  }

  // BACKEND //
  // $status = null  -> semua transaksi yang SUDAH checkout (default tampilan: status 1/Belum Lunas
  //                     yang diatur dari controller), status 0 (belum checkout / masih cart) SELALU
  //                     disembunyikan dari daftar transaksi admin.
  function get_all($status = null)
  {
    // Kolom jumlah_reschedule cuma bisa disertakan kalau tabel log-nya sudah ada
    // (migration db/migration_2026_08_update_2.sql sudah dijalankan). Kalau belum,
    // jangan sampai bikin SELECT keseluruhan gagal - anggap saja 0 (belum pernah reschedule).
    $reschedule_select = $this->db->table_exists('transaksi_reschedule_log')
      ? "(SELECT COUNT(*) FROM transaksi_reschedule_log rl WHERE rl.trans_id = transaksi.id_trans) AS jumlah_reschedule,"
      : "0 AS jumlah_reschedule,";

    $this->db->select('transaksi.*, 
                      konfirmasi_pembayaran.nama_pengirim, 
                      konfirmasi_pembayaran.bank_pengirim, 
                      konfirmasi_pembayaran.nominal AS nominal_konfirmasi, 
                      konfirmasi_pembayaran.bukti_transfer, 
                      konfirmasi_pembayaran.jenis_konfirmasi,
                      konfirmasi_pembayaran.catatan AS catatan_konfirmasi, 
                      konfirmasi_pembayaran.created_at AS tgl_konfirmasi,
                      konf_deposit.bukti_transfer AS deposit_bukti_transfer,
                      konf_deposit.nama_pengirim AS deposit_nama_pengirim,
                      konf_deposit.bank_pengirim AS deposit_bank_pengirim,
                      konf_deposit.nominal AS deposit_nominal,
                      konf_deposit.catatan AS deposit_catatan,
                      konf_deposit.created_at AS deposit_tgl,
                      konf_pelunasan.bukti_transfer AS pelunasan_bukti_transfer,
                      konf_pelunasan.nama_pengirim AS pelunasan_nama_pengirim,
                      konf_pelunasan.bank_pengirim AS pelunasan_bank_pengirim,
                      konf_pelunasan.nominal AS pelunasan_nominal,
                      konf_pelunasan.catatan AS pelunasan_catatan,
                      konf_pelunasan.created_at AS pelunasan_tgl,
                      (SELECT MIN(td.tanggal) FROM transaksi_detail td WHERE td.trans_id = transaksi.id_trans) AS tanggal_acara,
                      (SELECT COUNT(*) FROM transaksi_npwp tn WHERE tn.trans_id = transaksi.id_trans) AS jumlah_npwp,
                      (SELECT tn.nomor_npwp FROM transaksi_npwp tn WHERE tn.trans_id = transaksi.id_trans ORDER BY tn.id_npwp DESC LIMIT 1) AS nomor_npwp,
                      (SELECT SUM(kp.nominal) FROM konfirmasi_pembayaran kp WHERE kp.id_invoice = transaksi.id_invoice) AS total_dibayar,
                      ' . $reschedule_select . '
                      transaksi.id_trans AS id_trans
                      ', FALSE); // FALSE = matikan auto-escaping query builder, supaya subquery
                                 // kompleks di atas tidak salah di-backtick jadi nama kolom
                                 // (penyebab error "Unknown column '1=1'" sebelumnya).

    // LEFT JOIN hanya konfirmasi TERBARU per invoice (bukan semua histori),
    // supaya satu transaksi tidak muncul dobel kalau sudah pernah kirim
    // konfirmasi deposit DAN pelunasan.
    $this->db->join(
      '(SELECT k1.* FROM konfirmasi_pembayaran k1
        INNER JOIN (SELECT id_invoice, MAX(id_konfirmasi) AS max_id FROM konfirmasi_pembayaran GROUP BY id_invoice) k2
          ON k1.id_invoice = k2.id_invoice AND k1.id_konfirmasi = k2.max_id
       ) konfirmasi_pembayaran',
      'transaksi.id_invoice = konfirmasi_pembayaran.id_invoice',
      'left'
    );

    // LEFT JOIN konfirmasi DEPOSIT terbaru per invoice, dipakai supaya tombol
    // "Set Lunas Deposit" cuma tampil kalau bukti transfer kategori deposit ada.
    $this->db->join(
      "(SELECT k1.* FROM konfirmasi_pembayaran k1
        INNER JOIN (SELECT id_invoice, MAX(id_konfirmasi) AS max_id FROM konfirmasi_pembayaran WHERE jenis_konfirmasi = 'deposit' GROUP BY id_invoice) k2
          ON k1.id_invoice = k2.id_invoice AND k1.id_konfirmasi = k2.max_id
       ) konf_deposit",
      'transaksi.id_invoice = konf_deposit.id_invoice',
      'left'
    );

    // LEFT JOIN konfirmasi PELUNASAN terbaru per invoice, dipakai supaya tombol
    // "Set Lunas Pembayaran" cuma tampil kalau bukti transfer kategori pelunasan ada.
    $this->db->join(
      "(SELECT k1.* FROM konfirmasi_pembayaran k1
        INNER JOIN (SELECT id_invoice, MAX(id_konfirmasi) AS max_id FROM konfirmasi_pembayaran WHERE jenis_konfirmasi = 'pelunasan' GROUP BY id_invoice) k2
          ON k1.id_invoice = k2.id_invoice AND k1.id_konfirmasi = k2.max_id
       ) konf_pelunasan",
      'transaksi.id_invoice = konf_pelunasan.id_invoice',
      'left'
    );

    // "Belum Checkout" (status 0, masih di keranjang) tidak pernah ditampilkan di daftar transaksi
    $this->db->where('transaksi.status !=', '0');

    if ($status !== null && $status !== '') {
      if (is_array($status)) {
        $this->db->where_in('transaksi.status', $status);
      } else {
        $this->db->where('transaksi.status', $status);
      }
    }

    $this->db->order_by('transaksi.id_trans', 'DESC');
    return $this->db->get($this->table)->result();
  }

  // Riwayat semua transaksi yang sudah di-refund (status 5), lengkap dengan detail
  // refund-nya (nominal, catatan, tanggal, dan admin yang memprosesnya), dipakai di
  // halaman admin "Riwayat Refund". Diurut dari refund terbaru.
  function get_refund_history()
  {
    $this->db->select('transaksi.*,
                      (SELECT MIN(td.tanggal) FROM transaksi_detail td WHERE td.trans_id = transaksi.id_trans) AS tanggal_acara
                      ');
    $this->db->where('transaksi.status', '5');
    $this->db->order_by('transaksi.refund_date', 'DESC');
    $this->db->order_by('transaksi.id_trans', 'DESC');
    return $this->db->get($this->table)->result();
  }

  function get_cart_per_customer_finished_back($id)
  {
    $this->db->select('
    lapangan.id_lapangan, lapangan.nama_lapangan, lapangan.is_addon,
    transaksi.id_trans, transaksi.id_invoice, transaksi.subtotal, transaksi.diskon, transaksi.grand_total, transaksi.deadline, transaksi.status, transaksi.catatan, transaksi.nama_acara, transaksi.created_date,
    transaksi.guest_name, transaksi.guest_email, transaksi.guest_phone, transaksi.guest_address, transaksi.guest_province_id, transaksi.guest_city_id,
    transaksi_detail.trans_id, transaksi_detail.lapangan_id, transaksi_detail.tanggal, transaksi_detail.jam_mulai, transaksi_detail.durasi, transaksi_detail.jam_selesai, transaksi_detail.harga_jual, transaksi_detail.total,
    guest_provinsi.nama_provinsi as guest_nama_provinsi,
    guest_kota.nama_kota as guest_nama_kota
    ');
    $this->db->join('lapangan', 'transaksi_detail.lapangan_id = lapangan.id_lapangan');
    $this->db->join('transaksi', 'transaksi_detail.trans_id = transaksi.id_trans');
    $this->db->join('provinsi as guest_provinsi', 'guest_provinsi.id_provinsi = transaksi.guest_province_id', 'left');
    $this->db->join('kota as guest_kota', 'guest_kota.id_kota = transaksi.guest_city_id', 'left');
    $this->db->where('transaksi.id_trans',$id);
    return $this->db->get($this->table2);
  }

  // FRONTEND
  function total_cart_navbar()
  {
    $this->db->join('transaksi_detail', 'transaksi.id_trans = transaksi_detail.trans_id');
    $this->db->where('session_id', session_id());
    $this->db->where('status','0');
    // status 0 + subtotal masih 0 = masih di cart. status 0 tapi subtotal > 0 = transaksi sudah dibuat (checkout), jangan dihitung lagi
    $this->db->where('(subtotal = 0 OR subtotal IS NULL)', NULL, FALSE);
    return $this->db->get($this->table)->num_rows();
  }

  function cek_transaksi()
  {
    $this->db->where('session_id', session_id());
    $this->db->where('status','0');
    // status 0 + subtotal masih 0 = masih di cart (boleh dipakai ulang). status 0 tapi subtotal > 0 = transaksi sudah dibuat, jangan dipakai ulang
    $this->db->where('(subtotal = 0 OR subtotal IS NULL)', NULL, FALSE);
    return $this->db->get($this->table)->row();
  }

  // Addon cuma boleh ditambahkan kalau keranjang sesi ini SUDAH punya minimal 1 venue.
  function has_venue_in_cart()
  {
    $this->db->select('transaksi_detail.id_transdet');
    $this->db->join('lapangan', 'transaksi_detail.lapangan_id = lapangan.id_lapangan');
    $this->db->join('transaksi', 'transaksi_detail.trans_id = transaksi.id_trans');
    $this->db->where('transaksi.session_id', session_id());
    $this->db->where('transaksi.status', '0');
    $this->db->where('(transaksi.subtotal = 0 OR transaksi.subtotal IS NULL)', NULL, FALSE);
    $this->db->where('lapangan.is_addon', '0');
    $this->db->limit(1);
    return $this->db->get($this->table2)->num_rows() > 0;
  }

  function get_notransdet($id)
  {
    $this->db->join('transaksi_detail', 'transaksi.id_trans = transaksi_detail.trans_id');
    $this->db->where('lapangan_id',$id);
    $this->db->where('session_id', session_id());
    $this->db->where('status','0');
    $this->db->where('(subtotal = 0 OR subtotal IS NULL)', NULL, FALSE);
    return $this->db->get($this->table)->row();
  }

  function get_cart_per_customer()
  {
    $this->db->select('transaksi_detail.*, transaksi.id_trans, lapangan.nama_lapangan, lapangan.harga, lapangan.is_addon');
    $this->db->join('lapangan', 'transaksi_detail.lapangan_id = lapangan.id_lapangan');
    $this->db->join('transaksi', 'transaksi_detail.trans_id = transaksi.id_trans');
    $this->db->where('transaksi.session_id', session_id());
    $this->db->where('status','0');
    $this->db->where('(subtotal = 0 OR subtotal IS NULL)', NULL, FALSE);
    return $this->db->get($this->table2);
  }

  function get_cart_per_customer_finished($id)
  {
    $this->db->select('
    lapangan.id_lapangan, lapangan.nama_lapangan, lapangan.is_addon,
    transaksi.id_trans, transaksi.id_invoice, transaksi.session_id, transaksi.subtotal, transaksi.diskon, transaksi.grand_total, transaksi.deadline, transaksi.status, transaksi.catatan, transaksi.nama_acara, transaksi.created_date, transaksi.created_time,
    transaksi.guest_name, transaksi.guest_email, transaksi.guest_phone,
    transaksi_detail.trans_id, transaksi_detail.lapangan_id, transaksi_detail.tanggal, transaksi_detail.jam_mulai, transaksi_detail.durasi, transaksi_detail.jam_selesai, transaksi_detail.harga_jual, transaksi_detail.total
    ');
    $this->db->join('lapangan', 'transaksi_detail.lapangan_id = lapangan.id_lapangan');
    $this->db->join('transaksi', 'transaksi_detail.trans_id = transaksi.id_trans');
    $this->db->where('transaksi.id_trans', $id);
    $this->db->where('transaksi.session_id', session_id());
    $this->db->order_by('transaksi.id_trans', 'DESC');
    return $this->db->get($this->table2);
  }

  function get_cart_per_customer_latest()
  {
    $this->db->select('id_trans');
    $this->db->where('session_id', session_id());
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

  function get_by_id($id)
  {
    $this->db->where($this->id, $id);
    return $this->db->get($this->table)->row();
  }

  // Ambil satu baris transaksi berdasarkan id_invoice (bukan id_trans). Dipakai di
  // halaman Konfirmasi Pembayaran depan untuk menampilkan detail tagihan (Grand Total,
  // Deposit yang diperlukan, sisa yang perlu dilunasi, dsb).
  function get_by_invoice($id_invoice)
  {
    $this->db->where('id_invoice', $id_invoice);
    return $this->db->get($this->table)->row();
  }

  function get_by_id_detail($id)
  {
    $this->db->join('transaksi', 'transaksi_detail.trans_id = transaksi.id_trans');
    $this->db->where('id_transdet',$id);
    $this->db->where('transaksi.session_id', session_id());
    $this->db->where('transaksi.status','0');
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

  // Riwayat booking per sesi tamu saat ini (tanpa login).
  function cart_history()
  {
    $this->db->where('session_id', session_id());
    $this->db->where_not_in('status','0');
    $this->db->order_by('id_trans', 'DESC');
    return $this->db->get($this->table);
  }

  public function get_omset_harian()
  {
    $date = new DateTime("now");
    $curr_date = $date->format('Y-m-d ');
    $this->db->select_sum('subtotal');
    $this->db->where('DATE(created_date)',$curr_date);
    $this->db->where('status','4'); // 4 = Lunas Pembayaran (lunas penuh)
    $query = $this->db->get($this->table);
    return $query->row()->subtotal;
  }

  public function get_omset_bulanan()
  {
    $this->db->select_sum('subtotal');
    $this->db->where('MONTH(created_date)', date('m'));
    $this->db->where('YEAR(created_date)', date('Y'));
    $this->db->where('status','4');
    $query = $this->db->get($this->table);
    return $query->row()->subtotal;
  }

  public function get_omset_tahunan()
  {
    $this->db->select_sum('subtotal');
    $this->db->where('YEAR (created_date) = YEAR(CURDATE()) ');
    $this->db->where('status','4');
    $query = $this->db->get($this->table);
    return $query->row()->subtotal;
  }

  public function stats_omset_bulanan(){
    $this->db->select("created_date, date(created_date)");
    $this->db->select_sum('subtotal');
    $this->db->where('MONTH(created_date) = MONTH(CURDATE()) ');
    $this->db->where('status','4');
    $this->db->group_by('created_date');
    $this->db->order_by('created_date', 'ASC');
    return $this->db->get($this->table)->result();
  }

  public function stats_omset_tahunan(){
    $this->db->select('MONTHNAME(created_date) as nama_bulan');
    $this->db->select_sum('subtotal');
    $this->db->where('YEAR (created_date) = YEAR(CURDATE()) ');
    $this->db->where('status','4');
    $this->db->group_by('MONTH(created_date)');
    $this->db->order_by('created_date', 'ASC');
    return $this->db->get($this->table)->result();
  }

  public function get_omset_total()
  {
    $this->db->select_sum('subtotal');
    $this->db->where('status','4');
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
