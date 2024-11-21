'use strict';

import EditorJS from '@editorjs/editorjs';

import Header from '@editorjs/header';

import Paragraph from '@editorjs/paragraph';

import ImageTool from '@editorjs/image';

import Embed from '@editorjs/embed';

import Quote from '@editorjs/quote';

import Marker from '@editorjs/marker';

import CodeTool from '@editorjs/code';

//import LinkTool from '@editorjs/link';

import List from '@editorjs/list';

import Delimiter from '@editorjs/delimiter';

import InlineCode from '@editorjs/inline-code';

//import RawTool from '@editorjs/raw';

import Warning from '@editorjs/warning';

import Table from '@editorjs/table';

import Checklist from '@editorjs/checklist';

import WPImage from './class-wp-image';

//import ImageGallery from '@rodrigoodhin/editorjs-image-gallery';

import Carousel from '@vietlongn/editorjs-carousel';

import AttachesTool from '@editorjs/attaches';

import RawTool from '@editorjs/raw';

import AlignmentTuneTool from 'editorjs-text-alignment-blocktune';


export default ($) => {
    var editor_js,
        editor_data = window.editor_data,
        editor_js_settings;
    const editors = {};

    $(document).ready(function () {
        $('.EditorJS-editor').each((i, val) => {
            const container_id = $(val).attr('id');
            editor_js_settings = get_editor_settings(container_id,container_id)
            editors[container_id] = new EditorJS(editor_js_settings);

            $('#' + container_id).mouseenter(function () {
                onChangeSaveData(container_id)
            });

            $('#' + container_id).mouseleave(function () {
                onChangeSaveData(container_id)
            })
        })
    })

    // or if you inject ImageTool via standalone script
    //const Carousel = window.Carousel;

    try {
        editor_data = JSON.parse(editor_data);
    }
    catch (e) {
        editor_data = editor_data;
    }

    /**
     * Save on change data
     */
    function onChangeSaveData(container_id) {
        const $editor_js_object = editors[container_id];
        console.log($editor_js_object)
        if($editor_js_object.save()){
            $editor_js_object.save().then((data) => {
                document.getElementById(`${container_id}-textarea`).value = JSON.stringify(data);
            });
        }
    }

    /**
     * Editor js params
     * @returns
     */
    function get_editor_settings(editor_id,container_id) {
        const editor_js = editors[container_id];
        const editor_settings = editor_data.editor_js[editor_id].settings
        const currentEditorContent = editor_data.editor_js[editor_id].content
        let active_wp_media_uploader = (editor_data.is_user_logged_in && editor_settings.editor_gallery_plugin) ? true : false;

        return {
            holder: container_id,
            placeholder: editor_settings.placeholder??'',
            autofocus: false,
            i18n: editor_data.translations.i18n,
            tools: {
                ...(editor_settings.editor_header_plugin && {
                    header: {
                        class: Header,
                        inlineToolbar: true,
                        tunes: ['AlignmentTool'],
                        config: {
                            placeholder: editor_settings.editor_header_placeholder_plugin ?? 'Enter a header',
                            levels: [2, 3, 4],
                            defaultLevel: 3
                        },
                        shortcut: 'CMD+SHIFT+H'
                    }
                }),
                ...(active_wp_media_uploader && {
                    wpImageGallery: {
                        class: WPImage,
                        inlineToolbar: true,
                        config: {
                            wp_media_uploader: wpMediaUploader()
                        }
                    }
                }),
                ...(editor_settings.editor_image_plugin && {
                    image: {
                        class: ImageTool,
                        inlineToolbar: true,
                        config: {
                            uploader: {
                                uploadByFile(file) {
                                    return uploadImage(file).then(data => {
                                        if (!data.success) {
                                            editor_js.notifier.show({
                                                message: data.data.message ?? 'Something goes wrong try later',
                                                style: 'error',
                                            });
                                            return { "success": 0 };
                                        }
                                        return {
                                            "success": 1,
                                            "file": {
                                                "url": data.data.url,
                                            }
                                        };
                                    })
                                },
                                uploadByUrl(url) {
                                    return uploadImage(null, url).then(data => {
                                        if (!data.success) {
                                            editor_js.notifier.show({
                                                message: data.data.message ?? 'Something goes wrong try later',
                                                style: 'error',
                                            });
                                            return { "success": 0 };
                                        }
                                        return {
                                            "success": 1,
                                            "file": {
                                                "url": data.data.url,
                                            }
                                        };
                                    })
                                }

                            }

                        }
                    }
                }),

                ...(editor_settings.editor_list_plugin && {
                    list: {
                        class: List,
                        inlineToolbar: true,
                        shortcut: 'CMD+SHIFT+L'
                    }
                }),

                ...(editor_settings.editor_checklist_plugin && {
                    checklist: {
                        class: Checklist,
                        inlineToolbar: true,
                    }
                }),

                ...(editor_settings.editor_quote_plugin && {
                    quote: {
                        class: Quote,
                        inlineToolbar: true,
                        config: {
                            quotePlaceholder: editor_settings.editor_quote_placeholder ?? 'Enter a quote hey',
                            captionPlaceholder: editor_settings.editor_quote_caption_placeholder ?? 'Quote\'s author hey',
                        },
                        shortcut: 'CMD+SHIFT+O'
                    },
                }),

                ...(editor_settings.editor_warning_plugin && {
                    warning: Warning,
                }),

                ...(editor_settings.editor_marker_plugin && {
                    marker: {
                        class: Marker,
                        shortcut: 'CMD+SHIFT+M'
                    }
                }),

                ...(editor_settings.editor_code_plugin && {
                    code: {
                        class: CodeTool,
                        shortcut: 'CMD+SHIFT+C',
                    },
                }),

                ...(editor_settings.editor_delimiter_plugin && {
                    delimiter: Delimiter
                }),

                ...(editor_settings.editor_inlineCode_plugin && {
                    inlineCode: {
                        class: InlineCode,
                        shortcut: 'CMD+SHIFT+C'
                    },
                }),

                ...(editor_settings.editor_embed_plugin && {
                    embed: {
                        class: Embed,
                        inlineToolbar: true,
                        // config: {
                        //     services: {
                        //         youtube: {
                        //             height: 100,
                        //             width: 100,
                        //         }
                        //     }
                        // }
                    }


                }),

                ...(editor_settings.editor_table_plugin && {
                    table: {
                        class: Table,
                        inlineToolbar: true,
                        shortcut: 'CMD+ALT+T'
                    },
                }),
                ...(editor_settings.editor_carousel_plugin && {
                    carousel: {
                        class: Carousel,
                        config: {
                            endpoints: {
                                byFile: editor_data.rest_url_image,
                            }
                        }
                    },
                }),
                ...(editor_settings.editor_attaches_plugin && {
                    attaches: {
                        class: AttachesTool,
                        config: {
                            endpoint: editor_data.rest_url_upload_file
                        }
                    }
                }),
                paragraph: {
                    class: Paragraph,
                    inlineToolbar: true,
                    tunes: ['AlignmentTool'],
                },
                AlignmentTool: {
                    class: AlignmentTuneTool,
                    config: {
                        default: "left",
                    }
                },
                //linkTool: LinkTool,
                //raw: RawTool
            },
            defaultBlock: 'paragraph',
            ...((typeof currentEditorContent === 'object' && 'blocks' in currentEditorContent) && {
                data: {
                    blocks: currentEditorContent.blocks
                }
            }),
            onReady: () => {
                console.log(currentEditorContent);
                // Checking if the post have saved blokes or not
                if (typeof currentEditorContent === 'object' && 'blocks' in currentEditorContent) {
                    return;
                }

                if (currentEditorContent.length === 0) {
                    return;
                }

                editor_js.blocks.renderFromHTML(currentEditorContent)
                    .catch(error => {
                        console.log('Error with rendering HTML data ' + error);
                    });
            },
            onChange: () => {
                onChangeSaveData()
            }
        }

    };

    function objectifyForm(formArray) {
        //serialize data function
        var returnArray = {};
        for (var i = 0; i < formArray.length; i++) {
            returnArray[formArray[i]['name']] = formArray[i]['value'];
        }
        return returnArray;
    }

    /**
     * uploading image
     */
    function uploadImage(file = null, url = null) {
        return new Promise((resolve, reject) => {
            const formData = new FormData();
            let post_id = document.querySelector(`.fus-form`).getAttribute('post_id');

            formData.append('action', 'bfe_uploading_image')

            formData.append('post_id', post_id)
            formData.append('form_id', editor_data.form_id)
            formData.append('x-wp-nonce', editor_data.nonce)

            if (file !== null) {
                formData.append('image', file)
            }
            if (url !== null) {
                formData.append('image_url', url)
            }

            fetch(editor_data.rest_url_post_thumb_uploading_image, {
                method: 'POST',
                body: formData,
                headers: {
                    'X-WP-Nonce': editor_data.nonce
                }
            })
                .then(response => response.json())
                .then(data => {
                    if (data.success) {
                        resolve(data);
                    } else {
                        resolve(data);
                    }
                }).catch()
        })
    }

    function wpMediaUploader() {

        var file_frame = 0;

        if (file_frame) {
            file_frame.open();
            return;
        }

        // Create the media frame.
        file_frame = wp.media.frames.file_frame = wp.media({
            title: jQuery(this).data('uploader_title'),
            button: {
                text: jQuery(this).data('uploader_button_text'),
            },
            multiple: true,  // Set to true to allow multiple files to be selected
            type: 'image'//audio, video, application/pdf, ... etc
        });

        // When an image is selected, run a callback.
        file_frame.on('select', function () {
            // We set multiple to false so only get one image from the uploader
            var selection = file_frame.state().get('selection').first().toJSON();

            console.log(selection.url)

            // var attachment_ids = selection.map(function (attachment) {
            //     attachment = attachment.toJSON();
            //     var array = {id:attachment.id,url:attachment.url}
            //     return array;
            // }).join();

            //file_frame.open();
        });

    }
}