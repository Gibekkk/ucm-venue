<?php $this->load->view('front/header'); ?>
<?php $this->load->view('front/navbar'); ?>

<style>
  /* Class khusus untuk menyamakan ukuran gambar */
  .img-event-custom {
    width: 100%;
    max-width: 1280px;
    height: auto;          /* Tinggi mengikuti rasio asli gambar, tidak dipotong */
    display: block;
    border-radius: 4px;   /* Opsional: Membuat ujung gambar sedikit tumpul */
  }
  
  /* Memberikan jarak bawah agar tidak terlalu mepet dengan teks */
  .event-meta {
    margin-top: 15px;
  }
  
  /* Memberikan jarak antar event */
  .event-item {
    margin-bottom: 40px;
  }
</style>

<div class="container">
    <ol class="breadcrumb">
        <li><a href="<?php echo base_url() ?>">Home</a></li>
        <li><a href="#">Events</a></li>
        <li class="active">Semua Events</li>
    </ol>

    <div class="row">
        <div class="col-md-12">
            <h1>SEMUA EVENTS</h1><hr>
            
            <?php foreach($event_all as $event){ ?>
              <div class="event-item">
                <h2><a href="<?php echo base_url('event/').$event->slug_event ?>"><?php echo $event->nama_event ?></a></h2>
                
                <a href="<?php echo base_url("event/$event->slug_event ") ?>">
                    <?php
                    if(empty($event->foto)) {
                        echo "<img class='img-responsive img-event-custom' src='".base_url()."assets/images/no_image_thumb.png'>";
                    }
                    else { 
                        echo "<img class='img-responsive img-event-custom' src='".base_url()."assets/images/event/".$event->foto.'_thumb'.$event->foto_type."'>";
                    }
                    ?>
                </a>
                
                <p class="event-meta">
                    <i class="fa fa-user"></i> <?php echo $event->created_by ?> &nbsp;&nbsp;
                    <i class="fa fa-calendar"></i> <?php echo date("j F Y", strtotime($event->created_at)); ?>
                </p>
                <p><?php echo character_limiter($event->deskripsi,350) ?></p>
                <a class="btn btn-sm btn-primary" href="<?php echo base_url("event/$event->slug_event ") ?>">Selengkapnya <i class="fa fa-angle-right"></i></a>
              </div>
            <?php } ?>
            
            <div align="center" style="margin-top: 30px;"><?php echo $this->pagination->create_links() ?></div>
        </div>
        <?php /* $this->load->view('front/sidebar'); */ ?>
    </div>
</div>

<?php $this->load->view('front/footer'); ?>