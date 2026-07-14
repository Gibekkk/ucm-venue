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
							<li>Sudah punya akun? <a href="<?php echo base_url('auth/login') ?>">Login disini</a> untuk tracking lebih mudah</li>
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
									echo '<span class="label label-info">Menunggu Pembayaran</span>';
								} elseif ($booking->status == '2') {
									echo '<span class="label label-success">Lunas</span>';
								} elseif ($booking->status == '3') {
									echo '<span class="label label-danger">Dibatalkan</span>';
								}
								?>
							</td>
						</tr>
						<?php if ($booking->deadline) { ?>
						<tr>
							<th>Batas Pembayaran</th>
							<td class="text-danger"><b><?php echo date('d F Y, H:i', strtotime($booking->deadline)); ?> WIB</b></td>
						</tr>
						<?php } ?>
						<tr>
							<th>Grand Total</th>
							<td><h4 class="text-success"><b>Rp <?php echo number_format($booking->grand_total); ?></b></h4></td>
						</tr>
					</table>
					
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
					
					<div class="alert alert-info">
						<h4><i class="fa fa-info-circle"></i> Perhatian</h4>
						<p>Untuk konfirmasi pembayaran, silakan hubungi customer service kami dengan menyertakan kode booking dan bukti transfer.</p>
						<a href="<?php echo base_url('contact') ?>" class="btn btn-primary">
							<i class="fa fa-phone"></i> Hubungi Kami
						</a>
					</div>
					
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
