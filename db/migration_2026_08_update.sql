-- =====================================================================
-- MIGRATION UNTUK PERUBAHAN SISTEM UCM VENUE (Agustus 2026)
-- =====================================================================
-- JALANKAN SATU KALI SAJA, backup database dulu sebelum eksekusi.
-- Sesuaikan nama tabel jika prefix tabel di DB Anda berbeda.
-- =====================================================================

-- ---------------------------------------------------------------------
-- 1. Tabel `transaksi`
-- ---------------------------------------------------------------------

-- Field nama acara (diinput tamu, tampil di atas catatan pada halaman cart)
ALTER TABLE `transaksi`
  ADD COLUMN `nama_acara` VARCHAR(150) NULL DEFAULT NULL AFTER `catatan`;

-- Perluas kolom status agar muat kode status baru:
--   0 = Belum Checkout (masih di cart)
--   1 = Belum Lunas (sudah checkout, belum ada pembayaran)
--   2 = Lunas Deposit (deposit sudah dibayar, pelunasan belum)
--   3 = Expired (tidak dibayar > 3 hari & belum lunas deposit)
--   4 = Lunas Pembayaran (lunas penuh)
--   5 = Refund (deposit/pembayaran sudah direfund)
-- Jika kolom status Anda sudah bertipe VARCHAR/CHAR yang cukup panjang, baris ini boleh dilewati.
ALTER TABLE `transaksi`
  MODIFY COLUMN `status` VARCHAR(2) NOT NULL DEFAULT '0';

-- Field untuk pencatatan refund deposit (tombol "Refund Deposit" di admin)
ALTER TABLE `transaksi`
  ADD COLUMN `refund_amount` DECIMAL(15,2) NULL DEFAULT NULL AFTER `status`,
  ADD COLUMN `refund_note` TEXT NULL DEFAULT NULL AFTER `refund_amount`,
  ADD COLUMN `refund_date` DATETIME NULL DEFAULT NULL AFTER `refund_note`,
  ADD COLUMN `refund_by` VARCHAR(100) NULL DEFAULT NULL AFTER `refund_date`;

-- CATATAN soal user_id / login:
-- Kolom `user_id` dan `session_id` TIDAK dihapus lewat migration ini supaya data transaksi lama
-- (yang dulu dibuat lewat akun member) tidak hilang/putus relasinya. Kode aplikasi SEKARANG
-- sudah tidak lagi menulis/membaca `user_id` sama sekali (semua booking = tamu, diidentifikasi
-- lewat `session_id` PHP session biasa). Kalau Anda benar-benar ingin membuang kolom `user_id`
-- secara fisik dari database, jalankan baris berikut SETELAH yakin tidak ada data yang butuh dicek lagi:
-- ALTER TABLE `transaksi` DROP COLUMN `user_id`;

-- ---------------------------------------------------------------------
-- 2. Tabel `konfirmasi_pembayaran`
-- ---------------------------------------------------------------------
-- Membedakan bukti transfer untuk DEPOSIT vs PELUNASAN/pembayaran penuh
ALTER TABLE `konfirmasi_pembayaran`
  ADD COLUMN `jenis_konfirmasi` ENUM('deposit','pelunasan') NOT NULL DEFAULT 'pelunasan' AFTER `id_invoice`;

-- ---------------------------------------------------------------------
-- 3. Tabel baru `transaksi_npwp`
-- ---------------------------------------------------------------------
-- NPWP disimpan sebagai file terpisah (URL/path gambar), bukan blob di tabel transaksi.
-- Diisi tamu di form checkout cart (opsional, untuk instansi/perusahaan).
CREATE TABLE IF NOT EXISTS `transaksi_npwp` (
  `id_npwp`     INT(11) NOT NULL AUTO_INCREMENT,
  `trans_id`    INT(11) NOT NULL,
  `nomor_npwp`  VARCHAR(30) NULL DEFAULT NULL,
  `npwp_image`  VARCHAR(255) NOT NULL,
  `created_at`  DATETIME NOT NULL,
  PRIMARY KEY (`id_npwp`),
  KEY `idx_trans_id` (`trans_id`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;

-- =====================================================================
-- SELESAI
-- =====================================================================
-- Ringkasan status baru untuk referensi tim (juga ada di komentar kode):
--   0 Belum Checkout | 1 Belum Lunas | 2 Lunas Deposit | 3 Expired | 4 Lunas Pembayaran | 5 Refund
-- =====================================================================
