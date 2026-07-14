<div class="bs-callout bs-callout-primary"><h4><i class="fa fa-phone"></i> Hubungi Kami</h4></div>
<ul class="list-group">
  <div align="center"><img src="<?php echo base_url('assets/images/wa.jpg') ?>" width="100px"></div>
  <?php foreach($kontak_sidebar as $kontak){?>
    <b><?php echo $kontak->nama_kontak ?></b><br>
    +<?php echo $kontak->nohp ?><br>
    <a href="https://api.whatsapp.com/send?phone=+<?php echo $kontak->nohp ?>&text=Hai%20Kak%2C%20saya%20mau%20tanya-tanya%20seputar%20informasi%20booking%20tempat%20di%20UC%20Makassar">
      <button class="btn btn-success" type="submit" name="button">Chat via Whatsapp (klik disini)</button>
    </a><br><br>
  <?php } ?>
</ul>
