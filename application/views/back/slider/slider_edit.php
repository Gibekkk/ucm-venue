<?php $this->load->view('back/meta') ?>
  <div class="wrapper">
    <?php $this->load->view('back/navbar') ?>
    <?php $this->load->view('back/sidebar') ?>
    <!-- Content Wrapper. Contains page content -->
    <div class="content-wrapper">
      <!-- Content Header (Page header) -->
      <section class="content-header">
        <h1><?php echo $title ?></h1>
        <ol class="breadcrumb">
          <li><a href="#"><i class="fa fa-dashboard"></i> Home</a></li>
          <li><a href="#"><?php echo $module ?></a></li>
					<li class="active"><?php echo $title ?></li>
        </ol>
      </section>
      <!-- Main content -->
      <section class="content">
        <!-- Small boxes (Stat box) -->
        <div class="row">
          <div class="col-lg-12">
						<div class="box box-primary">
              <div class="box-body">
								<?php echo validation_errors() ?>
								<?php if($this->session->flashdata('message')){echo $this->session->flashdata('message');} ?>
								<?php echo form_open_multipart($action);?>
									<div class="form-group"><label>No. Urut</label>
										<?php echo form_input($no_urut, $slider->no_urut);?>
									</div>
									<div class="form-group"><label>Nama</label>
										<?php echo form_input($nama_slider, $slider->nama_slider);?>
									</div>
									<div class="form-group"><label>Link</label>
										<?php echo form_input($link, $slider->link);?>
									</div>
								<div class="form-group"><label>Media Sebelumnya</label><br>
									<?php 
									$video_types = array('.mp4', '.webm');
									if (in_array(strtolower($slider->foto_type), $video_types)) {
										echo '<video src="'.base_url('assets/images/slider/'.$slider->foto.$slider->foto_type).'" width="300px" controls></video>';
									} else {
										echo '<img src="'.base_url('assets/images/slider/'.$slider->foto.$slider->foto_type).'" width="200px"/>';
									}
									?>
								</div>
								<div class="form-group"><label>Media Baru</label>
									<input type="file" class="form-control" name="foto" id="foto" onchange="tampilkanPreview(this,'preview')" accept="image/*,video/*"/>
									<br><p><b>Preview</b><br>
									<img id="preview" src="" alt="" width="350px" style="display:none;"/>
									<video id="preview-video" width="350px" controls style="display:none;"></video>
									</div>
									<?php echo form_input($id_slider,$slider->id_slider);?>
									<button type="submit" name="submit" class="btn btn-success"><?php echo $button_submit ?></button>
									<button type="reset" name="reset" class="btn btn-danger"><?php echo $button_reset ?></button>
								<?php echo form_close(); ?>
							</div>
						</div>
          </div><!-- ./col -->
        </div><!-- /.row -->
      </section><!-- /.content -->
    </div><!-- /.content-wrapper -->
    <?php $this->load->view('back/footer') ?>
  </div><!-- ./wrapper -->
  <?php $this->load->view('back/js') ?>
	<script type="text/javascript">
	function tampilkanPreview(foto,idpreview)
	{ //membuat objek gambar atau video
		var gb = foto.files;
		var imgPreview = document.getElementById('preview');
		var videoPreview = document.getElementById('preview-video');
		
		//loop untuk merender gambar/video
		for (var i = 0; i < gb.length; i++)
		{ //bikin variabel
			var gbPreview = gb[i];
			var imageType = /image.*/;
			var videoType = /video.*/;
			var reader = new FileReader();
			
			if (gbPreview.type.match(imageType))
			{ //jika tipe image
				imgPreview.file = gbPreview;
				reader.onload = function(e) {
					imgPreview.src = e.target.result;
					imgPreview.style.display = 'block';
					videoPreview.style.display = 'none';
				};
				reader.readAsDataURL(gbPreview);
			}
			else if (gbPreview.type.match(videoType))
			{ //jika tipe video
				videoPreview.file = gbPreview;
				reader.onload = function(e) {
					videoPreview.src = e.target.result;
					videoPreview.style.display = 'block';
					imgPreview.style.display = 'none';
				};
				reader.readAsDataURL(gbPreview);
			}
			else
			{ //jika tipe data tidak sesuai
				alert("Tipe file tidak sesuai. File harus bertipe gambar (.png, .gif, .jpg, .webp) atau video (.mp4, .webm).");
			}
		}
	}
</script>
</body>
</html>
