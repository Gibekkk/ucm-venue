<?php if (!defined('BASEPATH')) exit('No direct script access allowed');

// Menyimpan bukti NPWP (nomor + foto) untuk booking atas nama instansi/perusahaan.
// Foto disimpan sebagai file di disk, kolom npwp_image hanya menyimpan nama file/URL-nya
// (bukan blob), sama seperti pola bukti_transfer pada konfirmasi_pembayaran.
class Npwp_model extends CI_Model
{
    public $table = 'transaksi_npwp';
    public $id    = 'id_npwp';

    function insert($data)
    {
        return $this->db->insert($this->table, $data);
    }

    function get_by_trans_id($trans_id)
    {
        $this->db->where('trans_id', $trans_id);
        $this->db->order_by('id_npwp', 'DESC');
        return $this->db->get($this->table)->row();
    }
}
