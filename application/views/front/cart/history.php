<?php $this->load->view('front/header'); ?>
<?php $this->load->view('front/navbar'); ?>

<div class="container">
	<div class="row">
    <div class="col-lg-12">
			<nav aria-label="breadcrumb">
			  <ol class="breadcrumb">
			    <li class="breadcrumb-item"><a href="<?php echo base_url() ?>"><i class="fa fa-home"></i> Home</a></li>
					<li class="breadcrumb-item active">Riwayat Transaksi</li>
			  </ol>
			</nav>
    </div>

    <div class="col-lg-12"><h1>RIWAYAT TRANSAKSI</h1><hr>
			<div class="row">
			  <div class="col-lg-12">
					<?php echo $this->session->userdata('message') <> '' ? $this->session->userdata('message') : ''; ?>
          <div class="box-body table-responsive padding">
						<?php if(empty($cek_cart_history->id_trans)){echo "Anda belum ada transaksi";}else{ ?>
            	<table id="datatable" class="table table-striped table-bordered">
	              <thead>
	                <tr>
	                  <th style="text-align: center">No.</th>
	                  <th style="text-align: center">Invoice</th>
										<th style="text-align: center">Dibuat</th>
										<th style="text-align: center">Grand Total</th>
										<th style="text-align: center">Status</th>
	                  <th style="text-align: center">Aksi</th>
	                </tr>
	              </thead>
	              <tbody>
	              <?php $no=1; foreach ($cart_history as $history){ ?>
	                <tr>
	                  <td style="text-align:center"><?php echo $no++ ?></td>
	                  <td style="text-align:center"><?php echo $history->id_invoice ?></a></td>
										<td style="text-align:center"><?php echo tgl_indo($history->created_date) ?></td>
										<td style="text-align:center"><?php echo number_format($history->grand_total) ?></a></td>
										<td style="text-align:center">
			                <?php if($history->status == '1'){ ?>
			                  <span class="label label-warning">BELUM LUNAS</span>
			                <?php } elseif($history->status == '2'){ ?>
			                  <span class="label label-primary">LUNAS DEPOSIT</span>
			                <?php } elseif($history->status == '3'){ ?>
			                  <span class="label label-default">EXPIRED</span>
			                <?php } elseif($history->status == '4'){ ?>
			                  <span class="label label-success">LUNAS PEMBAYARAN</span>
			                <?php } elseif($history->status == '5'){ ?>
			                  <span class="label label-danger">REFUND</span>
			                <?php } elseif($history->status == '6'){ ?>
			                  <span class="label label-default">DIBATALKAN</span>
			                <?php } ?>
										</td>
										<td style="text-align:center">
	                    <a href="<?php echo base_url('cart/history_detail/').$history->id_trans ?>">
	                      <button name="update" class="btn btn-warning"><i class="glyphicon glyphicon-zoom-in"></i> Detail</button>
	                    </a>
	                  </td>
	                </tr>
	              <?php } ?>
	              </tbody>
	            </table>
						<?php } ?>
  			  </div>
  			</div>
			</div>
	  </div>
	</div>
</div>

<!-- DATA TABLES-->
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

<?php $this->load->view('front/footer'); ?>
