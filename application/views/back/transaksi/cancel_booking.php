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
          <li class="active">Batalkan Booking</li>
        </ol>
      </section>
      <section class="content">
        <div class="row">
          <div class="col-lg-6">
            <div class="box box-danger">
              <div class="box-header with-border">
                <h3 class="box-title">Batalkan Booking - <?php echo $row->id_invoice ?></h3>
              </div>
              <div class="box-body">
                <?php if ($this->session->flashdata('message')) { echo $this->session->flashdata('message'); } ?>

                <div class="alert alert-warning">
                  <i class="fa fa-exclamation-triangle"></i> Tindakan ini akan mengubah status transaksi menjadi <b>DIBATALKAN</b> dan tidak bisa dibatalkan/diurungkan lewat aplikasi. Alasan pembatalan wajib diisi sebagai catatan.
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
                    <th>Grand Total</th>
                    <td>Rp <?php echo number_format($row->grand_total, 0, ',', '.') ?></td>
                  </tr>
                  <tr>
                    <th>Status Saat Ini</th>
                    <td><?php echo isset(Transaksi::$status_list[$row->status]) ? Transaksi::$status_list[$row->status] : $row->status ?></td>
                  </tr>
                </table>

                <?php echo form_open('admin/transaksi/cancel_action') ?>
                  <input type="hidden" name="id_trans" value="<?php echo $row->id_trans ?>">

                  <div class="form-group">
                    <label>Alasan Pembatalan <span class="text-danger">*</span></label>
                    <textarea name="cancel_note" class="form-control" rows="3" required placeholder="Contoh: Dibatalkan atas permintaan tamu / venue tidak tersedia karena kendala teknis, dsb."></textarea>
                  </div>

                  <button type="submit" class="btn btn-danger" onclick="return confirm('Yakin ingin membatalkan booking ini? Status transaksi akan menjadi DIBATALKAN.')">
                    <i class="fa fa-ban"></i> Batalkan Booking
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
