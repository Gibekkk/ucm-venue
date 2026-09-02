<?php $this->load->view('front/header'); ?>
<?php $this->load->view('front/navbar'); ?>

<div class="container">
    <ol class="breadcrumb">
        <li><a href="<?php echo base_url() ?>">Home</a></li>
        <li><a href="#">Transaksi</a></li>
        <li class="active">Konfirmasi Pembayaran</li>
    </ol>

    <div class="row">
        <div class="col-md-12">
            <h1>KONFIRMASI PEMBAYARAN</h1>
            <hr>

            <?php echo validation_errors('<div class="alert alert-danger">', '</div>'); ?>
            <?php if($this->session->flashdata('message')){echo $this->session->flashdata('message');} ?>

            <?php if (empty($prefill_invoice)) { ?>

                <!-- Tidak ada id_invoice yang dibawa dari Track Booking -->
                <div class="alert alert-warning">
                    <i class="fa fa-info-circle"></i> Konfirmasi pembayaran hanya bisa dilakukan melalui halaman <b>Track Booking</b>. Silakan cari booking Anda terlebih dahulu, lalu klik tombol "Konfirmasi Pembayaran" pada booking yang bersangkutan.
                </div>
                <a href="<?php echo base_url('cart/track_booking') ?>" class="btn btn-primary">
                    <i class="fa fa-search"></i> Cari Booking Saya (Track Booking)
                </a>

            <?php } elseif (empty($trans)) { ?>

                <!-- id_invoice ada di URL tapi bookingnya tidak ditemukan -->
                <div class="alert alert-danger">
                    <i class="fa fa-exclamation-triangle"></i> No. Invoice <b><?php echo htmlspecialchars($prefill_invoice) ?></b> tidak ditemukan. Silakan cari kembali melalui Track Booking.
                </div>
                <a href="<?php echo base_url('cart/track_booking') ?>" class="btn btn-primary">
                    <i class="fa fa-search"></i> Kembali ke Track Booking
                </a>

            <?php } elseif (!$can_confirm) { ?>

                <!-- Sudah tidak ada jenis pembayaran yang perlu/boleh dikonfirmasi lagi -->
                <div class="alert alert-info">
                    <i class="fa fa-check-circle"></i> Tidak ada pembayaran yang perlu dikonfirmasi untuk invoice <b><?php echo htmlspecialchars($prefill_invoice) ?></b> saat ini
                    (mis. pembayaran sudah lunas/terverifikasi, sudah pernah dikonfirmasi sebelumnya, atau booking sudah tidak aktif).
                </div>
                <a href="<?php echo base_url('cart/track_booking') ?>" class="btn btn-primary">
                    <i class="fa fa-search"></i> Cek Status Booking
                </a>

            <?php } else { ?>

                <!-- Ringkasan Total Kewajiban -->
                <div class="panel panel-default">
                    <div class="panel-heading"><b><i class="fa fa-money"></i> Total yang Harus Dibayar - <?php echo htmlspecialchars($prefill_invoice) ?></b></div>
                    <table class="table table-bordered" style="margin-bottom:0;">
                        <tr>
                            <th style="width:40%">Total Tagihan Sewa (Grand Total)</th>
                            <td>Rp <?php echo number_format($payment_info['grand_total'], 0, ',', '.') ?></td>
                        </tr>
                        <tr>
                            <th>Total Tagihan Deposit <small class="text-muted">(25% dari Grand Total)</small></th>
                            <td>Rp <?php echo number_format($payment_info['target_deposit'], 0, ',', '.') ?></td>
                        </tr>
                        <tr class="active">
                            <th>Total Keseluruhan yang Harus Dibayar <small class="text-muted">(Sewa + Deposit)</small></th>
                            <td><b>Rp <?php echo number_format($payment_info['total_keseluruhan_tagihan'], 0, ',', '.') ?></b></td>
                        </tr>
                        <tr>
                            <th>Total Sudah Dibayar <small class="text-muted">(Sewa + Deposit)</small></th>
                            <td>Rp <?php echo number_format($payment_info['total_keseluruhan_dibayar'], 0, ',', '.') ?></td>
                        </tr>
                        <tr>
                            <th>Sisa yang Masih Harus Dibayar</th>
                            <td><b class="text-danger">Rp <?php echo number_format($payment_info['sisa_keseluruhan'], 0, ',', '.') ?></b></td>
                        </tr>
                    </table>
                </div>
                <p class="text-muted"><small><i class="fa fa-info-circle"></i> Harga sudah termasuk PPN.</small></p>

                <div class="row">
                    <!-- Kategori 1: Sewa / Pelunasan (Grand Total) -->
                    <div class="col-md-6">
                        <div class="panel panel-success">
                            <div class="panel-heading"><b><i class="fa fa-home"></i> Sewa (Pelunasan)</b></div>
                            <table class="table table-bordered" style="margin-bottom:0;">
                                <tr>
                                    <th style="width:55%">Total Tagihan</th>
                                    <td>Rp <?php echo number_format($payment_info['grand_total'], 0, ',', '.') ?></td>
                                </tr>
                                <tr>
                                    <th>Sudah Dibayar</th>
                                    <td>Rp <?php echo number_format($payment_info['total_dibayar_pelunasan'], 0, ',', '.') ?></td>
                                </tr>
                                <tr>
                                    <th>Sisa Tagihan</th>
                                    <td><b class="text-success">Rp <?php echo number_format($payment_info['sisa_tagihan'], 0, ',', '.') ?></b></td>
                                </tr>
                                <tr>
                                    <th>Status</th>
                                    <td>
                                        <?php if ($payment_info['status_pelunasan'] == 'Lunas') { ?>
                                            <span class="label label-success">Lunas</span>
                                        <?php } else { ?>
                                            <span class="label label-warning">Belum Lunas</span>
                                        <?php } ?>
                                    </td>
                                </tr>
                            </table>
                        </div>
                    </div>

                    <!-- Kategori 2: Deposit / Jaminan -->
                    <div class="col-md-6">
                        <div class="panel panel-primary">
                            <div class="panel-heading"><b><i class="fa fa-shield"></i> Deposit (Jaminan)</b></div>
                            <table class="table table-bordered" style="margin-bottom:0;">
                                <tr>
                                    <th style="width:55%">Total Tagihan <small class="text-muted">(25%)</small></th>
                                    <td>Rp <?php echo number_format($payment_info['target_deposit'], 0, ',', '.') ?></td>
                                </tr>
                                <tr>
                                    <th>Sudah Dibayar</th>
                                    <td>Rp <?php echo number_format($payment_info['total_dibayar_deposit'], 0, ',', '.') ?></td>
                                </tr>
                                <tr>
                                    <th>Sisa Tagihan</th>
                                    <td><b class="text-primary">Rp <?php echo number_format($payment_info['sisa_deposit'], 0, ',', '.') ?></b></td>
                                </tr>
                                <tr>
                                    <th>Status</th>
                                    <td>
                                        <?php if ($payment_info['status_deposit'] == 'Lunas') { ?>
                                            <span class="label label-success">Lunas</span>
                                        <?php } else { ?>
                                            <span class="label label-warning">Belum Lunas</span>
                                        <?php } ?>
                                    </td>
                                </tr>
                                <?php if (!empty($payment_info['jatuh_tempo_deposit'])) { ?>
                                <tr>
                                    <th>Jatuh Tempo <small class="text-muted">(H+3 sejak booking)</small></th>
                                    <td><b class="text-danger"><?php echo date('d-m-Y', strtotime($payment_info['jatuh_tempo_deposit'])) ?></b></td>
                                </tr>
                                <?php } ?>
                            </table>
                        </div>
                    </div>
                </div>

                <?php if ($deposit_available || $pelunasan_available) { ?>
                <div class="alert alert-info">
                    <i class="fa fa-info-circle"></i> Pembayaran <b>Deposit</b> maupun <b>Sewa (Pelunasan)</b> boleh dilakukan bertahap/beberapa kali (cicilan) sampai nominal di atas tercukupi. Isi <b>Nominal Transfer</b> sesuai jumlah yang benar-benar Anda kirim pada transfer ini.
                </div>
                <?php } ?>

                <!-- Wajib menggunakan form_open_multipart agar bisa upload gambar -->
                <?php echo form_open_multipart('send', array('id'=>'contactForm')) ?>

                    <div class="row">
                        <div class="col-md-6">
                            <div class="form-group">
                                <label>No. Invoice <span class="text-danger">*</span></label>
                                <div class="controls">
                                    <input type="text" name="id_invoice" class="form-control" value="<?php echo htmlspecialchars($prefill_invoice) ?>" readonly style="background-color:#eee; cursor:not-allowed;">
                                </div>
                            </div>
                        </div>

                        <div class="col-md-6">
                            <div class="form-group">
                                <label>Jenis Konfirmasi <span class="text-danger">*</span></label>
                                <div class="controls">
                                    <?php if ($deposit_available && $pelunasan_available) { ?>
                                        <select name="jenis_konfirmasi" class="form-control" required>
                                            <option value="">-- Pilih Jenis --</option>
                                            <option value="deposit" <?php echo ($prefill_jenis == 'deposit') ? 'selected' : '' ?>>Konfirmasi Deposit (Jaminan)</option>
                                            <option value="pelunasan" <?php echo ($prefill_jenis == 'pelunasan') ? 'selected' : '' ?>>Konfirmasi Sewa (Pelunasan)</option>
                                        </select>
                                    <?php } else { ?>
                                        <!-- Hanya satu jenis yang tersedia - tampilkan langsung tanpa pilihan -->
                                        <input type="text" class="form-control" value="<?php echo $deposit_available ? 'Konfirmasi Deposit (Jaminan)' : 'Konfirmasi Sewa (Pelunasan)' ?>" readonly style="background-color:#eee; cursor:not-allowed;">
                                        <input type="hidden" name="jenis_konfirmasi" value="<?php echo $deposit_available ? 'deposit' : 'pelunasan' ?>">
                                    <?php } ?>
                                </div>
                            </div>
                        </div>
                    </div>

                    <div class="row">
                        <div class="col-md-6">
                            <div class="form-group">
                                <label>Nama Pengirim (Pemilik Rekening) <span class="text-danger">*</span></label>
                                <div class="controls">
                                    <input type="text" name="nama_pengirim" class="form-control" placeholder="Nama pada buku tabungan pengirim" required>
                                </div>
                            </div>
                        </div>

                        <div class="col-md-6">
                            <div class="form-group">
                                <label>Bank Pengirim <span class="text-danger">*</span></label>
                                <div class="controls">
                                    <input type="text" name="bank_pengirim" class="form-control" placeholder="Contoh: BCA / Mandiri / BNI / Dana" required>
                                </div>
                            </div>
                        </div>
                    </div>

                    <div class="row">
                        <div class="col-md-6">
                            <div class="form-group">
                                <label>Nominal Transfer <span class="text-danger">*</span></label>
                                <div class="controls">
                                    <input type="number" name="nominal" class="form-control" placeholder="Contoh: 150000 (Tanpa titik/koma)" required>
                                </div>
                            </div>
                        </div>
                    </div>

                    <div class="form-group">
                        <label>Upload Bukti Transfer <span class="text-danger">*</span></label>
                        <div class="controls">
                            <!-- Input file khusus untuk gambar -->
                            <input type="file" name="bukti_transfer" class="form-control" accept="image/png, image/jpeg, image/jpg" required>
                            <small class="text-muted">Format yang diizinkan: JPG, JPEG, PNG. Maksimal ukuran file 2MB.</small>
                        </div>
                    </div>

                    <div class="form-group">
                        <label>Catatan Tambahan (Opsional)</label>
                        <div class="controls">
                            <textarea name="catatan" class="form-control" rows="3" placeholder="Tambahkan catatan jika diperlukan..."></textarea>
                        </div>
                    </div>

                    <hr>
                    <button type="submit" id="submit" class="btn btn-success" style="pointer-events: all; cursor: pointer;">
                        <i class="fa fa-send"></i> Kirim Konfirmasi
                    </button>

                <?php echo form_close() ?>

            <?php } ?>
        </div>
        <?php /* $this->load->view('front/sidebar'); */ ?>
    </div>
</div>

<?php $this->load->view('front/footer'); ?>
