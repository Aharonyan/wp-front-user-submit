<?php 
$url = $data['file']['url'];
$title = $data['title'];
?>
<!-- wp:file {"href":"<?php echo $url;?>"} -->
<div class="wp-block-file"><a href="<?php echo $url;?>"><?php echo $title;?></a><a href="<?php echo $url;?>" class="wp-block-file__button" download><?php _e("Download", 'front-editor')?></a></div>
<!-- /wp:file -->