<!-- wp:table -->
<figure class="wp-block-table"><table class="bfe-table"><?php foreach ((array) ($data['content'] ?? []) as $row) : ?><tr><?php foreach ((array) $row as $cell) : ?><td><?= wp_kses_post((string) $cell) ?></td><?php endforeach ?></tr><?php endforeach ?></table></figure>
<!-- /wp:table -->