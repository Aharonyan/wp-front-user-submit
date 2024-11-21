<div class="wrap">
    <h2>
        <?php
            esc_html_e( 'Post Forms', 'front-editor' );

            if ( current_user_can( is_admin() ) ) {
                ?>
                <a href="<?php echo esc_url( $add_new_page_url ); ?>" id="new-fe-post-form" class="page-title-action add-form"><?php esc_html_e( 'Add Form', 'front-editor' ); ?></a>
            <?php
            }
        ?>
    </h2>


    <div class="list-table-wrap fe-post-form-wrap">
        <div class="list-table-inner fe-post-form-wrap-inner">

            <form method="get">
                <input type="hidden" name="page" value="fe-post-forms">
                <?php
                    $fe_post_form = new PostFormsListTable();
                    $fe_post_form->prepare_items();
                    $fe_post_form->search_box( __( 'Search Forms', 'front-editor' ), 'fe-post-form-search' );

                    if ( current_user_can( fe_admin_role() ) ) {
                        $fe_post_form->views();
                    }

                    $fe_post_form->display();
                ?>
            </form>

        </div><!-- .list-table-inner -->
    </div><!-- .list-table-wrap -->
</div>
