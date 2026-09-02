<?php  if ( ! defined('BASEPATH')) exit('No direct script access allowed');

class Transaksi_detail_model extends CI_Model{

	// Booking dianggap MEMBLOKIR jam & venue yang sama untuk tamu lain kalau:
	//   - status = 2 (Lunas Deposit, sudah divalidasi admin) atau 4 (Lunas Pembayaran) -> SELALU blokir.
	//   - status = 1 (Belum Lunas, belum divalidasi admin) -> blokir HANYA KALAU total
	//     konfirmasi DEPOSIT (jenis_konfirmasi = 'deposit' saja - Deposit/Jaminan adalah
	//     kewajiban TERPISAH dari Sewa/Grand Total) yang sudah masuk (akumulasi semua
	//     cicilan, apa adanya walau belum divalidasi admin) sudah mencapai MINIMAL 50%
	//     dari target Deposit (Deposit = 25% dari Grand Total, jadi ambang blokir =
	//     12.5% dari Grand Total).
	//     Booking yang baru checkout tanpa ada pembayaran Deposit sama sekali TIDAK
	//     memblokir slot, supaya slot itu tetap bisa diambil tamu lain yang serius
	//     membayar duluan.
	// Status yang TIDAK PERNAH memblokir (jam tetap bisa dipilih orang lain):
	//   0 = Belum Checkout, 3 = Expired, 5 = Refund, 6 = Dibatalkan
	const DEPOSIT_PERCENT           = 0.25; // Deposit = 25% dari Grand Total
	const BLOCK_THRESHOLD_OF_DEPOSIT = 0.5;  // Blokir slot begitu 50% dari Deposit itu terbayar

	// Cek apakah satu baris transaksi_detail (dengan status & total_dibayar_deposit tertentu)
	// dianggap AKTIF/MEMBLOKIR slot venue-nya, sesuai kebijakan di atas.
	private function _is_booking_blocking($status, $grand_total, $total_dibayar_deposit)
	{
		if ($status == '2' || $status == '4') {
			return true;
		}
		if ($status == '1') {
			$ambang_blokir = $grand_total * self::DEPOSIT_PERCENT * self::BLOCK_THRESHOLD_OF_DEPOSIT;
			return ($total_dibayar_deposit >= $ambang_blokir);
		}
		return false;
	}

