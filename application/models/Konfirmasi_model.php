<?php  if ( ! defined('BASEPATH')) exit('No direct script access allowed');

class Konfirmasi_model extends CI_Model
{
    public $table = 'konfirmasi_pembayaran';
    public $id    = 'id_konfirmasi';
    public $order = 'DESC';

    // Mengambil semua data konfirmasi (dipakai di Page::__construct untuk sidebar)
    // Ikut join ke transaksi karena kolom status ada di tabel transaksi, bukan di konfirmasi_pembayaran
    function get_all()
    {
        $this->db->select('konfirmasi_pembayaran.*, transaksi.status AS status_transaksi');
        $this->db->from('konfirmasi_pembayaran');
        $this->db->join('transaksi', 'konfirmasi_pembayaran.id_invoice = transaksi.id_invoice', 'left');
        $this->db->order_by('konfirmasi_pembayaran.' . $this->id, $this->order);
        return $this->db->get()->result();
    }

    // Mengambil semua data konfirmasi beserta status dari tabel transaksi
    function get_all_with_status()
    {
        $this->db->select('konfirmasi_pembayaran.*, transaksi.status AS status_transaksi');
        $this->db->from('konfirmasi_pembayaran');
        $this->db->join('transaksi', 'konfirmasi_pembayaran.id_invoice = transaksi.id_invoice', 'left');
        $this->db->order_by('konfirmasi_pembayaran.id_konfirmasi', 'DESC');
        return $this->db->get()->result();
    }

    // Mengecek ketersediaan id_invoice di tabel transaksi
    // Transaksi yang sudah Lunas (status 2) dianggap tidak valid untuk konfirmasi ulang
    function cek_invoice_valid($id_invoice)
    {
        $this->db->where('id_invoice', $id_invoice);
        $this->db->where('status !=', '2');
        $this->db->where('status !=', '1'); // Tambahan: status 1 (Pending) juga dianggap tidak valid untuk konfirmasi ulang
        return $this->db->get('transaksi')->num_rows();
    }

    // Menyimpan data konfirmasi baru dari form depan
    function insert($data)
    {
        return $this->db->insert($this->table, $data);
    }

    // Memperbarui status pembayaran di tabel transaksi menjadi Lunas (2)
    function set_lunas_transaksi($id_invoice)
    {
        $this->db->where('id_invoice', $id_invoice);
        // Status 2 = Lunas
        return $this->db->update('transaksi', array('status' => '2')); 
    }

    // Menghapus data konfirmasi jika dirasa invalid/spam
    function delete($id)
    {
        // Ambil data untuk hapus foto fisiknya (opsional tapi disarankan)
        $this->db->where($this->id, $id);
        $row = $this->db->get($this->table)->row();
        if ($row && file_exists('./assets/images/' . $row->bukti_transfer)) {
            unlink('./assets/images/' . $row->bukti_transfer);
        }

        $this->db->where($this->id, $id);
        $this->db->delete($this->table);
    }
}