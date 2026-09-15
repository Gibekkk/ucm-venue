<?php
/**
 * ==============================================================================
 * FIX: Status "Lunas" Deposit/Pelunasan muncul palsu sebelum admin verifikasi
 * ==============================================================================
 * BUG: Status "Lunas" untuk Deposit/Sewa di halaman Konfirmasi Pembayaran, Track
 * Booking, dan Detail Transaksi admin sebelumnya dihitung HANYA dari akumulasi
 * nominal yang disubmit tamu (SUM nominal >= target) - bukan dari apakah admin
 * sudah benar-benar memverifikasi (klik "Set Lunas Deposit" / "Set Lunas
 * Pembayaran"). Akibatnya begitu tamu submit cukup nominal, status langsung
 * tampil "Lunas" padahal admin belum membuka panel sama sekali.
 *
 * FIX INI mengubah logika supaya status "Lunas" HANYA muncul kalau admin sudah
 * benar-benar memverifikasi (transaksi.status = '2' untuk Deposit, atau '4'
 * untuk Sewa/Pelunasan). Selama belum diverifikasi admin, status akan tampil
 * "Belum Lunas" - walaupun nominal yang disubmit sudah cukup (ini tetap AMAN,
 * cuma kurang informatif; jauh lebih baik daripada menampilkan status palsu).
 *
 * CARA PAKAI:
 *   1. Upload file ini ke ROOT folder project (folder yang berisi "application/").
 *   2. Jalankan lewat SSH:  php fix_verification_status.php
 *   3. Script akan melaporkan file mana saja yang berhasil ditambal.
 *   4. Setelah selesai, file ini boleh dihapus dari server.
 * ==============================================================================
 */

$root = __DIR__;

// Setiap entri: [path relatif, [ [cari, ganti], [cari, ganti], ... ] ]
$patches = array(
    array(
        'file' => 'application/controllers/Page.php',
        'replacements' => array(
            array(
                "search"  => "\$status_pelunasan = (\$total_dibayar_pelunasan >= \$trans->grand_total) ? 'Lunas' : 'Belum Lunas';",
                "replace" => "\$status_pelunasan = (\$status_booking == '4') ? 'Lunas' : 'Belum Lunas'; // FIX: harus diverifikasi admin (status 4), bukan cuma cukup nominal",
            ),
            array(
                "search"  => "\$status_deposit   = (\$total_dibayar_deposit >= \$target_deposit) ? 'Lunas' : 'Belum Lunas';",
                "replace" => "\$status_deposit   = (in_array(\$status_booking, array('2', '4'))) ? 'Lunas' : 'Belum Lunas'; // FIX: harus diverifikasi admin (status 2/4), bukan cuma cukup nominal",
            ),
        ),
    ),
    array(
        'file' => 'application/controllers/Cart.php',
        'replacements' => array(
            array(
                "search"  => "'status_pelunasan'        => (\$total_dibayar_pelunasan >= \$booking->grand_total) ? 'Lunas' : 'Belum Lunas',",
                "replace" => "'status_pelunasan'        => (\$booking->status == '4') ? 'Lunas' : 'Belum Lunas', // FIX: harus diverifikasi admin",
            ),
            array(
                "search"  => "'status_deposit'          => (\$total_dibayar_deposit >= \$target_deposit) ? 'Lunas' : 'Belum Lunas',",
                "replace" => "'status_deposit'          => (in_array(\$booking->status, array('2', '4'))) ? 'Lunas' : 'Belum Lunas', // FIX: harus diverifikasi admin",
            ),
        ),
    ),
    array(
        'file' => 'application/controllers/admin/Transaksi.php',
        'replacements' => array(
            // detail()
            array(
                "search"  => "'status_pelunasan'        => (\$total_dibayar_pelunasan >= \$row->grand_total) ? 'Lunas' : 'Belum Lunas',",
                "replace" => "'status_pelunasan'        => (\$row->status == '4') ? 'Lunas' : 'Belum Lunas', // FIX: harus diverifikasi admin",
            ),
            array(
                "search"  => "'status_deposit'          => (\$total_dibayar_deposit >= \$target_deposit) ? 'Lunas' : 'Belum Lunas',",
                "replace" => "'status_deposit'          => (in_array(\$row->status, array('2', '4'))) ? 'Lunas' : 'Belum Lunas', // FIX: harus diverifikasi admin",
            ),
            // refund_deposit()
            array(
                "search"  => "'status_pelunasan'        => (\$total_dibayar_pelunasan_rd >= \$row->grand_total) ? 'Lunas' : 'Belum Lunas',",
                "replace" => "'status_pelunasan'        => (\$row->status == '4') ? 'Lunas' : 'Belum Lunas', // FIX: harus diverifikasi admin",
            ),
            array(
                "search"  => "'status_deposit'          => (\$total_dibayar_deposit_rd >= \$target_deposit_rd) ? 'Lunas' : 'Belum Lunas',",
                "replace" => "'status_deposit'          => (in_array(\$row->status, array('2', '4'))) ? 'Lunas' : 'Belum Lunas', // FIX: harus diverifikasi admin",
            ),
        ),
    ),
);

echo "==============================================================\n";
echo " Menambal bug status verifikasi palsu\n";
echo "==============================================================\n\n";

$total_ok = 0;
$total_fail = 0;

foreach ($patches as $patch) {
    $filepath = $root . '/' . $patch['file'];
    echo "File: {$patch['file']}\n";

    if (!file_exists($filepath)) {
        echo "  [GAGAL] File tidak ditemukan. Pastikan script ini ada di root project.\n\n";
        $total_fail += count($patch['replacements']);
        continue;
    }

    $content = file_get_contents($filepath);
    $original = $content;
    $file_ok = 0;
    $file_fail = 0;

    foreach ($patch['replacements'] as $r) {
        if (strpos($content, $r['search']) !== false) {
            $content = str_replace($r['search'], $r['replace'], $content);
            $file_ok++;
        } else {
            echo "  [LEWATI] Pola tidak ditemukan (mungkin sudah ditambal sebelumnya, atau file sudah diubah manual):\n";
            echo "           " . substr($r['search'], 0, 90) . "...\n";
            $file_fail++;
        }
    }

    if ($content !== $original) {
        // Backup dulu sebelum menimpa
        copy($filepath, $filepath . '.bak-' . date('Ymd-His'));
        file_put_contents($filepath, $content);
        echo "  [OK] $file_ok pola berhasil ditambal. Backup asli disimpan di: " . basename($filepath) . ".bak-" . date('Ymd-His') . "\n";
    } else {
        echo "  [INFO] Tidak ada perubahan pada file ini.\n";
    }

    echo "\n";
    $total_ok += $file_ok;
    $total_fail += $file_fail;
}

echo "==============================================================\n";
echo " Selesai. Berhasil: $total_ok | Dilewati/gagal: $total_fail\n";
echo "==============================================================\n";

if ($total_fail > 0) {
    echo "\nCatatan: baris yang 'dilewati' biasanya berarti fix ini sudah pernah\n";
    echo "dijalankan sebelumnya, atau ada penyesuaian manual di file tsb.\n";
    echo "Silakan cek manual bagian 'status_pelunasan' / 'status_deposit' di file\n";
    echo "terkait kalau status masih tidak sesuai harapan.\n";
}
