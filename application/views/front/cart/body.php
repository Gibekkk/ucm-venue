<?php $this->load->view('front/header'); ?>
<?php $this->load->view('front/navbar'); ?>

<div class="container">
    <div class="row">
        <div class="col-lg-12">
            <nav aria-label="breadcrumb">
                <ol class="breadcrumb">
                    <li class="breadcrumb-item"><a href="<?php echo base_url() ?>"><i class="fa fa-home"></i> <?php echo $this->lang->line('nav_home'); ?></a></li>
                    <li class="breadcrumb-item active"><?php echo $this->lang->line('cart_title'); ?></li>
                </ol>
            </nav>
        </div>

        <div class="col-lg-12">
            <h1><?php echo strtoupper($this->lang->line('cart_title')); ?></h1>
            <hr>
            <?php if (!empty($min_booking_date)) { ?>
                <div class="alert alert-info"><i class="fa fa-info-circle"></i> Booking minimal H-4 (tanggal acara paling cepat <b><?php echo date('d-m-Y', strtotime($min_booking_date)); ?></b>).</div>
            <?php } ?>
            <?php echo form_open_multipart('cart/checkout') ?>
                <div class="row">
                    <div class="col-lg-12">
                        <?php if ($this->session->flashdata('message')) {
                            echo $this->session->flashdata('message');
                        } ?>
                        <h4><i class="fa fa-building"></i> Booking Venue</h4>
                        <div class="box-body table-responsive padding">
                            <table id="datatable" class="table table-striped table-bordered">
                                <thead>
                                    <tr>
                                        <th style="text-align: center"><?php echo $this->lang->line('cart_field'); ?></th>
                                        <th style="text-align: center"><?php echo $this->lang->line('cart_price'); ?></th>
                                        <th style="text-align: center"><?php echo $this->lang->line('cart_date'); ?></th>
                                        <th style="text-align: center"><?php echo $this->lang->line('cart_start_time'); ?></th>
                                        <th style="text-align: center"><?php echo $this->lang->line('cart_duration'); ?></th>
                                        <th style="text-align: center"><?php echo $this->lang->line('cart_end_time'); ?></th>
                                        <th style="text-align: center"><?php echo $this->lang->line('cart_total'); ?></th>
                                        <th style="text-align: center"><?php echo $this->lang->line('cart_action'); ?></th>
                                    </tr>
                                </thead>
                                <tbody>
                                    <?php
                                    // PENTING: jangan pakai nama $ada_venue di sini, itu variabel yang dikirim
                                    // dari controller (Cart::index()) untuk mengontrol tombol "Tambah Addon" di
                                    // bawah. Kalau ditimpa di sini, nilainya bisa salah kalau loop ini berubah nanti.
                                    $ada_baris_venue = false;
                                    foreach ($cart_data as $cart) {
                                        if ($cart->is_addon == 1) continue;
                                        $ada_baris_venue = true; ?>
                                        <tr>
                                            <td style="text-align:left"><?php echo $cart->nama_lapangan ?></td>
                                            <td style="text-align:center" class="harga_per_jam"><?php echo number_format($cart->harga) ?></td>
                                            <td style="text-align:center">
                                                <?php echo form_input($tanggal) ?>
                                                <input type="hidden" name="harga_jual[]" value="<?php echo $cart->harga ?>">
                                                <input type="hidden" name="lapangan[]" value="<?php echo $cart->lapangan_id ?>">
                                                <input type="hidden" name="id_transdet[]" value="<?php echo $cart->id_transdet ?>">
                                                <input type="hidden" name="is_addon[]" value="0">
                                                <input type="hidden" value="<?php echo $cart->lapangan_id; ?>" class="lapangan_id">
                                            </td>
                                            <td style="text-align:center">
                                                <?php echo form_dropdown('', array('' => '- Pilih Tanggal Dulu -'), '', $jam_mulai); ?>
                                                <span class="loading_container" style="display:none;">
                                                    <img src="<?php echo base_url(); ?>assets/template/frontend/img/loading.gif" style="display:inline;" />&nbsp;memuat data ...</span>
                                            </td>
                                            <td style="text-align:center">
                                                <input type="number" name="durasi[]" class="durasi" min="1">
                                            </td>
                                            <td style="text-align:center" class="jam_selesai"></td>
                                            <td style="text-align:center" class="subtotal"></td>
                                            <td style="text-align:center">
                                                <a href="<?php echo base_url('cart/delete/') . $cart->id_transdet ?>" class="btn btn-sm btn-danger"><i class="fa fa-remove"></i></a>
                                            </td>
                                        </tr>
                                    <?php } if (!$ada_baris_venue) { ?>
                                        <tr><td colspan="8" class="text-center text-muted">Belum ada venue di keranjang.</td></tr>
                                    <?php } ?>
                                </tbody>
                            </table>
                        </div>
                    </div>
                </div>

                <div class="row">
                    <div class="col-lg-12">
                        <h4><i class="fa fa-cubes"></i> Addon</h4>

                        <?php if (!empty($addons) && $ada_venue) { ?>
                            <div style="margin-bottom: 10px;">
                                <button type="button" class="btn btn-primary btn-sm" data-toggle="modal" data-target="#addonModal">
                                    <i class="fa fa-plus-circle"></i> Tambah Addon (Opsional)
                                </button>
                            </div>
                        <?php } elseif (!empty($addons) && !$ada_venue) { ?>
                            <div class="alert alert-warning" style="padding:8px 12px; margin-bottom:10px;">
                                <i class="fa fa-info-circle"></i> Tambahkan Venue terlebih dahulu sebelum bisa menambahkan Addon.
                            </div>
                        <?php } ?>

                        <div class="box-body table-responsive padding">
                            <table class="table table-striped table-bordered">
                                <thead>
                                    <tr>
                                        <th style="text-align: center">Addon</th>
                                        <th style="text-align: center">Harga Satuan</th>
                                        <th style="text-align: center">Tanggal</th>
                                        <th style="text-align: center">Jumlah</th>
                                        <th style="text-align: center">Total</th>
                                        <th style="text-align: center"><?php echo $this->lang->line('cart_action'); ?></th>
                                    </tr>
                                </thead>
                                <tbody>
                                    <?php $ada_addon = false;
                                    foreach ($cart_data as $cart) {
                                        if ($cart->is_addon != 1) continue;
                                        $ada_addon = true; ?>
                                        <tr>
                                            <td style="text-align:left"><?php echo $cart->nama_lapangan ?></td>
                                            <td style="text-align:center" class="harga_per_jam"><?php echo number_format($cart->harga) ?></td>
                                            <td style="text-align:center">
                                                <input type="text" name="tanggal[]" class="form-control tanggal_addon" autocomplete="off" required>
                                                <input type="hidden" name="harga_jual[]" value="<?php echo $cart->harga ?>">
                                                <input type="hidden" name="lapangan[]" value="<?php echo $cart->lapangan_id ?>">
                                                <input type="hidden" name="id_transdet[]" value="<?php echo $cart->id_transdet ?>">
                                                <input type="hidden" name="is_addon[]" value="1">
                                                <!-- Addon tidak pakai jam, jam_mulai dikunci 00:00:00 -->
                                                <input type="hidden" name="jam_mulai[]" value="00:00:00">
                                            </td>
                                            <td style="text-align:center">
                                                <input type="number" name="durasi[]" class="jumlah" min="1" value="1">
                                            </td>
                                            <td style="text-align:center" class="subtotal"></td>
                                            <td style="text-align:center">
                                                <a href="<?php echo base_url('cart/delete/') . $cart->id_transdet ?>" class="btn btn-sm btn-danger"><i class="fa fa-remove"></i></a>
                                            </td>
                                        </tr>
                                    <?php } if (!$ada_addon) { ?>
                                        <tr><td colspan="6" class="text-center text-muted">Belum ada addon di keranjang.</td></tr>
                                    <?php } ?>
                                </tbody>
                            </table>
                        </div>
                    </div>
                </div>

                <table class="table table-striped table-bordered">
                    <tbody>
                        <tr>
                            <th><?php echo $this->lang->line('cart_subtotal'); ?></th>
                            <td align="center">Rp</td>
                            <td align="right" id="subtotal_bawah"></td>
                        </tr>
                        <tr>
                            <th scope="row"><?php echo $this->lang->line('cart_grand_total'); ?></th>
                            <td align="center">Rp</td>
                            <td align="right"><b>
                                    <div id="grandtotal"></div>
                                </b></td>
                        </tr>
                    </tbody>
                </table>
                <p class="text-muted"><small><i class="fa fa-info-circle"></i> Harga sudah termasuk PPN.</small></p>

                <?php if ($cek_keranjang != NULL) { ?>
                    <div class="col-lg-12">
                        <div class="row">
                            <div class="form-group"><label><?php echo $this->lang->line('cart_event_name'); ?></label>
                                <input type="text" name="nama_acara" id="nama_acara" class="form-control" required placeholder="<?php echo $this->lang->line('cart_event_name'); ?>">
                            </div>
                        </div>
                        <div class="row">
                            <div class="form-group"><label><?php echo $this->lang->line('cart_notes'); ?></label>
                                <input type="text" name="catatan" class="form-control">
                            </div>
                        </div>

                        <div class="row">
                            <div class="col-lg-12">
                                <h3><?php echo $this->lang->line('guest_info_title'); ?></h3>
                                <hr>
                            </div>
                            <div class="col-sm-6">
                                <div class="form-group">
                                    <label><?php echo $this->lang->line('guest_full_name'); ?> <span class="text-danger">*</span></label>
                                    <input type="text" name="guest_name" id="guest_name" class="form-control" required placeholder="<?php echo $this->lang->line('guest_full_name'); ?>">
                                </div>
                            </div>
                            <div class="col-sm-6">
                                <div class="form-group">
                                    <label><?php echo $this->lang->line('guest_email'); ?> <span class="text-danger">*</span></label>
                                    <input type="email" name="guest_email" id="guest_email" class="form-control" required placeholder="example@email.com">
                                </div>
                            </div>
                            <div class="col-sm-6">
                                <div class="form-group">
                                    <label><?php echo $this->lang->line('guest_phone'); ?> <span class="text-danger">*</span></label>
                                    <input type="text" name="guest_phone" id="guest_phone" class="form-control" required placeholder="08xxxxxxxxxx">
                                </div>
                            </div>
                            <div class="col-sm-6">
                                <div class="form-group">
                                    <label><?php echo $this->lang->line('guest_province'); ?> <span class="text-danger">*</span></label>
                                    <?php echo form_dropdown('guest_province_id', $ambil_provinsi, '', array('class' => 'form-control', 'id' => 'guest_province_id', 'required' => 'required', 'onchange' => 'tampilKotaGuest()')); ?>
                                </div>
                            </div>
                            <div class="col-sm-6">
                                <div class="form-group">
                                    <label><?php echo $this->lang->line('guest_city'); ?> <span class="text-danger">*</span></label>
                                    <?php echo form_dropdown('guest_city_id', array('' => $this->lang->line('guest_select_city')), '', array('class' => 'form-control', 'id' => 'guest_city_id', 'required' => 'required')); ?>
                                </div>
                            </div>
                            <div class="col-sm-12">
                                <div class="form-group">
                                    <label><?php echo $this->lang->line('guest_address'); ?> <span class="text-danger">*</span></label>
                                    <textarea name="guest_address" id="guest_address" class="form-control" rows="3" required placeholder="<?php echo $this->lang->line('guest_address'); ?>"></textarea>
                                </div>
                            </div>
                        </div>

                        <div class="row">
                            <div class="col-lg-12">
                                <h3><?php echo $this->lang->line('npwp_title'); ?></h3>
                                <hr>
                            </div>
                            <div class="col-sm-6">
                                <div class="form-group">
                                    <label><?php echo $this->lang->line('npwp_number'); ?> <span class="text-danger">*</span></label>
                                    <input type="text" name="nomor_npwp" id="nomor_npwp" class="form-control" required placeholder="00.000.000.0-000.000">
                                </div>
                            </div>
                            <div class="col-sm-6">
                                <div class="form-group">
                                    <label><?php echo $this->lang->line('npwp_upload'); ?> <span class="text-danger">*</span></label>
                                    <input type="file" name="npwp_file" id="npwp_file" class="form-control" accept="image/png, image/jpeg, image/jpg, application/pdf" required>
                                    <small class="text-muted"><?php echo $this->lang->line('npwp_hint'); ?></small>
                                </div>
                            </div>
                        </div>

                    </div>
                <?php } ?>

                <div class="row">
                    <div class="col-lg-12">
                        <?php if (!empty($cek_keranjang->id_trans) && !empty($cek_keranjang->lapangan_id)) { ?>
                            <a href="<?php echo base_url('cart/empty_cart/') . $cek_keranjang->id_trans ?>">
                                <button name="hapus" type="button" class="btn btn-danger" aria-label="Left Align" title="Kosongkan Keranjang" OnClick="return confirm('Apakah Anda yakin?');">
                                    <span class="glyphicon glyphicon-trash" aria-hidden="true"></span> Kosongkan
                                </button>
                            </a>
                        <?php } ?>
                        <a href="<?php echo base_url() ?>">
                            <button name="hapus" type="button" class="btn btn-primary" aria-label="Left Align" title="Lanjut Belanja">
                                <span class="glyphicon glyphicon-shopping-cart" aria-hidden="true"></span> Lanjut Belanja
                            </button>
                        </a>
                        <?php if (!empty($cek_keranjang->id_trans)) { ?>
                            <button name="checkout" type="button" id="btnCheckout" class="btn btn-success" aria-label="Left Align" title="Checkout">
                                <span class="glyphicon glyphicon-shopping-cart" aria-hidden="true"></span> Checkout
                            </button>
                        <?php } ?>
                    </div>
                </div>
                <?php if (!empty($cek_keranjang->id_trans)) { ?>
                    <input type="hidden" name="id_trans" value="<?php echo $cek_keranjang->id_trans ?>">
                <?php } ?>
                <?php echo form_close() ?>
        </div>

        <div class="modal fade" id="addonModal" tabindex="-1" role="dialog" aria-labelledby="addonModalLabel">
            <div class="modal-dialog" role="document">
                <div class="modal-content">
                    <div class="modal-header" style="background-color: #337ab7; color: white;">
                        <button type="button" class="close" data-dismiss="modal" aria-label="Close" style="color: white; opacity: 1;"><span aria-hidden="true">&times;</span></button>
                        <h4 class="modal-title" id="addonModalLabel"><i class="fa fa-cubes"></i> Daftar Addon Tersedia</h4>
                    </div>
                    <div class="modal-body">
                        <div class="form-group">
                            <div class="input-group">
                                <span class="input-group-addon"><i class="fa fa-search"></i></span>
                                <input type="text" id="searchAddonInput" class="form-control" placeholder="Cari addon (misal: Bola, Sepatu, Wasit)...">
                            </div>
                        </div>

                        <div style="max-height: 350px; overflow-y: auto;">
                            <ul class="list-group" id="addonList">
                                <?php if (!empty($addons)) {
                                    foreach ($addons as $addon) { ?>
                                    <li class="list-group-item addon-item" style="display: flex; justify-content: space-between; align-items: center;">
                                        <div>
                                            <h5 class="addon-name" style="margin: 0 0 5px 0; font-weight: bold; color: #34495e;"><?php echo $addon->nama_lapangan; ?></h5>
                                            <span style="color: #e74c3c; font-weight: 600;">Rp <?php echo number_format($addon->harga); ?></span>
                                        </div>
                                        <a href="<?php echo base_url('cart/buy/'.$addon->id_lapangan); ?>" class="btn btn-sm btn-success"><i class="fa fa-plus"></i> Tambah</a>
                                    </li>
                                <?php } } else { ?>
                                    <li class="list-group-item text-center text-muted">Belum ada addon yang tersedia saat ini.</li>
                                <?php } ?>
                            </ul>
                        </div>
                    </div>
                    <div class="modal-footer">
                        <button type="button" class="btn btn-default" data-dismiss="modal">Tutup</button>
                    </div>
                </div>
            </div>
        </div>

        <div class="modal fade" id="validationModal" tabindex="-1" role="dialog" aria-labelledby="validationModalLabel">
            <div class="modal-dialog" role="document">
                <div class="modal-content">
                    <div class="modal-header" style="background-color: #d9534f; color: white;">
                        <button type="button" class="close" data-dismiss="modal" aria-label="Close"><span aria-hidden="true">&times;</span></button>
                        <h4 class="modal-title" id="validationModalLabel"><i class="fa fa-exclamation-triangle"></i> <?php echo $this->lang->line('btn_warning'); ?></h4>
                    </div>
                    <div class="modal-body">
                        <p id="validationMessage"></p>
                    </div>
                    <div class="modal-footer">
                        <button type="button" class="btn btn-danger" data-dismiss="modal"><?php echo $this->lang->line('close'); ?></button>
                    </div>
                </div>
            </div>
        </div>

        <div class="modal fade" id="confirmCheckoutModal" tabindex="-1" role="dialog" aria-labelledby="confirmCheckoutModalLabel">
            <div class="modal-dialog" role="document">
                <div class="modal-content">
                    <div class="modal-header" style="background-color: #5cb85c; color: white;">
                        <button type="button" class="close" data-dismiss="modal" aria-label="Close"><span aria-hidden="true">&times;</span></button>
                        <h4 class="modal-title" id="confirmCheckoutModalLabel"><i class="fa fa-check-circle"></i> <?php echo $this->lang->line('btn_confirm'); ?> <?php echo $this->lang->line('cart_checkout'); ?></h4>
                    </div>
                    <div class="modal-body">
                        <p><?php echo $this->lang->line('validation_confirm_checkout'); ?></p>
                        <p class="text-muted"><small><?php echo $this->lang->line('validation_complete_booking'); ?></small></p>
                    </div>
                    <div class="modal-footer">
                        <button type="button" class="btn btn-default" data-dismiss="modal"><?php echo $this->lang->line('cancel'); ?></button>
                        <button type="button" class="btn btn-success" id="confirmCheckoutBtn"><i class="fa fa-shopping-cart"></i> <?php echo $this->lang->line('cart_checkout'); ?></button>
                    </div>
                </div>
            </div>
        </div>

        <link href="<?php echo base_url('assets/plugins/') ?>datepicker/css/bootstrap-datepicker.css" rel="stylesheet">
        <script src="<?php echo base_url('assets/plugins/') ?>datepicker/js/bootstrap-datepicker.js"></script>

        <script type="text/javascript">
            var MIN_BOOKING_DATE = "<?php echo !empty($min_booking_date) ? $min_booking_date : '0'; ?>";

            // Script Live Search Addon
            $(document).ready(function() {
                $("#searchAddonInput").on("keyup", function() {
                    var value = $(this).val().toLowerCase();
                    $("#addonList .addon-item").filter(function() {
                        $(this).toggle($(this).find(".addon-name").text().toLowerCase().indexOf(value) > -1)
                    });
                });

                // NPWP sekarang wajib diisi untuk semua booking (tidak lagi opsional/pakai checkbox instansi).
            });

            // Format Uang
            const numberWithCommas = (x) => {
                var parts = x.toString().split(".");
                parts[0] = parts[0].replace(/\B(?=(\d{3})+(?!\d))/g, ",");
                return parts.join(".");
            }

            function hitungGrandTotal() {
                subtotal_bawah = 0;
                $('.subtotal').each(function(i, obj) {
                    a_subtotal_html = $(this).html().trim().replace(/,/g, '');
                    if (a_subtotal_html == "") {
                        a_subtotal_html = "0";
                    }
                    subtotal_bawah += parseInt(a_subtotal_html);
                });

                $("#subtotal_bawah").html(numberWithCommas(subtotal_bawah));
                $("#grandtotal").html(numberWithCommas(subtotal_bawah));
            }

            // Datepicker (venue) & Kalkulasi Cart. Booking minimal H-4: tanggal sebelum
            // MIN_BOOKING_DATE tidak bisa dipilih (sisanya disabled).
            $(function() {
                $(document).on("focus", ".tanggal", function() {
                    var $el = $(this);

                    // Ambil daftar tanggal yang venue ini sudah penuh booking aktif, lalu
                    // disable tanggal2 tsb di datepicker. Cuma di-fetch sekali per elemen.
                    if ($el.data('dp_initialized')) {
                        return;
                    }
                    $el.data('dp_initialized', true);

                    var lapangan_id_el = $el.parent().parent().find(".lapangan_id");

                    $.post('<?php echo base_url(); ?>Cart/getBookedDates', {
                        lapangan_id: lapangan_id_el.val()
                    }, function(disabledDates) {
                        $el.datepicker({
                            startDate: MIN_BOOKING_DATE,
                            autoclose: true,
                            todayHighlight: true,
                            format: 'yyyy-mm-dd',
                            datesDisabled: disabledDates || []
                        });
                        $el.datepicker('show');
                    }, 'json').fail(function() {
                        // kalau AJAX gagal, tetap bisa pilih tanggal (aturan H-4 tetap berlaku)
                        $el.datepicker({
                            startDate: MIN_BOOKING_DATE,
                            autoclose: true,
                            todayHighlight: true,
                            format: 'yyyy-mm-dd'
                        });
                        $el.datepicker('show');
                    });
                });

                // Datepicker addon: sama-sama minimal H-4, tapi tidak memicu lookup jam
                $(document).on("focus", ".tanggal_addon", function() {
                    $(this).datepicker({
                        startDate: MIN_BOOKING_DATE,
                        autoclose: true,
                        todayHighlight: true,
                        format: 'yyyy-mm-dd'
                    });
                });

                $('.tanggal').on('changeDate', function(ev) {
                    tanggal_el = $(this);
                    tanggal_val = $(this).val();
                    jam_mulai_el = tanggal_el.parent().parent().find(".jam_mulai");
                    durasi_el = tanggal_el.parent().parent().find(".durasi");
                    jam_selesai_el = durasi_el.parent().parent().find(".jam_selesai");
                    loading_container_el = tanggal_el.parent().parent().find(".loading_container");
                    lapangan_id_el = tanggal_el.parent().parent().find(".lapangan_id");

                    jam_mulai_el.hide();
                    loading_container_el.show();

                    $.post('<?php echo base_url(); ?>Cart/getJamMulai', {
                            tanggal: tanggal_val,
                            lapangan_id: lapangan_id_el.val()
                        }, function(data) {
                            jam_mulai_el.show();
                            loading_container_el.hide();
                            jam_mulai_el.html("");

                            jam_mulai_el.append("<option value='' selected='selected'>- Pilih Jam Mulai -</option>");

                            count = 0;

                            data.forEach(function(item, index) {
                                jam_mulai_el.append("<option durasi='" + item.durasi + "'>" + item.jam_mulai + "</option>");
                                count++;
                            });

                            durasi_el.val(0);
                            jam_selesai_el.html("");

                            if (count == 0) {
                                jam_mulai_el.html("");
                                jam_mulai_el.append("<option value='' selected='selected'>- Tidak ada pilihan -</option>");
                            }

                        },
                        'json'
                    );
                });

                $(document).on("change", ".jam_mulai", function() {
                    jam_mulai_el = $(this);
                    durasi_el = jam_mulai_el.parent().parent().find(".durasi");
                    durasi_el.val(jam_mulai_el.find(":selected").attr("durasi")).change();
                });

                $(document).on("change keyup", ".durasi", function() {
                    durasi_el = $(this);
                    durasi = $(this).val();

                    if (durasi == "") {
                        durasi = 0;
                        durasi_el.val(durasi);
                    }

                    jam_mulai_el = durasi_el.parent().parent().find(".jam_mulai");
                    jam_selesai_el = durasi_el.parent().parent().find(".jam_selesai");

                    harga_per_jam_el = durasi_el.parent().parent().find(".harga_per_jam");
                    subtotal_el = durasi_el.parent().parent().find(".subtotal");

                    if (jam_mulai_el.val() != "") {
                        jam_selesai = moment("01-01-2018 " + jam_mulai_el.val(), "MM-DD-YYYY HH:mm:ss").add(parseInt(durasi), 'hours').format('HH:mm:ss');
                        jam_selesai_el.html(jam_selesai);

                        harga_per_jam = harga_per_jam_el.html().replace(/,/g, '');
                        harga_per_jam_int = parseInt(harga_per_jam);

                        subtotal_el.html(numberWithCommas(harga_per_jam_int * parseInt(durasi)));

                        hitungGrandTotal();
                    }
                });

                // Kalkulasi addon: total = harga satuan x jumlah (tidak butuh jam)
                $(document).on("change keyup", ".jumlah", function() {
                    jumlah_el = $(this);
                    jumlah = parseInt($(this).val());
                    if (isNaN(jumlah) || jumlah < 1) {
                        jumlah = 1;
                        jumlah_el.val(jumlah);
                    }

                    harga_per_jam_el = jumlah_el.parent().parent().find(".harga_per_jam");
                    subtotal_el = jumlah_el.parent().parent().find(".subtotal");

                    harga_satuan = parseInt(harga_per_jam_el.html().replace(/,/g, ''));
                    subtotal_el.html(numberWithCommas(harga_satuan * jumlah));

                    hitungGrandTotal();
                });

                // Hitung ulang total addon yang sudah ada saat halaman dimuat
                $('.jumlah').trigger('keyup');
            });
        </script>

        <script type="text/javascript">
        // Load cities based on selected province for guest
        function tampilKotaGuest()
        {
            provinsi_id = document.getElementById("guest_province_id").value;
            $.ajax({
                url:"<?php echo base_url();?>cart/pilih_kota/"+provinsi_id+"",
                success: function(response){
                    $("#guest_city_id").html(response);
                },
                dataType:"html"
            });
            return false;
        }

        // Show validation error modal
        function showValidationError(message) {
            $('#validationMessage').text(message);
            $('#validationModal').modal('show');
        }

        // Checkout validation and confirmation
        $(document).ready(function() {
            $('#btnCheckout').click(function(e) {
                e.preventDefault();

                // Check if all VENUE booking details are filled (addon tidak butuh jam)
                var allFilled = true;
                $('.tanggal').each(function() {
                    if($(this).val() == '') {
                        allFilled = false;
                    }
                });
                $('.jam_mulai').each(function() {
                    if($(this).val() == '') {
                        allFilled = false;
                    }
                });
                $('.durasi').each(function() {
                    if($(this).val() == '' || $(this).val() == '0') {
                        allFilled = false;
                    }
                });
                $('.tanggal_addon').each(function() {
                    if($(this).val() == '') {
                        allFilled = false;
                    }
                });
                $('.jumlah').each(function() {
                    if($(this).val() == '' || $(this).val() == '0') {
                        allFilled = false;
                    }
                });

                if(!allFilled) {
                    showValidationError('<?php echo $this->lang->line("validation_complete_booking"); ?>');
                    return false;
                }

                if(!$('#nama_acara').length || $('#nama_acara').val() == '') {
                    showValidationError('<?php echo $this->lang->line("cart_event_name"); ?> <?php echo $this->lang->line("validation_required"); ?>');
                    $('#nama_acara').focus();
                    return false;
                }

                // Validate guest information (booking selalu sebagai tamu, tanpa login)
                if($('#guest_name').val() == '') {
                    showValidationError('<?php echo $this->lang->line("guest_full_name"); ?> <?php echo $this->lang->line("validation_required"); ?>');
                    $('#guest_name').focus();
                    return false;
                }
                if($('#guest_email').val() == '') {
                    showValidationError('<?php echo $this->lang->line("guest_email"); ?> <?php echo $this->lang->line("validation_required"); ?>');
                    $('#guest_email').focus();
                    return false;
                }
                if($('#guest_phone').val() == '') {
                    showValidationError('<?php echo $this->lang->line("guest_phone"); ?> <?php echo $this->lang->line("validation_required"); ?>');
                    $('#guest_phone').focus();
                    return false;
                }
                if($('#guest_province_id').val() == '') {
                    showValidationError('<?php echo $this->lang->line("guest_province"); ?> <?php echo $this->lang->line("validation_required"); ?>');
                    $('#guest_province_id').focus();
                    return false;
                }
                if($('#guest_city_id').val() == '') {
                    showValidationError('<?php echo $this->lang->line("guest_city"); ?> <?php echo $this->lang->line("validation_required"); ?>');
                    $('#guest_city_id').focus();
                    return false;
                }
                if($('#guest_address').val() == '') {
                    showValidationError('<?php echo $this->lang->line("guest_address"); ?> <?php echo $this->lang->line("validation_required"); ?>');
                    $('#guest_address').focus();
                    return false;
                }
                if($('#nomor_npwp').val() == '') {
                    showValidationError('<?php echo $this->lang->line("npwp_number"); ?> <?php echo $this->lang->line("validation_required"); ?>');
                    $('#nomor_npwp').focus();
                    return false;
                }
                if($('#npwp_file').val() == '') {
                    showValidationError('<?php echo $this->lang->line("npwp_hint"); ?>');
                    $('#npwp_file').focus();
                    return false;
                }

                // Show confirmation modal
                $('#confirmCheckoutModal').modal('show');
            });

            // Handle confirm checkout button
            $('#confirmCheckoutBtn').click(function() {
                $('#confirmCheckoutModal').modal('hide');
                $('#btnCheckout').closest('form').submit();
            });
        });
        </script>
    </div>
</div>
<?php $this->load->view('front/footer'); ?>
