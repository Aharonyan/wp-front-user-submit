<!-- wp:list -->
<ul class="bfe-checklist">
    <?php foreach ((array) ($data['items'] ?? []) as $list) : ?>
        <li class="bfe-checklist-item <?php echo (!empty($list['checked']) && $list['checked'] !== 'false') ? 'checked': ''; ?>">
            <?= wp_kses_post($list['text'] ?? '') ?>
        </li>

    <?php endforeach ?>
</ul>
<!-- /wp:list -->