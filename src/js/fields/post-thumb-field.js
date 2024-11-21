(($) => {
    var fe_data = window.editor_data

    function readURL(input) {
        if (input.files && input.files[0]) {

            var maxSize = 6291456,
                size = input.files[0].size,
                isOk = maxSize > size;

            if (!isOk) {

                bfe_page_editor.bfee_editor.notifier.show({
                    message: 'Image is too big, please choose another one. Max size can be 6mb',
                    style: 'error',
                });

                return;
            }

            var reader = new FileReader();

            reader.onload = function (e) {
                $('#post_thumbnail_image').attr('style', 'background:url(' + e.target.result + ')');
                $('div.image_loader').addClass('chosen');
                document.querySelector('.fus-form .image_loader').setAttribute('thumb_exist', '1');
            }

            reader.readAsDataURL(input.files[0]); // convert to base64 string
        }
    }

    $("#img_inp").change(function (e) {
        var img = this,
            show_loading = document.querySelector('.image_loader .fus-loader')
            current_size = img.files[0].size
            //size_from_settings = fe_data.post_thumb.max_file_size * 1000000
        const formData = new FormData();
        formData.append('image', document.querySelector('#img_inp').files[0])
        formData.append('form_id', fe_data.form_id)

        // if(size_from_settings < current_size){
        //     return;
        // }

        show_loading.classList.toggle("active");

        formData.append('x-wp-nonce', fe_data.nonce)
        fetch(fe_data.rest_url_post_thumb_uploading_image, {
            method: 'POST',
            body: formData,
            headers: {
                'X-WP-Nonce': fe_data.nonce
            }
        })
        .then(response => response.json())
        .then(response => {
            if (response.success) {
                readURL(img);
                $('#thumb_img_id').val(response.data.id)
            } else {
                console.log(response)
            }
            show_loading.classList.toggle("active");
        }).catch((response) => {
            show_loading.classList.toggle("active");
        })
    });

    $('div.image_loader .bfe-remove-image').click(() => {
        $('div.image_loader').removeClass('chosen');
        $("#img_inp").val('');

        $('#post_thumbnail_image').removeAttr('att-id');
        $('#thumb_img_id').val('')

        document.querySelector('.bfe-editor .image_loader').setAttribute('thumb_exist', '0');
    })


    /**
     * If WP Media Uploader is enabled for post thumb image
     */
    if (window.editor_data.post_thumb.wp_media_uploader) {
        // Uploading files
        var file_frame;

        $('.image_loader input').on('click', function (event) {
            event.preventDefault();

            // If the media frame already exists, reopen it.
            if (file_frame) {
                file_frame.open();
                return;
            }

            // Create the media frame.
            file_frame = wp.media.frames.file_frame = wp.media({
                title: $(this).data('uploader_title'),
                button: {
                    text: $(this).data('uploader_button_text'),
                },
                multiple: false,  // Set to true to allow multiple files to be selected
                type : 'image' //audio, video, application/pdf, ... etc
            });

            // When an image is selected, run a callback.
            file_frame.on('select', function () {
                // We set multiple to false so only get one image from the uploader
                var selection = file_frame.state().get('selection').first().toJSON(),
                    post_thumbnail_image = $('#post_thumbnail_image'),
                    thumb_img_id = $('#thumb_img_id');

                $('div.image_loader').addClass('chosen');
                post_thumbnail_image.attr('src', selection.url);
                post_thumbnail_image.attr('att-id', selection.id);
                thumb_img_id.val(selection.id)
            });
            file_frame.open();
        });
    }


})(jQuery)

