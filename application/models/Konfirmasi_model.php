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

    // Mengecek apakah id_invoice masih boleh menerima konfirmasi BARU dari jenis tertentu.
    // Deposit (Jaminan) dan Pelunasan (Sewa/Grand Total) adalah DUA KEWAJIBAN TERPISAH:
    //   Total yang harus dibayar tamu = Grand Total (Sewa) + 25% dari Grand Total (Deposit).
    // Progres masing-masing dihitung dari konfirmasi yang jenisnya SAMA saja (tidak
    // dicampur), dan keduanya boleh dicicil berkali-kali sampai tercukupi:
    // - Target DEPOSIT = 25% dari Grand Total. Boleh dikirim berkali-kali selama booking
    //   masih aktif (bukan Expired/Refund/Dibatalkan) DAN total konfirmasi Deposit belum
    //   mencapai target tsb.
    // - Target PELUNASAN = 100% dari Grand Total (Sewa). Boleh dikirim berkali-kali selama
    //   booking masih aktif DAN total konfirmasi Pelunasan belum mencapai Grand Total.
    function cek_invoice_valid($id_invoice, $jenis_konfirmasi = null)
    {
        $trans = $this->db->where('id_invoice', $id_invoice)->get('transaksi')->row();
        if (!$trans) {
            return 0;
        }

        // Expired (3), Refund (5), Dibatalkan (6) -> booking sudah tidak aktif, tidak
        // menerima konfirmasi baru sama sekali (untuk Deposit maupun Pelunasan).
        // Status 0 (Belum Checkout) juga ditolak.
        if (in_array($trans->status, array('0', '3', '5', '6'))) {
            return 0;
        }

        if ($jenis_konfirmasi === 'deposit') {
            // Deposit (Jaminan) independen dari status Sewa - tetap bisa dikonfirmasi
            // walau Sewa (Pelunasan) sudah Lunas Pembayaran (4), selama target Deposit-nya
            // sendiri belum tercukupi.
            $target_deposit     = $trans->grand_total * 0.25;
            $total_dibayar_dp   = $this->get_total_paid_by_jenis($id_invoice, 'deposit');
            if ($total_dibayar_dp >= $target_deposit) {
                return 0; // target deposit sudah tercapai, tidak perlu konfirmasi deposit lagi
            }
            return 1;
        }

        // pelunasan - begitu admin sudah men-set Lunas Pembayaran (4), tidak perlu
        // konfirmasi pelunasan lagi.
        if ($trans->status === '4') {
            return 0;
        }
        $total_dibayar_pelunasan = $this->get_total_paid_by_jenis($id_invoice, 'pelunasan');
        if ($total_dibayar_pelunasan >= $trans->grand_total) {
            return 0; // sewa (grand total) sudah lunas penuh
        }
        return 1;
    }

    // Daftar jenis konfirmasi (deposit/pelunasan) yang SUDAH PERNAH disubmit untuk satu
    // invoice, apa adanya - terlepas dari sudah diverifikasi/di-set Lunas oleh admin atau
    // belum. Dipakai untuk menampilkan status "Menunggu Konfirmasi ..." di halaman Track
    // Booking (bukan lagi untuk membatasi submit ulang - sekarang cicilan diperbolehkan).
    function get_submitted_jenis($id_invoice)
    {
        $this->db->select('jenis_konfirmasi');
        $this->db->distinct();
        $this->db->where('id_invoice', $id_invoice);
        $rows = $this->db->get($this->table)->result();
        return array_map(function ($r) {
            return $r->jenis_konfirmasi;
        }, $rows);
    }

    // Ambil status transaksi apa adanya berdasarkan invoice, dipakai supaya pesan error
    // di form konfirmasi bisa lebih spesifik (mis. "sudah Lunas Deposit") daripada cuma
    // pesan generik "tidak ditemukan / sudah lunas / kadaluarsa".
    function get_status_by_invoice($id_invoice)
    {
        $this->db->select('status');
        $this->db->where('id_invoice', $id_invoice);
        $row = $this->db->get('transaksi')->row();
        return $row ? $row->status : null;
    }

    // Semua riwayat konfirmasi (deposit & pelunasan) untuk satu invoice, terbaru dulu
    function get_by_invoice($id_invoice)
    {
        $this->db->where('id_invoice', $id_invoice);
        $this->db->order_by($this->id, $this->order);
        return $this->db->get($this->table)->result();
    }

    // Konfirmasi TERBARU untuk satu invoice, opsional difilter berdasarkan jenisnya
    // (deposit / pelunasan). Dipakai untuk validasi sebelum admin set status Lunas,
    // supaya kategori bukti transfer yang divalidasi sesuai dengan status yang dituju.
    function get_latest_by_invoice($id_invoice, $jenis = null)
    {
        $this->db->where('id_invoice', $id_invoice);
        if ($jenis !== null) {
            $this->db->where('jenis_konfirmasi', $jenis);
        }
        $this->db->order_by($this->id, 'DESC');
        $this->db->limit(1);
        return $this->db->get($this->table)->row();
    }

    // Total nominal yang SUDAH ditransfer/dikonfirmasi tamu untuk satu invoice (jumlah semua
    // baris konfirmasi deposit + pelunasan sesuai bukti transfer yang diupload). Ini beda
    // dengan `grand_total` transaksi (tagihan seharusnya) - dipakai supaya admin bisa lihat
    // total pembayaran ASLI yang sudah masuk, terutama sebagai acuan saat memproses refund.
    function get_total_paid($id_invoice)
    {
        $this->db->select_sum('nominal');
        $this->db->where('id_invoice', $id_invoice);
        $row = $this->db->get($this->table)->row();
        return ($row && $row->nominal !== null) ? floatval($row->nominal) : 0;
    }

    // Total nominal yang sudah masuk untuk SATU jenis konfirmasi saja ('deposit' atau
    // 'pelunasan'). Deposit (Jaminan/DP) dan Pelunasan (Sewa/Grand Total) sekarang adalah
    // DUA KEWAJIBAN TERPISAH (Total yang harus dibayar tamu = Grand Total + 25% Deposit),
    // jadi progres pembayarannya juga harus dihitung terpisah per kategori - bukan digabung
    // jadi satu pool seperti get_total_paid() di atas.
    function get_total_paid_by_jenis($id_invoice, $jenis_konfirmasi)
    {
        $this->db->select_sum('nominal');
        $this->db->where('id_invoice', $id_invoice);
        $this->db->where('jenis_konfirmasi', $jenis_konfirmasi);
        $row = $this->db->get($this->table)->row();
        return ($row && $row->nominal !== null) ? floatval($row->nominal) : 0;
    }

    // Menyimpan data konfirmasi baru dari form depan
    function insert($data)
    {
        return $this->db->insert($this->table, $data);
    }

    // Memperbarui status pembayaran di tabel transaksi menjadi Lunas Pembayaran (4)
    function set_lunas_transaksi($id_invoice)
    {
        $this->db->where('id_invoice', $id_invoice);
        return $this->db->update('transaksi', array('status' => '4'));
    }

    // Menghapus data konfirmasi jika dirasa invalid/spam
    function delete($id)
    {
        // Ambil data untuk hapus foto fisiknya (opsional tapi disarankan)
        $this->db->where($this->id, $id);
        $row = $this->db->get($this->table)->row();
        if ($row && file_exists('./assets/images/bukti_transaksi/' . $row->bukti_transfer)) {
            unlink('./assets/images/bukti_transaksi/' . $row->bukti_transfer);
        }

        $this->db->where($this->id, $id);
        $this->db->delete($this->table);
    }
}