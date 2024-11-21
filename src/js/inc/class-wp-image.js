export default class WPImage {
    static get toolbox() {
        return {
            title: 'Image & Gallery',
            icon: '<svg  width="17" height="15" id="_x31_" enable-background="new 0 0 24 24" height="512" viewBox="0 0 24 24" width="512" xmlns="http://www.w3.org/2000/svg"><g><path d="m17.453 24c-.168 0-.34-.021-.51-.066l-15.463-4.141c-1.06-.292-1.692-1.39-1.414-2.45l1.951-7.272c.072-.267.346-.422.612-.354.267.071.425.346.354.612l-1.95 7.27c-.139.53.179 1.082.71 1.229l15.457 4.139c.531.14 1.079-.176 1.217-.704l.781-2.894c.072-.267.346-.426.613-.353.267.072.424.347.353.613l-.78 2.89c-.235.89-1.045 1.481-1.931 1.481z"/></g><g><path d="m22 18h-16c-1.103 0-2-.897-2-2v-12c0-1.103.897-2 2-2h16c1.103 0 2 .897 2 2v12c0 1.103-.897 2-2 2zm-16-15c-.551 0-1 .449-1 1v12c0 .551.449 1 1 1h16c.551 0 1-.449 1-1v-12c0-.551-.449-1-1-1z"/></g><g><path d="m9 9c-1.103 0-2-.897-2-2s.897-2 2-2 2 .897 2 2-.897 2-2 2zm0-3c-.551 0-1 .449-1 1s.449 1 1 1 1-.449 1-1-.449-1-1-1z"/></g><g><path d="m4.57 16.93c-.128 0-.256-.049-.354-.146-.195-.195-.195-.512 0-.707l4.723-4.723c.566-.566 1.555-.566 2.121 0l1.406 1.406 3.892-4.67c.283-.339.699-.536 1.142-.54h.011c.438 0 .853.19 1.139.523l5.23 6.102c.18.209.156.525-.054.705-.209.18-.524.157-.705-.054l-5.23-6.102c-.097-.112-.231-.174-.38-.174-.104-.009-.287.063-.384.18l-4.243 5.091c-.09.108-.221.173-.362.179-.142.01-.277-.046-.376-.146l-1.793-1.793c-.189-.188-.518-.188-.707 0l-4.723 4.723c-.097.097-.225.146-.353.146z"/></g></svg>'
        };
    }

    constructor({ data, api, config }) {
        this.api = api;
        this.config = config || {};
        this.data = data;
        // ... this.wrapper
        // ... this.settings
    }

    _createImage(url, captionText) {
        const image = document.createElement('img');
        const caption = document.createElement('div');

        image.src = url;
        caption.contentEditable = true;
        caption.innerHTML = captionText || '';

        this.wrapper.innerHTML = '';
        this.wrapper.appendChild(image);
        this.wrapper.appendChild(caption);
    }

    render() {
        this.wrapper = document.createElement('div');
        this.wrapper.classList.add('fe_wp_image_wrap');
        // Uploading files
        var file_frame;
        // TODO remove jquery in the future
        const $ = window.jQuery;

        const wp_image_wrap = this.wrapper;

        /**
         * If data already exist
         */
        if (this.data.length > 1) {
            this.data.map((attachment) => {
                let image = document.createElement('img');

                image.src = attachment.url;
                image.setAttribute("img_id", attachment.id);

                wp_image_wrap.appendChild(image);
            })

            return this.wrapper;
        }

        const wp_media_button = document.createElement('button');
        wp_media_button.classList.add('image_loader');

        wp_media_button.innerHTML = '';
        wp_media_button.addEventListener('click', (event) => {
            wp_media_button.remove();
            // If the media frame already exists, reopen it.
            if (file_frame) {
                file_frame.open();
                return;
            }

            // Create the media frame.
            file_frame = window.wp.media.frames.file_frame = wp.media({
                title: $(this).data('uploader_title'),
                button: {
                    text: $(this).data('uploader_button_text'),
                },
                multiple: true  // Set to true to allow multiple files to be selected
            });

            // When an image is selected, run a callback.
            file_frame.on('select', () => {
                // We set multiple to false so only get one image from the uploader
                var selection = file_frame.state().get('selection'),
                    attachment_ids = selection.map((attachment) => {
                        attachment = attachment.toJSON();
                        var array = { id: attachment.id, url: attachment.url };
                        let image = document.createElement('img');

                        image.src = attachment.url;
                        image.setAttribute("img_id", attachment.id);

                        wp_image_wrap.appendChild(image);

                        return array;
                    }).join();
            });
            file_frame.open();
        });

        this.wrapper.appendChild(wp_media_button);

        return this.wrapper;
    }

    save(blockContent) {

        let [...attachments] = [...blockContent.querySelectorAll('img')].map((attachment) => {
            return {
                'id': attachment.getAttribute('img_id'),
                'url': attachment.src
            };
        });

        return attachments;
    }
}