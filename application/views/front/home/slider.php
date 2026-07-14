<script src="<?php echo base_url('assets/plugins/slider/src/')?>slippry.js"></script>
<link rel="stylesheet" href="<?php echo base_url('assets/plugins/slider/dist/')?>slippry.css" />
<div class="big-title" data-animation="fadeInDown" data-animation-delay="02">
  <ul id="thumbnails">
    <?php foreach($slider_data as $slider){ ?>
    <li>
      <a href="<?php echo $slider->link ?>" target="_self">
        <?php 
        $video_types = array('.mp4', '.webm');
        if (in_array(strtolower($slider->foto_type), $video_types)) {
          echo '<video src="'.base_url('assets/images/slider/'.$slider->foto.$slider->foto_type).'" style="width: 100%; height: 700px;" autoplay muted loop playsinline></video>';
        } else {
          echo '<img src="'.base_url('assets/images/slider/'.$slider->foto.$slider->foto_type).'" alt="'.$slider->nama_slider.'">';
        }
        ?>
      </a>
    </li>
    <?php } ?>
  </ul>
</div>

<script type="text/javascript">
  var thumbs = jQuery('#thumbnails').slippry({
  // general elements & wrapper
  slippryWrapper: '<div class="slippry_box thumbnails" />',
  // options
  transition: 'horizontal',
  onSlideBefore: function (el, index_old, index_new) {
    jQuery('.thumbs a img').removeClass('active');
    jQuery('img', jQuery('.thumbs a')[index_new]).addClass('active');
  }
  });

  jQuery('.thumbs a').click(function () {
  thumbs.goToSlide($(this).data('slide'));
  return false;
  });
</script>
