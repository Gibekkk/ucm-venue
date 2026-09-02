<?php $this->load->view('back/meta') ?>
<div class="wrapper">
  <?php $this->load->view('back/navbar') ?>
  <?php $this->load->view('back/sidebar') ?>
  <!-- Content Wrapper. Contains page content -->
  <div class="content-wrapper">
    <!-- Content Header (Page header) -->
    <section class="content-header">
      <h1>INVOICE #<?php echo $cart_finished_row->id_invoice ?? 'N/A' ?></h1>
      <ol class="breadcrumb">
        <li><a href="#"><i class="fa fa-dashboard"></i> Home</a></li>
        <li><a href="#"><?php echo $module ?></a></li>
        <li class="active"><?php echo $title ?></li>
      </ol>
    </section>
    <div class="pad margin no-print">
      <div class="callout callout-info" style="margin-bottom: 0!important;">
        <h4><i class="fa fa-info"></i> Note:</h4>
        Halaman ini bisa langsung diprint dengan cara menekan tombol ctrl + p di keyboard
      </div>
    </div>
    <!-- Main content -->
    <section class="invoice">
      <!-- title row -->
      <div class="row">
        <div class="col-xs-12">
          <h2 class="page-header">
            <i class="fa fa-file-text-o"></i> Invoice: <?php echo $cart_finished_row->id_invoice ?? 'N/A' ?>
          </h2>
        </div><!-- /.col -->
      </div>
      <!-- info row -->
      <div class="row invoice-info">
        <div class="col-sm-4 invoice-col">
          Dari
          <address>
            <strong><?php echo $company_data->company_name ?></strong><br>
            <?php echo $company_data->company_address ?>
          </address>
        </div><!-- /.col -->
        <div class="col-sm-4 invoice-col">
          Kepada
          <address>
            <strong>
              <?php echo ($cart_finished_row->guest_name ?? 'N/A') . ' <span class="label label-info">Guest</span>' ?>
            </strong><br>
            <?php
              echo ($cart_finished_row->guest_address ?? '') . '<br>' .
                   ($cart_finished_row->guest_nama_kota ?? '') . ', ' .
                   ($cart_finished_row->guest_nama_provinsi ?? '') . '<br>' .
                   ($cart_finished_row->guest_email ?? '');
            ?>
          </address>
        </div><!-- /.col -->
        <div class="col-sm-4 invoice-col">
          Tanggal Pemesanan: <b><?php echo tgl_indo($cart_finished_row->created_date ?? '') ?></b><br />
          <?php if (!empty($cart_finished_row->nama_acara)) { ?>
            Nama Acara: <b><?php echo $cart_finished_row->nama_acara ?></b><br />
          <?php } ?>
          Status: <b>
            <?php if (($cart_finished_row->status ?? null) == '0') {
              echo "BELUM CHECKOUT";
            } elseif (($cart_finished_row->status ?? null) == '1') {
              echo "BELUM LUNAS";
            } elseif (($cart_finished_row->status ?? null) == '2') {
              echo "LUNAS DEPOSIT";
            } elseif (($cart_finished_row->status ?? null) == '3') {
              echo "EXPIRED";
            } elseif (($cart_finished_row->status ?? null) == '4') {
              echo "LUNAS PEMBAYARAN";
            } elseif (($cart_finished_row->status ?? null) == '5') {
              echo "REFUND";
            } elseif (($cart_finished_row->status ?? null) == '6') {
              echo "DIBATALKAN";
            }
            ?></b><br />
        </div><!-- /.col -->
      </div><!-- /.row -->

      <?php if (($row->status ?? null) == '6') { ?>
      <div class="row">
        <div class="col-xs-12">
          <div class="callout callout-danger">
            <h4><i class="fa fa-ban"></i> Booking Dibatalkan</h4>
            Alasan: <?php echo nl2br(htmlspecialchars($row->cancel_note ?? '-')) ?><br>
            Dibatalkan oleh: <b><?php echo $row->cancel_by ?? '-' ?></b>
            pada <?php echo !empty($row->cancel_date) ? date('d-m-Y H:i', strtotime($row->cancel_date)) : '-' ?>
          </div>
        </div>
      </div>
      <?php } ?>

      <?php if (($row->status ?? null) == '5') { ?>
      <div class="row">
        <div class="col-xs-12">
          <div class="callout callout-warning">
            <h4><i class="fa fa-undo"></i> Refund</h4>
            Nominal Refund: <b>Rp <?php echo number_format($row->refund_amount ?? 0) ?></b><br>
            Catatan: <?php echo nl2br(htmlspecialchars($row->refund_note ?? '-')) ?><br>
            Diproses oleh: <b><?php echo $row->refund_by ?? '-' ?></b>
            pada <?php echo !empty($row->refund_date) ? date('d-m-Y H:i', strtotime($row->refund_date)) : '-' ?>
          </div>
        </div>
      </div>
      <?php } ?>

      <?php if (!empty($npwp)) { ?>
      <div class="row">
        <div class="col-xs-12">
          <div class="callout callout-info">
            <h4><i class="fa fa-file-text"></i> Data NPWP</h4>
            No. NPWP: <b><?php echo !empty($npwp->nomor_npwp) ? $npwp->nomor_npwp : '-' ?></b><br>
            <?php if (!empty($npwp->npwp_image)) { ?>
              <a href="<?php echo base_url($npwp->npwp_image) ?>" target="_blank">Lihat berkas NPWP</a>
            <?php } ?>
          </div>
        </div>
      </div>
      <?php } ?>
      <!-- Table row -->
      <div class="row">
        <div class="col-xs-12">
          <div class="table-responsive">
            <table class="table table-striped">
              <thead>
                <tr>
                  <th style="text-align: center">No.</th>
                  <th style="text-align: center">Nama Lapangan</th>
                  <th style="text-align: center">Harga</th>
                  <th style="text-align: center">Tanggal</th>
                  <th style="text-align: center">Jumlah Mulai</th>
                  <th style="text-align: center">Durasi</th>
                  <th style="text-align: center">Jam Selesai</th>
                  <th style="text-align: center">Total</th>
                </tr>
              </thead>
              <tbody>
                <?php $no = 1;
                foreach ($cart_finished as $cart) { ?>
                  <tr>
                    <td style="text-align:center"><?php echo $no++ ?></td>
                    <td style="text-align:left"><?php echo $cart->nama_lapangan ?></td>
                    <td style="text-align:center"><?php echo number_format($cart->harga_jual) ?></td>
                    <td style="text-align:center"><?php echo $cart->tanggal ?></td>
                    <td style="text-align:center"><?php echo $cart->jam_mulai ?></td>
                    <td style="text-align:center"><?php echo $cart->durasi ?></td>
                    <td style="text-align:center"><?php echo $cart->jam_selesai ?></td>
                    <td style="text-align:right"><?php echo number_format($cart->subtotal) ?></td>
                  </tr>
                <?php } ?>
              </tbody>
            </table>
          </div>
        </div>
      </div>

      <div class="row">
        <div class="col-xs-12">
          <div class="table-responsive">
            <table class="table">
              <tr>
                <th>SubTotal</th>
                <td align="right">Rp</td>
                <td colspan="2" align="right"><?php echo number_format($cart_finished_row->subtotal ?? 0) ?></td>
              </tr>
              <tr>
                <th>Grand Total (Sewa) <small class="text-muted">(tagihan awal)</small></th>
                <td align="right">Rp</td>
                <td align="right"><?php echo number_format($cart_finished_row->grand_total ?? 0) ?></td>
              </tr>
              <tr>
                <th>Target Deposit <small class="text-muted">(25% dari Grand Total)</small></th>
                <td align="right">Rp</td>
                <td align="right"><?php echo number_format($payment_info['target_deposit'] ?? 0) ?></td>
              </tr>
              <tr class="active">
                <th>Total Keseluruhan yang Harus Dibayar <small class="text-muted">(Sewa + Deposit)</small></th>
                <td align="right">Rp</td>
                <td align="right"><b><?php echo number_format($payment_info['total_keseluruhan_tagihan'] ?? 0) ?></b></td>
              </tr>
            </table>

            <div class="row">
              <div class="col-md-6">
                <table class="table table-bordered">
                  <tr><th colspan="2" class="bg-green" style="color:white;">Sewa (Pelunasan)</th></tr>
                  <tr><th>Sudah Dibayar</th><td align="right">Rp <?php echo number_format($payment_info['total_dibayar_pelunasan'] ?? 0) ?></td></tr>
                  <tr><th>Sisa Tagihan</th><td align="right">Rp <?php echo number_format($payment_info['sisa_tagihan'] ?? 0) ?></td></tr>
                  <tr><th>Status</th><td align="right">
                    <?php if (($payment_info['status_pelunasan'] ?? '') == 'Lunas') { ?>
                      <span class="label label-success">Lunas</span>
                    <?php } else { ?>
                      <span class="label label-warning">Belum Lunas</span>
                    <?php } ?>
                  </td></tr>
                </table>
              </div>
              <div class="col-md-6">
                <table class="table table-bordered">
                  <tr><th colspan="2" class="bg-blue" style="color:white;">Deposit (Jaminan)</th></tr>
                  <tr><th>Sudah Dibayar</th><td align="right">Rp <?php echo number_format($payment_info['total_dibayar_deposit'] ?? 0) ?></td></tr>
                  <tr><th>Sisa Tagihan</th><td align="right">Rp <?php echo number_format($payment_info['sisa_deposit'] ?? 0) ?></td></tr>
                  <tr><th>Status</th><td align="right">
                    <?php if (($payment_info['status_deposit'] ?? '') == 'Lunas') { ?>
                      <span class="label label-success">Lunas</span>
                    <?php } else { ?>
                      <span class="label label-warning">Belum Lunas</span>
                    <?php } ?>
                  </td></tr>
                </table>
              </div>
            </div>
          </div>
          <b>Catatan:</b>
          <?php if (($cart_finished_row->catatan ?? null) != NULL) {
            echo $cart_finished_row->catatan;
          } else {
            echo "-";
          } ?>
          <br><br>
          <p><b>Hormat kami,</b>
            <br>Operator
          </p>
          <br><br><br><br>
          (<?php echo $this->session->userdata('username') ?>)
          <hr>
        </div><!-- /.col -->
      </div><!-- /.row -->

      <?php if (!empty($konfirmasi_list)) { ?>
      <div class="row">
        <div class="col-xs-12">
          <h4><i class="fa fa-money"></i> Riwayat Konfirmasi Pembayaran</h4>
          <div class="table-responsive">
            <table class="table table-bordered table-striped">
              <thead>
                <tr>
                  <th style="text-align:center">Waktu</th>
                  <th style="text-align:center">Jenis</th>
                  <th style="text-align:center">Nama Pengirim</th>
                  <th style="text-align:center">Bank</th>
                  <th style="text-align:center">Nominal</th>
                  <th style="text-align:center">Bukti</th>
                </tr>
              </thead>
              <tbody>
                <?php foreach ($konfirmasi_list as $k) { ?>
                  <tr>
                    <td style="text-align:center"><?php echo date('d-m-Y H:i', strtotime($k->created_at)) ?></td>
                    <td style="text-align:center">
                      <span class="label <?php echo ($k->jenis_konfirmasi == 'deposit' ? 'label-primary' : 'label-success') ?>">
                        <?php echo ($k->jenis_konfirmasi == 'deposit' ? 'DEPOSIT' : 'PELUNASAN') ?>
                      </span>
                    </td>
                    <td style="text-align:center"><?php echo htmlspecialchars($k->nama_pengirim) ?></td>
                    <td style="text-align:center"><?php echo htmlspecialchars($k->bank_pengirim) ?></td>
                    <td style="text-align:right">Rp <?php echo number_format($k->nominal) ?></td>
                    <td style="text-align:center">
                      <a href="<?php echo base_url('assets/images/bukti_transaksi/'.$k->bukti_transfer) ?>" target="_blank">Lihat</a>
                    </td>
                  </tr>
                <?php } ?>
              </tbody>
            </table>
          </div>
        </div>
      </div>
      <?php } ?>

      <!-- this row will not appear when printing -->
      <div class="row no-print">
        <div class="col-xs-12">
          <button onclick="window.print()" class="btn btn-default"><i class="fa fa-print"></i> Print</button>
          <?php if (isset($row) && !in_array($row->status, array('5', '6'))) { ?>
            <?php if (!empty($bisa_reschedule)) { ?>
              <a href="<?php echo base_url('admin/transaksi/reschedule/').$row->id_trans ?>" class="btn btn-warning">
                <i class="fa fa-calendar"></i> Reschedule
              </a>
            <?php } ?>
            <a href="<?php echo base_url('admin/transaksi/cancel/').$row->id_trans ?>" class="btn btn-danger">
              <i class="fa fa-ban"></i> Batalkan Booking
            </a>
          <?php } ?>
        </div>
      </div>
    </section><!-- /.content -->
    <div class="clearfix"></div>
  </div><!-- /.content-wrapper -->
  <?php $this->load->view('back/footer') ?>
</div>
<?php $this->load->view('back/js') ?>
</body>

</html>