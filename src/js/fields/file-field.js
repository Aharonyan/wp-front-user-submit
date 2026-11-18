

export default (
    FilePond,
    $,
    FilePondPluginImageEdit,
    FilePondPluginImagePreview,
    FilePondPluginImageValidateSize,
    FilePondPluginImageExifOrientation,
    FilePondPluginFileValidateType,
    FilePondPluginFileValidateSize
) => {

    // Get a reference to the file input element
    const allFileWraps = document.querySelectorAll('.file-field');
    const formData = window.editor_data;
    if (allFileWraps) {
        var fileElementArray = [...allFileWraps];
        fileElementArray.forEach(fileElem => {

            FilePond.registerPlugin(
                FilePondPluginImagePreview,
                FilePondPluginImageExifOrientation,
                FilePondPluginImageValidateSize,
                FilePondPluginImageEdit,
                FilePondPluginFileValidateType,
                FilePondPluginFileValidateSize
            );

            FilePond.setOptions({
                server: {
                    url: formData.get_rest_url,
                    // the REST routes check the form and the nonce
                    headers: { 'X-WP-Nonce': formData.nonce },
                    process: {
                        url: '/process',
                        ondata: (data) => {
                            data.append('form_id', formData.form_id);
                            return data;
                        },
                    },
                    revert: '/revert',
                    restore: '/restore/',
                    load: '/load/',
                    fetch: '/fetch/',
                    remove: (source, load, error) => {
                        try {
                            const response = fetch(formData.get_rest_url + '/revert', {
                                method: "DELETE",
                                body: source,
                                headers: { 'X-WP-Nonce': formData.nonce },
                            });
                        } catch (error) {
                            error("Error:", error);
                        }
                        load();
                    },

                }
            });

            var fileInput = fileElem.querySelector('input[type="file"].file_upload');
            var inputForSave = fileElem.querySelector('input.file_field');
            var labels = fileElem.querySelector('script[type="application/json"]')

            var args = {
                'allowReorder': true,
                'credits': false,
            };

            if (labels) {
                labels = JSON.parse(labels.textContent)
                args = {
                    ...args,
                    ...labels
                };
            }

            // Create a FilePond instance
            var pond = FilePond.create(fileInput, args);

            pond.on('processfile', (error, file) => {
                if (error) {
                    console.log('Some issue');
                    return;
                }

                save_to_input();
            });

            pond.on('removefile', (error, file) => {
                if (error) {
                    console.log('Some issue');
                    return;
                }

                save_to_input();
            });

            pond.on('init', (error, file) => {
                save_to_input()
            });

            pond.on('reorderfiles', (error, file) => {
                save_to_input();
            });

            window.fe_hooks.add_action('before_sending_ajax_request', (options) => {
                save_to_input()
            })


            function save_to_input() {
                var fileList = pond.getFiles();
                var idsArray = [];
                fileList.forEach(file => {
                    idsArray.push(file.serverId)
                })

                inputForSave.value = JSON.stringify(idsArray);
                console.assert(inputForSave.value)
            }
        });
    }
}