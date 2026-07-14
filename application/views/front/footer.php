<footer style="background-color: #ecf0f1; border-top: 1px solid #d1d8dd; padding: 25px 0 15px 0; margin-top: 40px; width: 100%;">
  <div class="container">
    <div class="row">
      
      <!-- KIRI: Social Media -->
      <div class="col-lg-5 col-md-5 col-sm-12 mb-3">
        <div class="bs-callout" style="border-left: 5px solid #2980b9; padding-left: 15px; margin-bottom: 15px;">
          <h4 style="margin: 0; color: #2c3e50; font-weight: bold; font-size: 15px;"><i class="fa fa-share-alt"></i> <?php echo $this->lang->line('footer_social_media'); ?></h4>
        </div>
        
        <!-- Social Media Buttons -->
        <div style="display: flex; flex-wrap: wrap; gap: 8px; margin-bottom: 10px;">
          <a href="https://www.facebook.com/UCMakassarOfficial/" target="_blank" class="btn btn-primary btn-sm" style="flex-grow: 1; padding: 8px; border-radius: 6px; text-align: center;" title="Facebook">
            <i class="fa fa-facebook fa-lg"></i>
          </a>
          <a href="https://www.instagram.com/uc_makassar/" target="_blank" class="btn btn-sm" style="flex-grow: 1; background-color: #e1306c; border-color: #e1306c; color: white; padding: 8px; border-radius: 6px; text-align: center;" title="Instagram">
            <i class="fa fa-instagram fa-lg"></i>
          </a>
          <a href="https://x.com/UCMakassar" target="_blank" class="btn btn-sm" style="flex-grow: 1; background-color: #000; border-color: #000; color: white; padding: 8px; border-radius: 6px; text-align: center;" title="X / Twitter">
            <i class="fa fa-twitter fa-lg"></i>
          </a>
          <a href="https://www.youtube.com/c/UCMakassar" target="_blank" class="btn btn-danger btn-sm" style="flex-grow: 1; padding: 8px; border-radius: 6px; text-align: center;" title="YouTube">
            <i class="fa fa-youtube-play fa-lg"></i>
          </a>
        </div>
        
        <!-- <p style="color: #7f8c8d; font-size: 13px; line-height: 1.4; margin: 0;">
          Ikuti akun sosial media resmi kami untuk mendapatkan informasi terbaru dan update kegiatan seputar UC Makassar.
        </p> -->
      </div>

      <!-- KANAN: Hubungi Kami -->
      <div class="col-lg-7 col-md-7 col-sm-12 mb-3">
        <div class="bs-callout" style="border-left: 5px solid #27ae60; padding-left: 15px; margin-bottom: 15px;">
          <h4 style="margin: 0; color: #2c3e50; font-weight: bold; font-size: 15px;"><i class="fa fa-phone"></i> <?php echo $this->lang->line('footer_contact_us'); ?></h4>
        </div>
        
        <!-- Wrapper Grid 3 Per Row (Langsung tanpa kotak putih) -->
        <div style="display: flex; flex-wrap: wrap; gap: 8px;">
          <?php 
          if (isset($kontak_sidebar) && is_array($kontak_sidebar)) {
            foreach($kontak_sidebar as $kontak){
          ?>
            <!-- Link Chat langsung menggunakan properti Grid agar menyusun 3 per baris -->
            <a href="https://api.whatsapp.com/send?phone=+<?php echo $kontak->nohp ?>&text=Hai%20Kak%2C%20saya%20mau%20tanya-tanya%20seputar%20informasi%20booking%20tempat%20di%20UC%20Makassar" target="_blank" style="flex: 1 1 calc(33.333% - 8px); min-width: 120px; text-decoration: none; display: block;">
              <button class="btn btn-success btn-sm" type="button" style="background-color: #25D366; border-color: #25D366; border-radius: 5px; font-weight: 600; width: 100%; padding: 8px 5px; font-size: 12px; white-space: nowrap; overflow: hidden; text-overflow: ellipsis;" title="Chat dengan <?php echo $kontak->nama_kontak ?>">
                <i class="fa fa-whatsapp"></i> <?php echo $kontak->nama_kontak ?>
              </button>
            </a>
          <?php 
            }
          } else {
            echo "<p style='color: #7f8c8d; margin: 0; font-size: 13px;'>Belum ada kontak yang tersedia.</p>";
          }
          ?>
        </div>

      </div>

    </div>

    <!-- Copyright -->
    <div class="row">
      <div class="col-lg-12">
        <hr style="border-top: 1px solid #bdc3c7; margin-top: 10px; margin-bottom: 10px;">
      </div>
    </div>
    <div class="row">
      <div class="col-xs-12 text-center">
        <p style="color: #7f8c8d; margin-bottom: 0; font-size: 12px;">&copy; <?php echo date('Y'); ?> UCM Venue. By <a href="https://wyattmatt.github.io/" target="_blank" style="color: #2980b9; font-weight: bold; text-decoration: none;">WyattMatt</a> and <a href="https://gibekkk.github.io/" target="_blank" style="color: #2980b9; font-weight: bold; text-decoration: none;">Gibekkk</a></p>
      </div>
    </div>
  </div>
</footer>

<!-- Back to Top Button - Fixed -->
<a href="#top" id="back-to-top" style="display: none; position: fixed; bottom: 20px; right: 20px; z-index: 9999; background-color: #2c3e50; color: white; width: 40px; height: 40px; text-align: center; line-height: 40px; border-radius: 50%; box-shadow: 0 4px 8px rgba(0,0,0,0.3); transition: all 0.3s ease; text-decoration: none;">
  <i class="fa fa-chevron-up" style="font-size: 16px; line-height: 40px;"></i>
</a>

<script>
// Back to Top Button functionality
(function() {
  var backToTop = document.getElementById('back-to-top');
  
  window.addEventListener('scroll', function() {
    if (window.pageYOffset > 300) {
      backToTop.style.display = 'block';
    } else {
      backToTop.style.display = 'none';
    }
  });
  
  backToTop.addEventListener('click', function(e) {
    e.preventDefault();
    window.scrollTo({top: 0, behavior: 'smooth'});
  });
  
  // Hover effect
  backToTop.addEventListener('mouseenter', function() {
    this.style.backgroundColor = '#34495e';
    this.style.transform = 'scale(1.1)';
  });
  
  backToTop.addEventListener('mouseleave', function() {
    this.style.backgroundColor = '#2c3e50';
    this.style.transform = 'scale(1)';
  });
})();
</script>

  </body>
</html>