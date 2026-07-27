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
              <div class="box-body">
                <?php echo $this->session->userdata('message') <> '' ? $this->session->userdata('message') : ''; ?>
                <div class="table-responsive no-padding">
                  <table id="datatable" class="table table-striped">
                    <thead>
                      <tr>
                        <th style="text-align: center">No.</th>
                        <th style="text-align: center">Invoice</th>
                        <th style="text-align: center">Atas Nama</th>
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
                        <td style="text-align:center; vertical-align:middle;"><b><?php echo $data->id_invoice ?></b></td>
                        <td style="text-align:center; vertical-align:middle;">
                          <?php 
                            if ($data->user_id == NULL) {
                              echo $data->guest_name . ' <br><span class="label label-info">Guest</span>';
                            } else {
                              echo $data->name;
                            }
                          ?>
                        </td>
                        <td style="text-align:center; vertical-align:middle;"><?php echo tgl_indo($data->created_date) ?></td>
                        <td style="text-align:center; vertical-align:middle;">Rp <?php echo number_format($data->grand_total, 0, ',', '.') ?></td>
                        <td style="text-align:center; vertical-align:middle;">
                          <?php if($data->status == '0'){ ?>
                            <button type="button" class="btn btn-sm btn-primary"><i class="fa fa-ban"></i> BELUM CHECKOUT</button>
                          <?php } elseif($data->status == '1'){ ?>
                            <button type="button" class="btn btn-sm btn-warning"><i class="fa fa-minus-circle"></i> BELUM LUNAS</button>
                            
                            <?php if(!empty($data->bukti_transfer)): ?>
                                <br><span class="label label-info" style="display:inline-block; margin-top:5px;"><i class="fa fa-clock-o"></i> Menunggu Validasi</span>
                            <?php endif; ?>

                          <?php } elseif($data->status == '2'){ ?>
                            <button type="button" class="btn btn-sm btn-success"><i class="fa fa-check"></i> LUNAS</button>
                          <?php } elseif($data->status == '3'){ ?>
                            <button type="button" class="btn btn-sm btn-danger"><i class="fa fa-remove"></i> EXPIRED</button>
                          <?php } ?>
                        </td>
                        <td style="text-align:center; vertical-align:middle;">
                          
                          <?php if(!empty($data->bukti_transfer)){ ?>
                            <button type="button" class="btn btn-sm btn-info" data-toggle="modal" data-target="#modalBukti<?php echo $data->id_trans ?>" title="Lihat Bukti Transfer">
                                <i class="fa fa-image"></i> Cek Bukti
                            </button>
                          <?php } ?>

                          <?php if($data->status != '2'){ ?>
                            <a href="<?php echo base_url('admin/transaksi/set_lunas/').$data->id_trans ?>" onclick="return confirm('Yakin ingin menandai transaksi ini sebagai LUNAS?')">
                              <button class="btn btn-sm btn-success"><i class="fa fa-check"></i> Set Lunas</button>
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

        <?php foreach ($get_all as $data){ ?>
          <?php if(!empty($data->bukti_transfer)){ ?>
          <div class="modal fade" id="modalBukti<?php echo $data->id_trans ?>" tabindex="-1" role="dialog" aria-labelledby="modalLabel<?php echo $data->id_trans ?>">
            <div class="modal-dialog modal-lg" role="document">
              <div class="modal-content">
                <div class="modal-header bg-blue">
                  <button type="button" class="close" data-dismiss="modal" aria-label="Close" style="color:white; opacity:1;"><span aria-hidden="true">&times;</span></button>
                  <h4 class="modal-title" id="modalLabel<?php echo $data->id_trans ?>"><i class="fa fa-money"></i> Detail Konfirmasi Pembayaran - <b><?php echo $data->id_invoice ?></b></h4>
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
                          <th>Nama Pengirim</th>
                          <td><?php echo isset($data->nama_pengirim) ? htmlspecialchars($data->nama_pengirim) : '-' ?></td>
                        </tr>
                        <tr>
                          <th>Bank Asal</th>
                          <td><?php echo isset($data->bank_pengirim) ? htmlspecialchars($data->bank_pengirim) : '-' ?></td>
                        </tr>
                        <tr>
                          <th>Nominal Transfer</th>
                          <td><b class="text-success">Rp <?php echo isset($data->nominal_konfirmasi) ? number_format($data->nominal_konfirmasi, 0, ',', '.') : '-' ?></b></td>
                        </tr>
                        <tr>
                          <th>Tagihan Seharusnya</th>
                          <td><b>Rp <?php echo number_format($data->grand_total, 0, ',', '.') ?></b></td>
                        </tr>
                        <tr>
                          <th>Waktu Submit</th>
                          <td><?php echo isset($data->tgl_konfirmasi) ? date('d-m-Y H:i:s', strtotime($data->tgl_konfirmasi)) : '-' ?></td>
                        </tr>
                        <tr>
                          <th>Catatan Tambahan</th>
                          <td><?php echo !empty($data->catatan_konfirmasi) ? nl2br(htmlspecialchars($data->catatan_konfirmasi)) : '-' ?></td>
                        </tr>
                      </table>
                    </div>
                    <div class="col-md-6 text-center">
                      <label style="display:block; margin-bottom:10px;"><i class="fa fa-search-plus"></i> Foto Bukti Transfer</label>
                      <a href="<?php echo base_url('assets/images/'.$data->bukti_transfer) ?>" target="_blank" title="Klik untuk melihat ukuran penuh">
                        <img src="<?php echo base_url('assets/images/'.$data->bukti_transfer) ?>" class="img-responsive img-thumbnail" style="max-height: 300px; cursor: pointer;" alt="Bukti Transfer">
                      </a>
                      <p class="text-muted" style="margin-top:10px;"><small><i>* Klik pada gambar untuk memperbesar secara layar penuh</i></small></p>
                    </div>
                  </div>
                </div>
                <div class="modal-footer">
                  <button type="button" class="btn btn-default pull-left" data-dismiss="modal">Tutup</button>
                  <?php if($data->status != '2'){ ?>
                    <a href="<?php echo base_url('admin/transaksi/set_lunas/').$data->id_trans ?>" class="btn btn-success" onclick="return confirm('Apakah dana sudah diverifikasi masuk? Klik OK untuk Set Lunas.')">
                      <i class="fa fa-check"></i> Validasi & Set Lunas
                    </a>
                  <?php } ?>
                </div>
              </div>
            </div>
          </div>
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