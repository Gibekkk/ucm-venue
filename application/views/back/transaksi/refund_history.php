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
          <li class="active">Riwayat Refund</li>
        </ol>
      </section>
      <section class="content">
        <div class="row">
          <div class="col-lg-12">
            <div class="box box-primary">
              <div class="box-header with-border">
                <h3 class="box-title"><i class="fa fa-undo"></i> Riwayat Refund</h3>
              </div>
              <div class="box-body">
                <?php if ($this->session->flashdata('message')) { echo $this->session->flashdata('message'); } ?>

                <?php if (empty($get_all)) { ?>
                  <div class="alert alert-info"><i class="fa fa-info-circle"></i> Belum ada transaksi yang di-refund.</div>
                <?php } else { ?>
                <div class="table-responsive no-padding">
                  <table id="datatable" class="table table-striped">
                    <thead>
                      <tr>
                        <th style="text-align: center">No.</th>
                        <th style="text-align: center">Invoice</th>
                        <th style="text-align: center">Atas Nama</th>
                        <th style="text-align: center">Nama Acara</th>
                        <th style="text-align: center">Grand Total</th>
                        <th style="text-align: center">Nominal Refund</th>
                        <th style="text-align: center">Catatan Refund</th>
                        <th style="text-align: center">Tanggal Refund</th>
                        <th style="text-align: center">Diproses Oleh</th>
                        <th style="text-align: center">Aksi</th>
                      </tr>
                    </thead>
                    <tbody>
                    <?php $no=1; foreach ($get_all as $data){ ?>
                      <tr>
                        <td style="text-align:center; vertical-align:middle;"><?php echo $no++ ?></td>
                        <td style="text-align:center; vertical-align:middle;"><b><?php echo $data->id_invoice ?></b></td>
                        <td style="text-align:center; vertical-align:middle;"><?php echo $data->guest_name ?></td>
                        <td style="text-align:center; vertical-align:middle;"><?php echo !empty($data->nama_acara) ? $data->nama_acara : '-' ?></td>
                        <td style="text-align:center; vertical-align:middle;">Rp <?php echo number_format($data->grand_total, 0, ',', '.') ?></td>
                        <td style="text-align:center; vertical-align:middle;">
                          <b class="text-danger">Rp <?php echo isset($data->refund_amount) ? number_format($data->refund_amount, 0, ',', '.') : '-' ?></b>
                        </td>
                        <td style="text-align:left; vertical-align:middle;"><?php echo !empty($data->refund_note) ? nl2br(htmlspecialchars($data->refund_note)) : '-' ?></td>
                        <td style="text-align:center; vertical-align:middle;"><?php echo !empty($data->refund_date) ? date('d-m-Y H:i', strtotime($data->refund_date)) : '-' ?></td>
                        <td style="text-align:center; vertical-align:middle;"><?php echo !empty($data->refund_by) ? htmlspecialchars($data->refund_by) : '-' ?></td>
                        <td style="text-align:center; vertical-align:middle;">
                          <a href="<?php echo base_url('admin/transaksi/detail/').$data->id_trans ?>">
                            <button class="btn btn-sm btn-primary"><i class="fa fa-search-plus"></i> Detail</button>
                          </a>
                        </td>
                      </tr>
                    <?php } ?>
                    </tbody>
                  </table>
                </div>
                <?php } ?>
              </div>
            </div>
          </div>
        </div>
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
