<?php $this->load->view('front/header'); ?>
<?php $this->load->view('front/navbar'); ?>

<div class="container">
    <ol class="breadcrumb">
        <li><a href="<?php echo base_url() ?>">Home</a></li>
        <li><a href="#">Transaksi</a></li>
        <li class="active">Konfirmasi Pembayaran</li>
    </ol>

    <div class="row">
        <div class="col-md-12">
            <h1>KONFIRMASI PEMBAYARAN</h1>
            <hr>
            
            <?php echo validation_errors('<div class="alert alert-danger">', '</div>'); ?>
            <?php if($this->session->flashdata('message')){echo $this->session->flashdata('message');} ?>
            
            <!-- Wajib menggunakan form_open_multipart agar bisa upload gambar -->
            <?php echo form_open_multipart('send', array('id'=>'contactForm')) ?>
                
                <div class="row">
                    <div class="col-md-6">
                        <div class="form-group">
                            <label>No. Invoice <span class="text-danger">*</span></label>
                            <div class="controls">
                                <input type="text" name="id_invoice" class="form-control" placeholder="Contoh: J-260617-0001" required>
                            </div>
                        </div>
                    </div>
                    
                    <div class="col-md-6">
                        <div class="form-group">
                            <label>Nama Pengirim (Pemilik Rekening) <span class="text-danger">*</span></label>
                            <div class="controls">
                                <input type="text" name="nama_pengirim" class="form-control" placeholder="Nama pada buku tabungan pengirim" required>
                            </div>
                        </div>
                    </div>
                </div>

                <div class="row">
                    <div class="col-md-6">
                        <div class="form-group">
                            <label>Bank Pengirim <span class="text-danger">*</span></label>
                            <div class="controls">
                                <input type="text" name="bank_pengirim" class="form-control" placeholder="Contoh: BCA / Mandiri / BNI / Dana" required>
                            </div>
                        </div>
                    </div>

                    <div class="col-md-6">
                        <div class="form-group">
                            <label>Nominal Transfer <span class="text-danger">*</span></label>
                            <div class="controls">
                                <input type="number" name="nominal" class="form-control" placeholder="Contoh: 150000 (Tanpa titik/koma)" required>
                            </div>
                        </div>
                    </div>
                </div>

                <div class="form-group">
                    <label>Upload Bukti Transfer <span class="text-danger">*</span></label>
                    <div class="controls">
                        <!-- Input file khusus untuk gambar -->
                        <input type="file" name="bukti_transfer" class="form-control" accept="image/png, image/jpeg, image/jpg" required>
                        <small class="text-muted">Format yang diizinkan: JPG, JPEG, PNG. Maksimal ukuran file 2MB.</small>
                    </div>
                </div>

                <div class="form-group">
                    <label>Catatan Tambahan (Opsional)</label>
                    <div class="controls">
                        <textarea name="catatan" class="form-control" rows="3" placeholder="Tambahkan catatan jika diperlukan..."></textarea>
                    </div>
                </div>
                
                <hr>
                <button type="submit" id="submit" class="btn btn-success" style="pointer-events: all; cursor: pointer;">
                    <i class="fa fa-send"></i> Kirim Konfirmasi
                </button>
                
            <?php echo form_close() ?>
        </div>
        <?php /* $this->load->view('front/sidebar'); */ ?>
    </div>
</div>

<?php $this->load->view('front/footer'); ?>