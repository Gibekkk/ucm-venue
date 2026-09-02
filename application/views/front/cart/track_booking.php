<?php $this->load->view('front/header'); ?>
<?php $this->load->view('front/navbar'); ?>

<div class="container">
	<div class="row">
    <div class="col-sm-12 col-lg-12">
			<nav aria-label="breadcrumb">
			  <ol class="breadcrumb">
			    <li class="breadcrumb-item"><a href="<?php echo base_url() ?>"><i class="fa fa-home"></i> Home</a></li>
					<li class="breadcrumb-item active">Track Booking</li>
			  </ol>
			</nav>
    </div>

    <div class="col-lg-12">
			<h1><i class="fa fa-search"></i> TRACK BOOKING</h1>
			<hr>
			
			<?php if ($this->session->flashdata('message')) {
				echo $this->session->flashdata('message');
			} ?>
			
			<?php if (!$booking_found) { ?>
			<!-- Search Form -->
			<div class="row">
				<div class="col-lg-6 col-lg-offset-3">
					<div class="panel panel-primary">
						<div class="panel-heading">
							<h3 class="panel-title">Masukkan Data Booking Anda</h3>
						</div>
						<div class="panel-body">
							<?php echo form_open('cart/track_booking'); ?>
								<div class="form-group">
									<label>Email <span class="text-danger">*</span></label>
									<input type="email" name="email" class="form-control" required placeholder="contoh@email.com">
									<small class="text-muted">Email yang Anda gunakan saat booking</small>
								</div>
								<div class="form-group">
									<label>Kode Booking <span class="text-danger">*</span></label>
									<input type="text" name="booking_code" class="form-control" required placeholder="J-YYMMDD-0001">
									<small class="text-muted">Kode booking yang dikirim ke email Anda</small>
								</div>
								<button type="submit" name="search" value="1" class="btn btn-primary btn-block">
									<i class="fa fa-search"></i> Cari Booking
								</button>
							<?php echo form_close(); ?>
						</div>
					</div>
					
					<div class="alert alert-info">
						<h4><i class="fa fa-info-circle"></i> Informasi</h4>
						<ul>
							<li>Masukkan email dan kode booking yang telah dikirim ke email Anda</li>
							<li>Anda dapat melihat status pembayaran dan detail booking</li>
						</ul>
					</div>
				</div>
			</div>
			<?php } else { ?>
			
			<!-- Booking Details -->
			<div class="row">
				<div class="col-lg-12">
					<div class="alert alert-success">
						<h4><i class="fa fa-check-circle"></i> Booking Ditemukan!</h4>
					</div>
					
					<h3>Detail Booking</h3>
					<table class="table table-bordered">
						<tr>
							<th width="200">Kode Booking</th>
							<td><?php echo $booking->id_invoice; ?></td>
						</tr>
						<tr>
							<th>Nama</th>
							<td><?php echo $booking->guest_name; ?></td>
						</tr>
						<tr>
							<th>Email</th>
							<td><?php echo $booking->guest_email; ?></td>
						</tr>
						<tr>
							<th>No. HP</th>
							<td><?php echo $booking->guest_phone; ?></td>
						</tr>
						<tr>
							<th>Tanggal Booking</th>
							<td><?php echo date('d F Y, H:i', strtotime($booking->created_date . ' ' . $booking->created_time)); ?> WIB</td>
						</tr>
						<tr>
							<th>Status</th>
							<td>
								<?php
								if ($booking->status == '0') {
									echo '<span class="label label-warning">Belum Checkout</span>';
								} elseif ($booking->status == '1') {
									if (in_array('pelunasan', $submitted_jenis)) {
										echo '<span class="label label-info">Menunggu Konfirmasi Pelunasan</span>';
									} elseif (in_array('deposit', $submitted_jenis)) {
										echo '<span class="label label-info">Menunggu Konfirmasi Deposit</span>';
									} else {
										echo '<span class="label label-info">Belum Lunas</span>';
									}
								} elseif ($booking->status == '2') {
									if (in_array('pelunasan', $submitted_jenis)) {
										echo '<span class="label label-primary">Lunas Deposit</span> <span class="label label-info">Menunggu Konfirmasi Pelunasan</span>';
									} else {
										echo '<span class="label label-primary">Lunas Deposit</span>';
									}
								} elseif ($booking->status == '3') {
									echo '<span class="label label-default">Expired</span>';
								} elseif ($booking->status == '4') {
									echo '<span class="label label-success">Lunas Pembayaran</span>';
								} elseif ($booking->status == '5') {
									echo '<span class="label label-danger">Refund</span>';
								} elseif ($booking->status == '6') {
									echo '<span class="label label-default">Dibatalkan</span>';
								}
								?>
							</td>
						</tr>
						<?php if ($booking->deadline) { ?>
						<tr>
							<th>Batas Pembayaran Deposit</th>
							<td class="text-danger"><b><?php echo date('d F Y, H:i', strtotime($booking->deadline)); ?> WIB</b></td>
						</tr>
						<?php } ?>
						<tr>
							<th>Total Keseluruhan yang Harus Dibayar <small class="text-muted">(Sewa + Deposit)</small></th>
							<td><h4 class="text-success"><b>Rp <?php echo number_format($payment_info['total_keseluruhan_tagihan']); ?></b></h4></td>
						</tr>
						<tr>
							<th>Total Sudah Dibayar <small class="text-muted">(Sewa + Deposit)</small></th>
							<td><b>Rp <?php echo number_format($payment_info['total_keseluruhan_dibayar']); ?></b></td>
						</tr>
						<tr>
							<th>Sisa yang Masih Harus Dibayar</th>
							<td><b class="text-danger">Rp <?php echo number_format($payment_info['sisa_keseluruhan']); ?></b></td>
						</tr>
						<tr>
							<td colspan="2"><small class="text-muted"><i class="fa fa-info-circle"></i> Harga sudah termasuk PPN.</small></td>
						</tr>
						<?php if (!empty($npwp)) { ?>
						<tr>
							<th>NPWP</th>
							<td>
								<?php echo !empty($npwp->nomor_npwp) ? htmlspecialchars($npwp->nomor_npwp) : '-'; ?>
								<?php if (!empty($npwp->npwp_image)) { ?>
									&nbsp;(<a href="<?php echo base_url($npwp->npwp_image); ?>" target="_blank">Lihat berkas</a>)
								<?php } ?>
							</td>
						</tr>
						<?php } ?>
						<?php if ($booking->status == '6') { ?>
						<tr>
							<th>Alasan Dibatalkan</th>
							<td class="text-danger"><?php echo nl2br(htmlspecialchars($booking->cancel_note ?? '-')); ?></td>
						</tr>
						<?php } ?>
						<?php if ($booking->status == '5') { ?>
						<tr>
							<th>Nominal Refund</th>
							<td>Rp <?php echo number_format($booking->refund_amount ?? 0); ?></td>
						</tr>
						<?php } ?>
					</table>

					<div class="row">
						<div class="col-md-6">
							<div class="panel panel-success">
								<div class="panel-heading"><b><i class="fa fa-home"></i> Sewa (Pelunasan)</b></div>
								<table class="table table-bordered" style="margin-bottom:0;">
									<tr>
										<th style="width:55%">Total Tagihan</th>
										<td>Rp <?php echo number_format($payment_info['grand_total']); ?></td>
									</tr>
									<tr>
										<th>Sudah Dibayar</th>
										<td>Rp <?php echo number_format($payment_info['total_dibayar_pelunasan']); ?></td>
									</tr>
									<tr>
										<th>Sisa Tagihan</th>
										<td><b class="text-success">Rp <?php echo number_format($payment_info['sisa_tagihan']); ?></b></td>
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
						<div class="col-md-6">
							<div class="panel panel-primary">
								<div class="panel-heading"><b><i class="fa fa-shield"></i> Deposit (Jaminan)</b></div>
								<table class="table table-bordered" style="margin-bottom:0;">
									<tr>
										<th style="width:55%">Total Tagihan <small class="text-muted">(25%)</small></th>
										<td>Rp <?php echo number_format($payment_info['target_deposit']); ?></td>
									</tr>
									<tr>
										<th>Sudah Dibayar</th>
										<td>Rp <?php echo number_format($payment_info['total_dibayar_deposit']); ?></td>
									</tr>
									<tr>
										<th>Sisa Tagihan</th>
										<td><b class="text-primary">Rp <?php echo number_format($payment_info['sisa_deposit']); ?></b></td>
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
								</table>
							</div>
						</div>
					</div>
					
					<h3>Detail Lapangan</h3>
					<div class="table-responsive">
						<table class="table table-striped table-bordered">
							<thead>
								<tr>
									<th>No.</th>
									<th>Lapangan</th>
									<th>Tanggal</th>
									<th>Jam Mulai</th>
									<th>Durasi</th>
									<th>Jam Selesai</th>
									<th>Harga</th>
									<th>Total</th>
								</tr>
							</thead>
							<tbody>
								<?php $no = 1; foreach ($booking_details as $detail) { ?>
								<tr>
									<td><?php echo $no++; ?></td>
									<td><?php echo $detail->nama_lapangan; ?></td>
									<td><?php echo date('d F Y', strtotime($detail->tanggal)); ?></td>
									<td><?php echo $detail->jam_mulai; ?></td>
									<td><?php echo $detail->durasi; ?> Jam</td>
									<td><?php echo $detail->jam_selesai; ?></td>
									<td>Rp <?php echo number_format($detail->harga_jual); ?></td>
									<td>Rp <?php echo number_format($detail->total); ?></td>
								</tr>
								<?php } ?>
							</tbody>
						</table>
					</div>
					
					<?php if (!empty($konfirmasi_list)) { ?>
					<h3>Riwayat Pembayaran</h3>
					<div class="table-responsive">
						<table class="table table-striped table-bordered">
							<thead>
								<tr>
									<th>Jenis</th>
									<th>Nominal</th>
									<th>Bank Pengirim</th>
									<th>Waktu Submit</th>
									<th>Status Verifikasi</th>
								</tr>
							</thead>
							<tbody>
								<?php foreach ($konfirmasi_list as $k) { ?>
								<tr>
									<td>
										<?php if ($k->jenis_konfirmasi == 'deposit') { ?>
											<span class="label label-primary">Deposit</span>
										<?php } else { ?>
											<span class="label label-success">Pelunasan</span>
										<?php } ?>
									</td>
									<td>Rp <?php echo number_format($k->nominal); ?></td>
									<td><?php echo htmlspecialchars($k->bank_pengirim); ?></td>
									<td><?php echo date('d F Y, H:i', strtotime($k->created_at)); ?> WIB</td>
									<td>
										<?php
										// Deposit dianggap terverifikasi kalau status sudah lewat dari Belum Lunas (2/4).
										// Pelunasan dianggap terverifikasi kalau status sudah Lunas Pembayaran (4).
										if ($k->jenis_konfirmasi == 'deposit') {
											if (in_array($booking->status, array('2', '4'))) {
												echo '<span class="label label-success">Terverifikasi</span>';
											} elseif ($booking->status == '1') {
												echo '<span class="label label-warning">Menunggu Verifikasi</span>';
											} else {
												echo '<span class="label label-default">-</span>';
											}
										} else {
											if ($booking->status == '4') {
												echo '<span class="label label-success">Terverifikasi</span>';
											} elseif (in_array($booking->status, array('1', '2'))) {
												echo '<span class="label label-warning">Menunggu Verifikasi</span>';
											} else {
												echo '<span class="label label-default">-</span>';
											}
										}
										?>
									</td>
								</tr>
								<?php } ?>
							</tbody>
						</table>
					</div>
					<?php } ?>

					<?php
						// Tombol disembunyikan kalau booking sudah tidak aktif (Expired/Lunas
						// Pembayaran/Refund/Dibatalkan) ATAU kedua kewajiban (Sewa & Deposit)
						// sudah lunas semua.
						$show_confirm_button = !in_array($booking->status, array('3', '5', '6'))
							&& ($payment_info['status_pelunasan'] != 'Lunas' || $payment_info['status_deposit'] != 'Lunas');
					?>
					<?php if ($show_confirm_button) { ?>
					<div class="alert alert-info">
						<h4><i class="fa fa-info-circle"></i> Perhatian</h4>
						<p>Untuk konfirmasi pembayaran/deposit, silakan isi form konfirmasi dengan menyertakan kode booking dan bukti transfer.</p>
						<a href="<?php echo base_url('confirm?id_invoice=' . urlencode($booking->id_invoice)) ?>" class="btn btn-primary">
							<i class="fa fa-credit-card"></i> Konfirmasi Pembayaran
						</a>
					</div>
					<?php } ?>
					
					<a href="<?php echo base_url('cart/track_booking') ?>" class="btn btn-default">
						<i class="fa fa-search"></i> Cari Booking Lain
					</a>
				</div>
			</div>
			
			<?php } ?>
	  </div>
  </div>
</div>

<?php $this->load->view('front/footer'); ?>
