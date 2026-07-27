  <footer style="background-color: #ecf0f1; padding: 40px 0 20px 0; margin-top: 50px; width: 100%;">
  <div class="container">
    <div class="row">
      <!-- Social Media & Facebook Widget -->
      <div class="col-lg-4 col-md-4 col-sm-12 mb-4">
        <div class="bs-callout bs-callout-primary">
          <h4><i class="fa fa-search"></i> <?php echo $this->lang->line('footer_social_media'); ?></h4>
        </div>
        <div class="mb-3">
          <button type="button" class="btn btn-sm btn-primary" onclick="window.open('https://www.facebook.com/UCMakassarOfficial/','_blank')"><i class="fa fa-facebook"></i></button>&nbsp;
          <button type="button" class="btn btn-sm btn-primary" onclick="window.open('https://www.instagram.com/uc_makassar/','_blank')"><i class="fa fa-instagram"></i></button>&nbsp;
          <button type="button" class="btn btn-sm btn-primary" onclick="window.open('https://x.com/UCMakassar','_blank')"><i class="fa fa-twitter"></i></button>&nbsp;
          <button type="button" class="btn btn-sm btn-primary" onclick="window.open('https://www.youtube.com/c/UCMakassar','_blank')"><i class="fa fa-youtube-play"></i></button>
        </div>
        <div class="bs-callout bs-callout-primary mt-3">
          <h4><i class="fa fa-facebook"></i> Facebook</h4>
        </div>
        <div id="fb-root"></div>
        <script async defer crossorigin="anonymous" src="https://connect.facebook.net/en_US/sdk.js#xfbml=1&version=v10.0&appId=1079046148857661&autoLogAppEvents=1" nonce="NRs3YdmA"></script>
        <div class="fb-page" data-href="https://www.facebook.com/UCMakassarOfficial/" data-tabs="timeline" data-width="250" data-height="282" data-small-header="true" data-adapt-container-width="true" data-hide-cover="false" data-show-facepile="false">
          <blockquote cite="https://www.facebook.com/UCMakassarOfficial/" class="fb-xfbml-parse-ignore"><a href="https://www.facebook.com/UCMakassarOfficial/">UCMakassarOfficial</a></blockquote>
        </div>
      </div>

      <!-- Event Terbaru -->
      <div class="col-lg-4 col-md-4 col-sm-12 mb-4">
        <div class="bs-callout bs-callout-primary">
          <h4><i class="fa fa-newspaper-o"></i> <?php echo $this->lang->line('footer_latest_events'); ?></h4>
        </div>
        <ul class="list-group">
          <?php
          if (isset($event_sidebar) && is_array($event_sidebar)) {
            foreach ($event_sidebar as $event_sidebar_item) {
          ?>
            <li class="list-group-item">
              <span class="badge">NEW</span>
              <?php echo anchor('event/read/' . $event_sidebar_item->slug_event . '', '' . $event_sidebar_item->nama_event . '') ?>
            </li>
          <?php 
            }
          } else {
            echo '<li class="list-group-item">' . $this->lang->line('footer_no_events') . '</li>';
          }
          ?>
        </ul>
      </div>

      <!-- Hubungi Kami -->
      <div class="col-lg-4 col-md-4 col-sm-12 mb-4">
        <div class="bs-callout bs-callout-primary">
          <h4><i class="fa fa-phone"></i> <?php echo $this->lang->line('footer_contact_us'); ?></h4>
        </div>
        <ul class="list-group">
          <div align="left"><img src="<?php echo base_url('assets/images/wa.jpg') ?>" width="100px" class="img-responsive"></div>
          <?php 
          if (isset($kontak_sidebar) && is_array($kontak_sidebar)) {
            foreach($kontak_sidebar as $kontak){
          ?>
            <b><?php echo $kontak->nama_kontak ?></b><br>
            +<?php echo $kontak->nohp ?><br>
            <a href="https://api.whatsapp.com/send?phone=+<?php echo $kontak->nohp ?>&text=Hai%20Kak%2C%20saya%20mau%20tanya-tanya%20seputar%20informasi%20booking%20tempat%20di%20UC%20Makassar">
              <button class="btn btn-success btn-sm" type="submit" name="button">Chat via Whatsapp</button>
            </a><br><br>
          <?php 
            }
          }
          ?>
        </ul>
      </div>
    </div>

    <!-- Copyright -->
    <div class="row">
      <div class="col-lg-12">
        <!-- <hr> -->
      </div>
    </div>
    <div class="row">
      <div class="col-xs-12 text-center" style="margin-top: 8px;">
        <p>&copy; <?php echo date('Y'); ?> UCM Venue. By <a href="https://wyattmatt.github.io/" target="_blank">WyattMatt</a></p>
      </div>
    </div>
  </div>
</footer>

<!-- Back to Top Button - Fixed -->
<a href="#top" id="back-to-top" style="display: none; position: fixed; bottom: 20px; right: 20px; z-index: 9999; background-color: #2c3e50; color: white; width: 50px; height: 50px; text-align: center; line-height: 50px; border-radius: 50%; box-shadow: 0 2px 5px rgba(0,0,0,0.3); transition: all 0.3s ease;">
  <i class="fa fa-chevron-up" style="font-size: 24px;"></i>
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
    this.style.backgroundColor = '#3b5168';
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