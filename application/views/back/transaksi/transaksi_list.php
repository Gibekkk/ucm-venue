<?php $this->load->view('back/meta') ?>
  <div class="wrapper">
    <?php $this->load->view('back/navbar') ?>
    <?php $this->load->view('back/sidebar') ?>
    <div class="content-wrapper">
      <section class="content-header">
        <h1><?php echo $title ?></h1>
        <ol class="breadcrumb">
          <li><a href="#"><i class="fa fa-dashboard"></i> Home</a></li>
          <li><a href="#"><?php echo $module ?></a></li>
          <li class="active"><?php echo $title ?></li>
        </ol>
      </section>
      <section class="content">
        <div class="row">
          <div class="col-lg-12">
            <div class="box box-primary">
              <div class="box-header">
                <?php echo form_open('admin/transaksi', array('method' => 'get', 'class' => 'form-inline')) ?>
                  <div class="form-group">
                    <label>Filter Status:</label>
                    <select name="status" class="form-control" onchange="this.form.submit()">
                      <option value="active" <?php echo ($status_filter === 'active' ? 'selected' : '') ?>>-- Perlu Tindak Lanjut (Belum Lunas + Lunas Deposit) --</option>
                      <option value="" <?php echo ($status_filter === '' ? 'selected' : '') ?>>-- Semua Status --</option>
                      <?php foreach ($status_list as $val => $label) { ?>
                        <option value="<?php echo $val ?>" <?php echo ($status_filter === $val ? 'selected' : '') ?>><?php echo $label ?></option>
                      <?php } ?>
                    </select>
                  </div>
                <?php echo form_close() ?>
              </div>
              <div class="box-body">
                <?php if ($this->session->flashdata('message')) { echo $this->session->flashdata('message'); } ?>
                <div class="table-responsive no-padding">
                  <table id="datatable" class="table table-striped">
                    <thead>
                      <tr>
                        <th style="text-align: center">No.</th>
                        <th style="text-align: center">Invoice</th>
                        <th style="text-align: center">Atas Nama</th>
                        <th style="text-align: center">Email</th>
                        <th style="text-align: center">Nama Acara</th>
                        <th style="text-align: center">Tanggal Acara</th>
                        <th style="text-align: center">Dibuat</th>
                        <th style="text-align: center">Grand Total</th>
                        <th style="text-align: center">Status</th>
                        <th style="text-align: center">Aksi</th>
                      </tr>
                    </thead>
                    <tbody>
                    <?php $no=1; foreach ($get_all as $data){ ?>
                      <tr>
                        <td style="text-align:center; vertical-align:middle;"><?php echo $no++ ?></td>
                        <td style="text-align:center; vertical-align:middle;">
                          <b><?php echo $data->id_invoice ?></b>
                          <?php if (!empty($data->jumlah_npwp)) { ?>
                            <br><span class="label label-default" title="No. NPWP: <?php echo !empty($data->nomor_npwp) ? htmlspecialchars($data->nomor_npwp) : '-' ?>"><i class="fa fa-file-text"></i> NPWP<?php echo !empty($data->nomor_npwp) ? ': ' . htmlspecialchars($data->nomor_npwp) : '' ?></span>
                          <?php } ?>
                        </td>
                        <td style="text-align:center; vertical-align:middle;">
                          <?php echo $data->guest_name ?> <br><span class="label label-info">Guest</span>
                        </td>
                        <td style="text-align:center; vertical-align:middle;"><?php echo !empty($data->guest_email) ? $data->guest_email : '-' ?></td>
                        <td style="text-align:center; vertical-align:middle;"><?php echo !empty($data->nama_acara) ? $data->nama_acara : '-' ?></td>
                        <td style="text-align:center; vertical-align:middle;"><?php echo !empty($data->tanggal_acara) ? tgl_indo($data->tanggal_acara) : '-' ?></td>
                        <td style="text-align:center; vertical-align:middle;"><?php echo tgl_indo($data->created_date) ?></td>
                        <td style="text-align:center; vertical-align:middle;">Rp <?php echo number_format($data->grand_total, 0, ',', '.') ?></td>
                        <td style="text-align:center; vertical-align:middle;">
                          <?php if($data->status == '1'){ ?>
                            <span class="label label-warning"><i class="fa fa-minus-circle"></i> BELUM LUNAS</span>
                          <?php } elseif($data->status == '2'){ ?>
                            <span class="label label-primary"><i class="fa fa-check"></i> LUNAS DEPOSIT</span>
                          <?php } elseif($data->status == '3'){ ?>
                            <span class="label label-default"><i class="fa fa-clock-o"></i> EXPIRED</span>
                          <?php } elseif($data->status == '4'){ ?>
                            <span class="label label-success"><i class="fa fa-check-circle"></i> LUNAS PEMBAYARAN</span>
                          <?php } elseif($data->status == '5'){ ?>
                            <span class="label label-danger"><i class="fa fa-undo"></i> REFUND</span>
                          <?php } elseif($data->status == '6'){ ?>
                            <span class="label label-default" style="background-color:#7f8c8d"><i class="fa fa-ban"></i> DIBATALKAN</span>
                          <?php } ?>

                          <?php // Badge "Menunggu Validasi" dicocokkan ke jenis yang MEMANG masih relevan untuk
                          // status transaksi saat ini (pakai deposit_bukti_transfer / pelunasan_bukti_transfer,
                          // sama seperti syarat tombol Set Lunas di bawah) - bukan cuma konfirmasi TERAKHIR
                          // apapun jenisnya. Supaya transaksi yang sudah LUNAS DEPOSIT tidak lagi kelihatan
                          // "butuh konfirmasi Deposit" meski ada histori/percobaan kirim ulang konfirmasi deposit. ?>
                          <?php if($data->status == '1' && !empty($data->deposit_bukti_transfer)): ?>
                              <br><span class="label label-info" style="display:inline-block; margin-top:5px;"><i class="fa fa-clock-o"></i> Menunggu Validasi Deposit</span>
                          <?php elseif(in_array($data->status, array('1','2')) && !empty($data->pelunasan_bukti_transfer)): ?>
                              <br><span class="label label-info" style="display:inline-block; margin-top:5px;"><i class="fa fa-clock-o"></i> Menunggu Validasi Pelunasan</span>
                          <?php endif; ?>
                        </td>
                        <td style="text-align:center; vertical-align:middle;">

                          <?php if(!empty($data->deposit_bukti_transfer)){ ?>
                            <button type="button" class="btn btn-sm btn-info" data-toggle="modal" data-target="#modalBuktiDeposit<?php echo $data->id_trans ?>" title="Lihat Bukti Transfer Deposit">
                                <i class="fa fa-image"></i> Bukti Deposit
                            </button>
                          <?php } ?>

                          <?php if(!empty($data->pelunasan_bukti_transfer)){ ?>
                            <button type="button" class="btn btn-sm btn-info" data-toggle="modal" data-target="#modalBuktiPelunasan<?php echo $data->id_trans ?>" title="Lihat Bukti Transfer Pelunasan">
                                <i class="fa fa-image"></i> Bukti Pelunasan
                            </button>
                          <?php } ?>

                          <br>

                          <?php if(in_array($data->status, array('1')) && !empty($data->deposit_bukti_transfer)){ ?>
                            <a href="<?php echo base_url('admin/transaksi/set_lunas_deposit/').$data->id_trans ?>" onclick="return confirm('Yakin ingin menandai transaksi ini sebagai LUNAS DEPOSIT?')">
                              <button class="btn btn-sm btn-primary"><i class="fa fa-check"></i> Set Lunas Deposit</button>
                            </a>
                          <?php } ?>

                          <?php if(in_array($data->status, array('1','2')) && !empty($data->pelunasan_bukti_transfer)){ ?>
                            <a href="<?php echo base_url('admin/transaksi/set_lunas/').$data->id_trans ?>" onclick="return confirm('Yakin ingin menandai transaksi ini sebagai LUNAS PEMBAYARAN?')">
                              <button class="btn btn-sm btn-success"><i class="fa fa-check-circle"></i> Set Lunas Pembayaran</button>
                            </a>
                          <?php } ?>

                          <?php if(in_array($data->status, array('2','4'))){ ?>
                            <a href="<?php echo base_url('admin/transaksi/refund_deposit/').$data->id_trans ?>">
                              <button class="btn btn-sm btn-danger"><i class="fa fa-undo"></i> Refund</button>
                            </a>
                          <?php } ?>

                          <?php
                            $bisa_reschedule = in_array($data->status, array('1','2','4'))
                              && (empty($data->jumlah_reschedule) || $data->jumlah_reschedule == 0)
                              && (!empty($data->tanggal_acara) && ((strtotime($data->tanggal_acara) - strtotime(date('Y-m-d'))) / 86400) >= 14);
                          ?>
                          <?php if($bisa_reschedule){ ?>
                            <a href="<?php echo base_url('admin/transaksi/reschedule/').$data->id_trans ?>">
                              <button class="btn btn-sm btn-warning"><i class="fa fa-calendar"></i> Reschedule</button>
                            </a>
                          <?php } ?>

                          <?php if(!in_array($data->status, array('5','6'))){ ?>
                            <a href="<?php echo base_url('admin/transaksi/cancel/').$data->id_trans ?>">
                              <button class="btn btn-sm btn-default" style="color:#a94442; border-color:#a94442;"><i class="fa fa-ban"></i> Batalkan</button>
                            </a>
                          <?php } ?>

                          <a href="<?php echo base_url('admin/transaksi/detail/').$data->id_trans ?>">
                            <button class="btn btn-sm btn-primary"><i class="fa fa-search-plus"></i> Detail</button>
                          </a>

                        </td>
                      </tr>
                    <?php } ?>
                    </tbody>
                  </table>
                </div>
              </div>
            </div>
          </div>
        </div>

        <?php
          // Dua jenis bukti pembayaran (Deposit & Pelunasan) ditampilkan sebagai modal
          // TERPISAH per baris transaksi, supaya kalau tamu sudah mengirim konfirmasi
          // deposit DAN pelunasan, admin tetap bisa cek kedua buktinya dari tabel -
          // tidak cuma bukti konfirmasi yang PALING TERAKHIR dikirim seperti sebelumnya.
          $bukti_jenis_config = array(
            'deposit'   => array(
              'field_prefix'  => 'deposit',
              'label'         => 'Deposit',
              'label_class'   => 'label-primary',
              'header_class'  => 'bg-blue',
            ),
            'pelunasan' => array(
              'field_prefix'  => 'pelunasan',
              'label'         => 'Pelunasan',
              'label_class'   => 'label-success',
              'header_class'  => 'bg-green',
            ),
          );
        ?>
        <?php foreach ($get_all as $data){ ?>
          <?php foreach ($bukti_jenis_config as $jenis => $cfg) {
            $f_bukti    = $cfg['field_prefix'] . '_bukti_transfer';
            $f_nama     = $cfg['field_prefix'] . '_nama_pengirim';
            $f_bank     = $cfg['field_prefix'] . '_bank_pengirim';
            $f_nominal  = $cfg['field_prefix'] . '_nominal';
            $f_catatan  = $cfg['field_prefix'] . '_catatan';
            $f_tgl      = $cfg['field_prefix'] . '_tgl';
            $modal_id   = 'modalBukti' . ucfirst($jenis) . $data->id_trans;
          ?>
          <?php if (!empty($data->$f_bukti)) { ?>
          <div class="modal fade" id="<?php echo $modal_id ?>" tabindex="-1" role="dialog" aria-labelledby="<?php echo $modal_id ?>Label">
            <div class="modal-dialog modal-lg" role="document">
              <div class="modal-content">
                <div class="modal-header <?php echo $cfg['header_class'] ?>">
                  <button type="button" class="close" data-dismiss="modal" aria-label="Close" style="color:white; opacity:1;"><span aria-hidden="true">&times;</span></button>
                  <h4 class="modal-title" id="<?php echo $modal_id ?>Label"><i class="fa fa-money"></i> Detail Konfirmasi <?php echo $cfg['label'] ?> - <b><?php echo $data->id_invoice ?></b></h4>
                </div>
                <div class="modal-body">
                  <div class="row">
                    <div class="col-md-6">
                      <table class="table table-bordered table-striped">
                        <tr>
                          <th style="width: 40%;">Invoice</th>
                          <td><b><?php echo $data->id_invoice ?></b></td>
                        </tr>
                        <tr>
                          <th>Jenis Konfirmasi</th>
                          <td><span class="label <?php echo $cfg['label_class'] ?>"><?php echo strtoupper($cfg['label']) ?></span></td>
                        </tr>
                        <tr>
                          <th>Nama Pengirim</th>
                          <td><?php echo isset($data->$f_nama) ? htmlspecialchars($data->$f_nama) : '-' ?></td>
                        </tr>
                        <tr>
                          <th>Bank Asal</th>
                          <td><?php echo isset($data->$f_bank) ? htmlspecialchars($data->$f_bank) : '-' ?></td>
                        </tr>
                        <tr>
                          <th>Nominal Transfer</th>
                          <td><b class="text-success">Rp <?php echo isset($data->$f_nominal) ? number_format($data->$f_nominal, 0, ',', '.') : '-' ?></b></td>
                        </tr>
                        <tr>
                          <th>Tagihan Seharusnya</th>
                          <td><b>Rp <?php echo number_format($data->grand_total, 0, ',', '.') ?></b></td>
                        </tr>
                        <tr>
                          <th>Waktu Submit</th>
                          <td><?php echo isset($data->$f_tgl) ? date('d-m-Y H:i:s', strtotime($data->$f_tgl)) : '-' ?></td>
                        </tr>
                        <tr>
                          <th>Catatan Tambahan</th>
                          <td><?php echo !empty($data->$f_catatan) ? nl2br(htmlspecialchars($data->$f_catatan)) : '-' ?></td>
                        </tr>
                      </table>
                    </div>
                    <div class="col-md-6 text-center">
                      <label style="display:block; margin-bottom:10px;"><i class="fa fa-search-plus"></i> Foto Bukti Transfer</label>
                      <a href="<?php echo base_url('assets/images/bukti_transaksi/'.$data->$f_bukti) ?>" target="_blank" title="Klik untuk melihat ukuran penuh">
                        <img src="<?php echo base_url('assets/images/bukti_transaksi/'.$data->$f_bukti) ?>" class="img-responsive img-thumbnail" style="max-height: 300px; cursor: pointer;" alt="Bukti Transfer">
                      </a>
                      <p class="text-muted" style="margin-top:10px;"><small><i>* Klik pada gambar untuk memperbesar secara layar penuh</i></small></p>
                    </div>
                  </div>
                </div>
                <div class="modal-footer">
                  <button type="button" class="btn btn-default pull-left" data-dismiss="modal">Tutup</button>
                  <?php if($jenis == 'deposit' && $data->status == '1' && !empty($data->deposit_bukti_transfer)){ ?>
                    <a href="<?php echo base_url('admin/transaksi/set_lunas_deposit/').$data->id_trans ?>" class="btn btn-primary" onclick="return confirm('Apakah dana sudah diverifikasi masuk? Klik OK untuk Set Lunas Deposit.')">
                      <i class="fa fa-check"></i> Validasi & Set Lunas Deposit
                    </a>
                  <?php } ?>
                  <?php if($jenis == 'pelunasan' && in_array($data->status, array('1','2')) && !empty($data->pelunasan_bukti_transfer)){ ?>
                    <a href="<?php echo base_url('admin/transaksi/set_lunas/').$data->id_trans ?>" class="btn btn-success" onclick="return confirm('Apakah dana sudah diverifikasi masuk? Klik OK untuk Set Lunas Pembayaran.')">
                      <i class="fa fa-check-circle"></i> Validasi & Set Lunas Pembayaran
                    </a>
                  <?php } ?>
                </div>
              </div>
            </div>
          </div>
          <?php } ?>
          <?php } ?>
        <?php } ?>
        </section>
    </div>
    <?php $this->load->view('back/footer') ?>
  </div>
  <?php $this->load->view('back/js') ?>
  <link href="<?php echo base_url('assets/plugins/') ?>datatables/dataTables.bootstrap.css" rel="stylesheet" type="text/css" />
  <script src="<?php echo base_url('assets/plugins/') ?>datatables/jquery.dataTables.min.js" type="text/javascript"></script>
  <script src="<?php echo base_url('assets/plugins/') ?>datatables/dataTables.bootstrap.min.js" type="text/javascript"></script>
  <script type="text/javascript">
  $('#datatable').dataTable({
    "bPaginate": true,
    "bLengthChange": true,
    "bFilter": true,
    "bSort": true,
    "bInfo": true,
    "bAutoWidth": false,
    "aaSorting": [[0,'desc']],
    "lengthMenu": [[10, 25, 50, 100, 500, 1000, -1], [10, 25, 50, 100, 500, 1000, "Semua"]]
  });
  </script>
</body>
</html>
