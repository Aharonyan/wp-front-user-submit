/**
 * Gutenberg Blocks
 *
 * All blocks related JavaScript files should be imported here.
 * You can create a new block folder in this dir and include code
 * for that block here as well.
 *
 * All blocks should be included here since this is the file that
 * Webpack is compiling as the input file.
 */

import Hooks from './inc/hooks'

window.fe_hooks = Hooks;

import './functions';

import './fields/post-thumb-field';

import EditorJS from './inc/editor-js-logic';
import SlimSelect from 'slim-select';
import front_post_form from './inc/front-form';
import Swal from 'sweetalert2'

import taxonomy from './fields/taxonomy.js';

import * as FilePond from 'filepond';
// Import the plugin code
import FilePondPluginImageEdit from 'filepond-plugin-image-edit';
import FilePondPluginImagePreview from 'filepond-plugin-image-preview';
import FilePondPluginImageValidateSize from 'filepond-plugin-image-validate-size';
import FilePondPluginImageExifOrientation from 'filepond-plugin-image-exif-orientation';
import FilePondPluginFileValidateType from 'filepond-plugin-file-validate-type';
import FilePondPluginFileValidateSize from 'filepond-plugin-file-validate-size';
import fileField from './fields/file-field';
import freshNonces, { hasCookie, isStalePage, useRestNonce } from './inc/fresh-nonces';

function initForms() {
    EditorJS(jQuery)
    taxonomy(jQuery, SlimSelect)
    fileField(FilePond, jQuery,FilePondPluginImageEdit,FilePondPluginImagePreview,FilePondPluginImageValidateSize,FilePondPluginImageExifOrientation,FilePondPluginFileValidateType,FilePondPluginFileValidateSize)

    /**
     * Keep in the end to give ability register all hooks
     */
    jQuery('.fus-form').each((i, val) => {
        front_post_form(val, jQuery, SlimSelect, Swal)
    })
}

// A page from a page cache may carry expired nonces, and cached or page builder pages come
// without the guest browser cookie. EditorJS and FilePond keep the upload URLs and headers
// they start with, so they start after the nonce and the cookie are there
const formData = window.editor_data || {};
const isGuest = !formData.is_user_logged_in;
const hasUploads = Boolean(document.querySelector('.fus-form .file-field, .fus-form #img_inp, .fus-form .EditorJS-editor'));
const needsFreshNonces = isStalePage(isGuest, formData.page_time, formData.nonce_life)
    || (isGuest && hasUploads && formData.visitor_cookie && !hasCookie(formData.visitor_cookie));

if (needsFreshNonces && formData.fresh_nonces_url) {
    // the buttons submit the form natively until the form script takes over
    const holdSubmit = (event) => event.preventDefault();
    document.querySelectorAll('.fus-form').forEach((form) => form.addEventListener('submit', holdSubmit));
    freshNonces(formData.fresh_nonces_url)
        .then((nonces) => {
            if (nonces) {
                useRestNonce(formData, nonces.rest)
            }
        })
        .finally(() => {
            document.querySelectorAll('.fus-form').forEach((form) => form.removeEventListener('submit', holdSubmit));
            initForms()
        })
} else {
    initForms()
}

import aiAssistant from './inc/ai-assistant';
jQuery(() => {
    document.querySelectorAll('.fus-form').forEach((form) => aiAssistant(form))
})


import './fields/bytemd-md-editor';

import textArea from './fields/tinymce';
textArea(jQuery)

import googleMap from './fields/google-map';
googleMap(jQuery)