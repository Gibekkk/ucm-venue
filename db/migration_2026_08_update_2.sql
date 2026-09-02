-- =====================================================================
-- MIGRATION TAMBAHAN - REVISI FITUR (Batalkan Booking, Reschedule)
-- =====================================================================
-- JALANKAN SATU KALI SAJA setelah migration_2026_08_update.sql, backup
-- database dulu sebelum eksekusi.
-- =====================================================================

-- ---------------------------------------------------------------------
-- 1. Tabel `transaksi` - kolom untuk pembatalan booking oleh admin
-- ---------------------------------------------------------------------
-- Status baru:
--   6 = Dibatalkan (booking dibatalkan admin, alasan wajib diisi)
-- Kolom status transaksi sudah VARCHAR(2) dari migration sebelumnya, jadi
-- cukup panjang untuk menampung kode '6'.
ALTER TABLE `transaksi`
  ADD COLUMN `cancel_note` TEXT NULL DEFAULT NULL AFTER `refund_by`,
  ADD COLUMN `cancel_date` DATETIME NULL DEFAULT NULL AFTER `cancel_note`,
  ADD COLUMN `cancel_by` VARCHAR(100) NULL DEFAULT NULL AFTER `cancel_date`;

-- ---------------------------------------------------------------------
-- 2. Tabel baru `transaksi_reschedule_log` - riwayat perubahan jadwal
--    (audit trail) setiap kali admin melakukan reschedule booking.
-- ---------------------------------------------------------------------
CREATE TABLE IF NOT EXISTS `transaksi_reschedule_log` (
  `id_log`            INT(11) NOT NULL AUTO_INCREMENT,
  `trans_id`          INT(11) NOT NULL,
  `transdet_id`       INT(11) NOT NULL,
  `lapangan_id_lama`  INT(11) NULL DEFAULT NULL,
  `tanggal_lama`      DATE NULL DEFAULT NULL,
  `jam_mulai_lama`    TIME NULL DEFAULT NULL,
  `durasi_lama`       INT(11) NULL DEFAULT NULL,
  `lapangan_id_baru`  INT(11) NULL DEFAULT NULL,
  `tanggal_baru`      DATE NULL DEFAULT NULL,
  `jam_mulai_baru`    TIME NULL DEFAULT NULL,
  `durasi_baru`       INT(11) NULL DEFAULT NULL,
  `created_by`        VARCHAR(100) NULL DEFAULT NULL,
  `created_at`        DATETIME NULL DEFAULT NULL,
  PRIMARY KEY (`id_log`),
  KEY `idx_trans_id` (`trans_id`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;

-- =====================================================================
-- SELESAI
-- =====================================================================
-- Ringkasan status transaksi.status terbaru:
--   0 Belum Checkout | 1 Belum Lunas | 2 Lunas Deposit | 3 Expired
--   4 Lunas Pembayaran | 5 Refund | 6 Dibatalkan (baru)
-- =====================================================================
