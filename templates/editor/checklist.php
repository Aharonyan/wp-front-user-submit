<!-- wp:list -->
<ul class="bfe-checklist">
    <?php foreach ($data['items'] as $list) : ?>
        <li class="bfe-checklist-item <?php echo ($list['checked'] == "true") ? 'checked': ''; ?>">
            <?= $list['text'] ?>
        </li>

    <?php endforeach ?>
</ul>
<!-- /wp:list -->