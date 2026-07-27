<style>
  /* Menyamakan tinggi gambar dan mencegah gambar melar/gepeng */
  .img-lapangan {
    width: 100%;
    height: auto;
  }
  
  /* Menyamakan tinggi kotak thumbnail/card */
  .thumbnail-lapangan {
    height: 100%;
    display: flex;
    flex-direction: column;
    margin-bottom: 25px; /* Jarak antar baris */
  }

  /* Mendorong tombol agar selalu berada di bawah jika panjang nama lapangan berbeda */
  .caption-lapangan {
    flex-grow: 1;
    display: flex;
    flex-direction: column;
    justify-content: space-between;
  }
</style>

<hr><h3 align="center"><b>VENUES</b></h3><hr>
<div class="row">
  <?php foreach($lapangan_new as $lapangan){ ?>
    <!-- Mengubah class grid menjadi col-md-6 agar tampil 2 per baris -->
    <div class="col-sm-12 col-md-6 col-lg-6">
      <div class="thumbnail thumbnail-lapangan">
        <?php
        if(empty($lapangan->foto)) {
            echo "<img class='card-img-top img-lapangan' src='".base_url()."assets/images/no_image_thumb.png'>";
        }
        else { 
            echo "<img class='img-lapangan' src='".base_url()."assets/images/lapangan/".$lapangan->foto."'> ";
        }
        ?>
        <div class="caption caption-lapangan">
          <div>
            <p class="card-text" style="margin-top: 10px;"><b><?php echo $lapangan->nama_lapangan ?></b></p>
            <hr>
          </div>
          <a href="<?php echo base_url('cart/buy/').$lapangan->id_lapangan ?>">
            <button class="btn btn-sm btn-primary" style="width: 100%;"><i class="fa fa-shopping-cart"></i> Booking Sekarang!</button>
          </a>
        </div>
      </div>
    </div>
  <?php } ?>
</div>