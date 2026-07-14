<nav class="navbar navbar-default navbar-fixed-top">
  <div class="container">
    <!-- Brand and toggle get grouped for better mobile display -->
    <div class="navbar-header">
      <button type="button" class="navbar-toggle collapsed" data-toggle="collapse" data-target="#bs-example-navbar-collapse-1" aria-expanded="false">
        <span class="sr-only">Toggle navigation</span>
        <span class="icon-bar"></span>
        <span class="icon-bar"></span>
        <span class="icon-bar"></span>
      </button>
      <a class="navbar-brand" href="<?php echo base_url() ?>">
        <img src="<?php echo base_url('assets/images/company/') . $company_data->foto . $company_data->foto_type ?>" alt="<?php echo $company_data->company_name ?>" style="max-height: 40px; margin-top: -10px;" />
      </a>
    </div>

    <!-- Collect the nav links, forms, and other content for toggling -->
    <div class="collapse navbar-collapse" id="bs-example-navbar-collapse-1">
      <ul class="nav navbar-nav">
        <li class="<?php if ($this->uri->segment(1) == "") {
                      echo "active";
                    } ?>">
          <a href="<?php echo base_url() ?>"><?php echo $this->lang->line('nav_home'); ?></a>
        </li>
        <li class="<?php if ($this->uri->segment(1) == "gallery") {
                      echo "active";
                    } ?>">
          <a href="<?php echo base_url('gallery/album') ?>"><?php echo $this->lang->line('nav_venues'); ?></a>
        </li>
        <li class="<?php if ($this->uri->segment(1) == "event") {
                      echo "active";
                    } ?>">
          <a href="<?php echo base_url('event') ?>"><?php echo $this->lang->line('nav_events'); ?></a>
        </li>
        <li class="dropdown <?php if ($this->uri->segment(1) == "about" or $this->uri->segment(1) == "contact") {
                              echo "active";
                            } ?>">
          <!-- <a href="#" class="dropdown-toggle" data-toggle="dropdown" role="button" aria-haspopup="true" aria-expanded="false">Profil <span class="caret"></span></a> -->
          <ul class="dropdown-menu">
            <li class="<?php if ($this->uri->segment(1) == "about") {
                          echo "active";
                        } ?>">
              <a href="<?php echo base_url('about') ?>"><?php echo $this->lang->line('nav_about'); ?></a>
            </li>
            <li class="<?php if ($this->uri->segment(1) == "contact") {
                          echo "active";
                        } ?>">
              <a href="<?php echo base_url('contact') ?>"><?php echo $this->lang->line('nav_contact'); ?></a>
            </li>
          </ul>
        </li>
        <li class="<?php if ($this->uri->segment(1) == "cart" && $this->uri->segment(2) == "") {
                      echo "active";
                    } ?>">
          <a href="<?php echo base_url('cart') ?>"><?php echo $this->lang->line('nav_cart'); ?></a>
        </li>
      </ul>

      <?php if ($this->session->userdata('usertype') != NULL) { ?>
        <ul class="nav navbar-nav navbar-right">
          <!-- Language Switcher -->
          <li class="dropdown">
            <a href="#" class="dropdown-toggle" data-toggle="dropdown" role="button" aria-haspopup="true" aria-expanded="false">
              <i class="fa fa-globe"></i> 
              <?php 
                $current_lang = isset($current_lang) ? $current_lang : 'english';
                echo get_language_code($current_lang); 
              ?>
              <span class="caret"></span>
            </a>
            <ul class="dropdown-menu">
              <li><a href="<?php echo base_url('language/switch_lang/english'); ?>">🇬🇧 English</a></li>
              <li><a href="<?php echo base_url('language/switch_lang/indonesian'); ?>">🇮🇩 Bahasa Indonesia</a></li>
              <li><a href="<?php echo base_url('language/switch_lang/chinese_simplified'); ?>">🇨🇳 简体中文</a></li>
              <li><a href="<?php echo base_url('language/switch_lang/chinese_traditional'); ?>">🇹🇼 繁體中文</a></li>
            </ul>
          </li>
          
          <!-- User Menu -->
          <li class="dropdown">
            <a href="#" class="dropdown-toggle" data-toggle="dropdown" role="button" aria-haspopup="true" aria-expanded="false">Hi, <?php echo $this->session->userdata('username') ?> <span class="caret"></span></a>
            <ul class="dropdown-menu">
              <li><a href="<?php echo base_url('cart/history') ?>"><?php echo $this->lang->line('nav_booking_history'); ?></a></li>
              <li><a href="<?php echo base_url('auth/edit_profil/') . $this->session->userdata('user_id') ?>"><?php echo $this->lang->line('nav_edit_profile'); ?></a></li>
              <li><a href="<?php echo base_url('auth/profil') ?>"><?php echo $this->lang->line('nav_my_profile'); ?></a></li>
              <li role="separator" class="divider"></li>
              <li><a href="<?php echo base_url('auth/logout') ?>"><?php echo $this->lang->line('nav_logout'); ?></a></li>
            </ul>
          </li>
        </ul>
      <?php } else { ?>
        <ul class="nav navbar-nav navbar-right">
          <!-- Language Switcher -->
          <li class="dropdown">
            <a href="#" class="dropdown-toggle" data-toggle="dropdown" role="button" aria-haspopup="true" aria-expanded="false">
              <i class="fa fa-globe"></i> 
              <?php 
                $current_lang = isset($current_lang) ? $current_lang : 'english';
                echo get_language_code($current_lang); 
              ?>
              <span class="caret"></span>
            </a>
            <ul class="dropdown-menu">
              <li><a href="<?php echo base_url('language/switch_lang/english'); ?>">🇬🇧 English</a></li>
              <li><a href="<?php echo base_url('language/switch_lang/indonesian'); ?>">🇮🇩 Bahasa Indonesia</a></li>
              <li><a href="<?php echo base_url('language/switch_lang/chinese_simplified'); ?>">🇨🇳 简体中文</a></li>
              <li><a href="<?php echo base_url('language/switch_lang/chinese_traditional'); ?>">🇹🇼 繁體中文</a></li>
            </ul>
          </li>
          
          <li><a href="<?php echo base_url('auth/register') ?>"><?php echo $this->lang->line('nav_register'); ?></a></li>
          <li><a href="<?php echo base_url('auth/login') ?>"><?php echo $this->lang->line('nav_login'); ?></a></li>
        </ul>
      <?php } ?>
    </div><!-- /.navbar-collapse -->
  </div><!-- /.container-fluid -->
</nav>