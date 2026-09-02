<?php $this->load->view('back/meta') ?>
  <div class="wrapper">
    <?php $this->load->view('back/navbar') ?>
    <?php $this->load->view('back/sidebar') ?>
    <div class="content-wrapper">
      <section class="content-header">
        <h1><?php echo $title ?></h1>
        <ol class="breadcrumb">
          <li><a href="#"><i class="fa fa-dashboard"></i> Home</a></li>
          <li><a href="<?php echo base_url('admin/transaksi') ?>">Transaksi</a></li>
          <li class="active">Refund Deposit</li>
        </ol>
      </section>
      <section class="content">
        <div class="row">
          <div class="col-lg-6">
            <div class="box box-danger">
              <div class="box-header with-border">
                <h3 class="box-title">Refund Deposit - <?php echo $row->id_invoice ?></h3>
              </div>
              <div class="box-body">
                <div class="alert alert-warning">
                  <i class="fa fa-info-circle"></i> Tentukan nominal refund sesuai <b>SOP kampus (aturan hari)</b> secara manual, lalu catat di sini. Setelah disimpan, status transaksi akan berubah menjadi <b>REFUND</b>.
                </div>

                <table class="table table-bordered">
                  <tr>
                    <th style="width:40%">Atas Nama</th>
                    <td><?php echo $row->guest_name ?></td>
                  </tr>
                  <tr>
                    <th>Nama Acara</th>
                    <td><?php echo !empty($row->nama_acara) ? $row->nama_acara : '-' ?></td>
                  </tr>
                  <tr>
                    <th>Grand Total (Sewa) <small class="text-muted">(tagihan awal)</small></th>
                    <td>Rp <?php echo number_format($row->grand_total, 0, ',', '.') ?></td>
                  </tr>
                  <tr>
                    <th>Target Deposit <small class="text-muted">(25% dari Grand Total)</small></th>
                    <td>Rp <?php echo number_format($payment_info['target_deposit'], 0, ',', '.') ?></td>
                  </tr>
                  <tr>
                    <th>Status Saat Ini</th>
                    <td><?php echo isset(Transaksi::$status_list[$row->status]) ? Transaksi::$status_list[$row->status] : $row->status ?></td>
                  </tr>
                </table>

                <div class="row">
                  <div class="col-md-6">
                    <table class="table table-bordered">
                      <tr><th colspan="2" class="bg-green" style="color:white;">Sewa (Pelunasan)</th></tr>
                      <tr><th>Sudah Dibayar</th><td>Rp <?php echo number_format($payment_info['total_dibayar_pelunasan'], 0, ',', '.') ?></td></tr>
                      <tr><th>Sisa Tagihan</th><td>Rp <?php echo number_format($payment_info['sisa_tagihan'], 0, ',', '.') ?></td></tr>
                      <tr><th>Status</th><td>
                        <?php if ($payment_info['status_pelunasan'] == 'Lunas') { ?>
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
                      <tr><th>Sudah Dibayar</th><td>Rp <?php echo number_format($payment_info['total_dibayar_deposit'], 0, ',', '.') ?></td></tr>
                      <tr><th>Sisa Tagihan</th><td>Rp <?php echo number_format($payment_info['sisa_deposit'], 0, ',', '.') ?></td></tr>
                      <tr><th>Status</th><td>
                        <?php if ($payment_info['status_deposit'] == 'Lunas') { ?>
                          <span class="label label-success">Lunas</span>
                        <?php } else { ?>
                          <span class="label label-warning">Belum Lunas</span>
                        <?php } ?>
                      </td></tr>
                    </table>
                  </div>
                </div>

                <div class="alert alert-info">
                  <i class="fa fa-info-circle"></i> Gunakan angka <b>Sudah Dibayar</b> di atas (Sewa &amp; Deposit terpisah) sebagai acuan nominal yang benar-benar sudah masuk saat menentukan nominal refund.
                </div>

                <?php echo form_open('admin/transaksi/refund_deposit_action') ?>
                  <input type="hidden" name="id_trans" value="<?php echo $row->id_trans ?>">

                  <div class="form-group">
                    <label>Nominal Refund <span class="text-danger">*</span></label>
                    <input type="number" name="refund_amount" class="form-control" required placeholder="Contoh: 100000">
                  </div>

                  <div class="form-group">
                    <label>Catatan Refund (alasan / acuan SOP hari) <span class="text-danger">*</span></label>
                    <textarea name="refund_note" class="form-control" rows="3" required placeholder="Contoh: Pembatalan H-10, sesuai SOP refund 50%"></textarea>
                  </div>

                  <button type="submit" class="btn btn-danger" onclick="return confirm('Yakin ingin memproses refund ini? Status transaksi akan menjadi REFUND.')">
                    <i class="fa fa-undo"></i> Proses Refund
                  </button>
                  <a href="<?php echo base_url('admin/transaksi') ?>" class="btn btn-default">Batal</a>
                <?php echo form_close() ?>
              </div>
            </div>
          </div>
        </div>
      </section>
    </div>
    <?php $this->load->view('back/footer') ?>
  </div>
  <?php $this->load->view('back/js') ?>
</body>
</html>
