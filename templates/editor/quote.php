<?php
    if ($data['alignment'] == 'center') {
        $centerClass = 'bfe-quote--center';
    } else {
        $centerClass = '';
    }
?>
<!-- wp:quote -->
<blockquote class="wp-block-quote bfe-quote <?= $centerClass ?>"><cite><?= $data['text'] ?></cite><p><?= $data['caption'] ?></p></blockquote>
<!-- /wp:quote -->