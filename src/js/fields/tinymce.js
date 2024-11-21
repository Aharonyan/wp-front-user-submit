
export default ($) => {
    var container

    $(document).ready(function () {
        $('.textarea-field.tinymce').each((i, val) => {
            container = $(val);
            var textarea_id = $(container).find('textarea').attr('id')
            console.log(textarea_id)
            $(container).parent().mouseenter(function () {
                document.getElementById(textarea_id).value = tinyMCE.get(textarea_id).getContent();
            });
            $(container).parent().mouseout(function () {
                document.getElementById(textarea_id).value = tinyMCE.get(textarea_id).getContent();
            });

            $(container).parent().mouseleave(function () {
                document.getElementById(textarea_id).value = tinyMCE.get(textarea_id).getContent();
            })
        })
    })
}