	// Semua baris booking yang MEMBLOKIR (lihat _is_booking_blocking) untuk satu venue, dari
	// hari ini sampai $days_ahead hari ke depan. Dipakai untuk menghitung tanggal mana yang
	// sudah penuh (semua jam terpakai) supaya bisa di-disable di datepicker front-end.
	function get_active_bookings_for_lapangan($lapangan_id, $days_ahead = 90)
	{
		$this->db->select('transaksi_detail.tanggal, transaksi_detail.jam_mulai, transaksi_detail.durasi, transaksi_detail.jam_selesai,
		                    transaksi.status, transaksi.grand_total,
		                    (SELECT COALESCE(SUM(kp.nominal),0) FROM konfirmasi_pembayaran kp WHERE kp.id_invoice = transaksi.id_invoice AND kp.jenis_konfirmasi = 'deposit') AS total_dibayar_deposit');
		$this->db->join('transaksi', 'transaksi.id_trans = transaksi_detail.trans_id');
		$this->db->where('transaksi_detail.lapangan_id', $lapangan_id);
		$this->db->where('transaksi_detail.tanggal >=', date('Y-m-d'));
		$this->db->where('transaksi_detail.tanggal <=', date('Y-m-d', strtotime('+' . intval($days_ahead) . ' days')));
		$this->db->where_in('transaksi.status', array('1', '2', '4'));
		$this->db->order_by('transaksi_detail.tanggal', 'asc');
		$rows = $this->db->get('transaksi_detail')->result();

		// Filter baris status Belum Lunas (1) yang belum memenuhi ambang bayar 50% Deposit
		$result = array();
		foreach ($rows as $r) {
			if ($this->_is_booking_blocking($r->status, $r->grand_total, $r->total_dibayar_deposit)) {
				$result[] = $r;
			}
		}
		return $result;
	}

	function get_jam_mulai_terpakai($tanggal, $lapangan_id, $exclude_trans_id = null){
		$this->db->select('transaksi_detail.jam_mulai, transaksi_detail.durasi, transaksi_detail.jam_selesai,
		                    transaksi.status, transaksi.grand_total,
		                    (SELECT COALESCE(SUM(kp.nominal),0) FROM konfirmasi_pembayaran kp WHERE kp.id_invoice = transaksi.id_invoice AND kp.jenis_konfirmasi = 'deposit') AS total_dibayar_deposit');
		$this->db->join('transaksi', 'transaksi.id_trans = transaksi_detail.trans_id');
		$this->db->where('transaksi_detail.tanggal', $tanggal);
		$this->db->where('transaksi_detail.lapangan_id', $lapangan_id);
		$this->db->where_in('transaksi.status', array('1', '2', '4'));
		if ($exclude_trans_id !== null) {
			$this->db->where('transaksi_detail.trans_id !=', $exclude_trans_id);
		}
		$rows = $this->db->get('transaksi_detail')->result();

		$result = array();
		foreach ($rows as $r) {
			if ($this->_is_booking_blocking($r->status, $r->grand_total, $r->total_dibayar_deposit)) {
				$result[] = $r;
			}
		}
		return $result;
	}

	// Cek apakah slot jam_mulai s/d jam_mulai+durasi bentrok dengan booking lain yang
	// AKTIF/MEMBLOKIR (lihat _is_booking_blocking) di venue & tanggal yang sama. Dipakai
	// bersama oleh checkout tamu (Cart::_is_slot_conflict) dan reschedule admin
	// (Transaksi::reschedule_action), supaya logika pengecekan bentrok konsisten di satu
	// tempat saja.
	// $exclude_trans_id     -> abaikan baris milik transaksi ini sendiri (mis. keranjang sendiri)
	// $exclude_transdet_id  -> abaikan baris detail ini sendiri (dipakai saat reschedule, supaya
	//                          baris yang sedang diubah tidak dianggap bentrok dengan dirinya sendiri)
	function is_slot_conflict($lapangan_id, $tanggal, $jam_mulai, $durasi, $exclude_trans_id = null, $exclude_transdet_id = null)
	{
		$durasi = max(1, intval($durasi));
		$new_start = strtotime('2000-01-01 ' . $jam_mulai);
		$new_end   = $new_start + ($durasi * 3600);

		$this->db->select('transaksi_detail.id_transdet, transaksi_detail.jam_mulai, transaksi_detail.durasi, transaksi_detail.jam_selesai,
		                    transaksi.status, transaksi.grand_total,
		                    (SELECT COALESCE(SUM(kp.nominal),0) FROM konfirmasi_pembayaran kp WHERE kp.id_invoice = transaksi.id_invoice AND kp.jenis_konfirmasi = 'deposit') AS total_dibayar_deposit');
		$this->db->join('transaksi', 'transaksi.id_trans = transaksi_detail.trans_id');
		$this->db->where('transaksi_detail.tanggal', $tanggal);
		$this->db->where('transaksi_detail.lapangan_id', $lapangan_id);
		$this->db->where_in('transaksi.status', array('1', '2', '4'));
		if ($exclude_trans_id !== null) {
			$this->db->where('transaksi_detail.trans_id !=', $exclude_trans_id);
		}
		if ($exclude_transdet_id !== null) {
			$this->db->where('transaksi_detail.id_transdet !=', $exclude_transdet_id);
		}
		$occupied = $this->db->get('transaksi_detail')->result();

		foreach ($occupied as $o) {
			if (!$this->_is_booking_blocking($o->status, $o->grand_total, $o->total_dibayar_deposit)) {
				continue; // status 1 tapi belum bayar minimal 50% Deposit -> tidak dianggap blokir
			}

			$occ_start = strtotime('2000-01-01 ' . $o->jam_mulai);
			$occ_end   = $occ_start + (max(1, intval($o->durasi)) * 3600);

			if ($new_start < $occ_end && $occ_start < $new_end) {
				return true;
			}
		}
		return false;
	}

	// Semua baris detail booking (venue & addon) untuk satu transaksi, lengkap dengan
	// data lapangan-nya. Dipakai di halaman admin Reschedule.
	function get_by_trans_id($trans_id)
	{
		$this->db->select('transaksi_detail.*, lapangan.nama_lapangan, lapangan.is_addon, lapangan.harga AS harga_lapangan_sekarang');
		$this->db->join('lapangan', 'transaksi_detail.lapangan_id = lapangan.id_lapangan');
		$this->db->where('transaksi_detail.trans_id', $trans_id);
		$this->db->order_by('transaksi_detail.id_transdet', 'ASC');
		return $this->db->get('transaksi_detail')->result();
	}
}

