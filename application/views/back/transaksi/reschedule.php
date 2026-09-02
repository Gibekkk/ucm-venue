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
          <li class="active">Reschedule Booking</li>
        </ol>
      </section>
      <section class="content">
        <div class="row">
          <div class="col-lg-12">
            <div class="box box-warning">
              <div class="box-header with-border">
                <h3 class="box-title">Reschedule Booking - <?php echo $row->id_invoice ?> (<?php echo $row->guest_name ?>)</h3>
              </div>
              <div class="box-body">
                <?php if ($this->session->flashdata('message')) { echo $this->session->flashdata('message'); } ?>
                <?php echo validation_errors('<div class="alert alert-danger alert">', '</div>'); ?>

                <div class="alert alert-info">
                  <i class="fa fa-info-circle"></i> Ubah tanggal, jam, dan/atau lapangan pada baris venue di bawah ini. Sistem akan otomatis menolak perubahan yang bentrok dengan booking aktif lain di venue &amp; jam yang sama. Baris <b>Addon</b> tidak bisa dijadwal ulang (pakai Jumlah, bukan slot jam) dan ditampilkan sebagai referensi saja.
                  <br><br>
                  <b>Kebijakan Reschedule:</b>
                  <ul style="margin-bottom:0;">
                    <li>Reschedule hanya bisa dilakukan <b>1 kali saja</b> untuk setiap booking.</li>
                    <li>Tanggal baru maksimal <b>3 bulan</b> setelah tanggal booking yang sedang berjalan (per baris venue).</li>
                  </ul>
                </div>

                <?php echo form_open('admin/transaksi/reschedule_action') ?>
                  <input type="hidden" name="id_trans" value="<?php echo $row->id_trans ?>">

                  <div class="table-responsive">
                    <table class="table table-bordered">
                      <thead>
                        <tr>
                          <th>Lapangan</th>
                          <th>Tanggal</th>
                          <th>Jam Mulai</th>
                          <th>Durasi (Jam)</th>
                          <th>Jam Selesai Saat Ini</th>
                        </tr>
                      </thead>
                      <tbody>
                        <?php foreach ($details as $d) { ?>
                          <?php if ($d->is_addon == '1') { ?>
                            <tr class="bg-gray disabled" style="opacity:0.6;">
                              <td><?php echo $d->nama_lapangan ?> <span class="label label-default">Addon</span></td>
                              <td><?php echo date('d-m-Y', strtotime($d->tanggal)) ?></td>
                              <td>-</td>
                              <td><?php echo $d->durasi ?> (Jumlah)</td>
                              <td>-</td>
                            </tr>
                          <?php } else { ?>
                            <tr>
                              <td>
                                <input type="hidden" name="id_transdet[]" value="<?php echo $d->id_transdet ?>">
                                <select name="lapangan_id[]" class="form-control" required>
                                  <?php foreach ($lapangan_list as $l) { ?>
                                    <option value="<?php echo $l->id_lapangan ?>" <?php echo ($l->id_lapangan == $d->lapangan_id ? 'selected' : '') ?>><?php echo $l->nama_lapangan ?></option>
                                  <?php } ?>
                                </select>
                              </td>
                              <td>
                                <input type="date" name="tanggal[]" class="form-control" value="<?php echo $d->tanggal ?>" min="<?php echo date('Y-m-d') ?>" max="<?php echo date('Y-m-d', strtotime('+3 months', strtotime($d->tanggal))) ?>" required>
                                <small class="text-muted">Maks: <?php echo date('d-m-Y', strtotime('+3 months', strtotime($d->tanggal))) ?></small>
                              </td>
                              <td>
                                <select name="jam_mulai[]" class="form-control" required>
                                  <?php
                                    $jam_sudah_ada = false;
                                    foreach ($jam_list as $j) {
                                      if ($j->jam == $d->jam_mulai) $jam_sudah_ada = true;
                                    }
                                    // Kalau jam_mulai saat ini kebetulan sudah tidak ada di master jam
                                    // (mis. jam tidak lagi aktif), tetap tampilkan sebagai opsi supaya
                                    // tidak hilang tanpa sengaja saat form dibuka.
                                    if (!$jam_sudah_ada) { ?>
                                      <option value="<?php echo $d->jam_mulai ?>" selected><?php echo substr($d->jam_mulai, 0, 5) ?></option>
                                    <?php }
                                  ?>
                                  <?php foreach ($jam_list as $j) { ?>
                                    <option value="<?php echo $j->jam ?>" <?php echo ($j->jam == $d->jam_mulai ? 'selected' : '') ?>><?php echo substr($j->jam, 0, 5) ?></option>
                                  <?php } ?>
                                </select>
                              </td>
                              <td>
                                <input type="number" name="durasi[]" class="form-control" value="<?php echo $d->durasi ?>" min="1" required>
                              </td>
                              <td><?php echo substr($d->jam_selesai, 0, 5) ?></td>
                            </tr>
                          <?php } ?>
                        <?php } ?>
                      </tbody>
                    </table>
                  </div>

                  <button type="submit" class="btn btn-warning" onclick="return confirm('Simpan perubahan jadwal booking ini?')">
                    <i class="fa fa-calendar"></i> Simpan Reschedule
                  </button>
                  <a href="<?php echo base_url('admin/transaksi/detail/').$row->id_trans ?>" class="btn btn-default">Batal</a>
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
