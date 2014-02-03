<div class="trace-query" style="width: 600px;">
    <div style="width: 80%; overflow: auto; float: left;" data-id="<?php echo $query->md5; ?>"><?php echo $sql; ?></div>
    <div style="float:right; width: 17%; margin-left: 3%"><?php echo $query->status; if($query->message) { echo ', ' . $query->message; } ?></div>
    <div style="clear: both"></div>
    <?php
    /*if($query->message)
   // {?>
     <div class="message"><?php echo $query->message; ?></div>
  //  <?php
  //  } */
    ?>
</div>