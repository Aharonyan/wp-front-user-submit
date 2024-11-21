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
EditorJS(jQuery)
import SlimSelect from 'slim-select';
import front_post_form from './inc/front-form';
import Swal from 'sweetalert2'

import taxonomy from './fields/taxonomy.js';
taxonomy(jQuery, SlimSelect)

import * as FilePond from 'filepond';
// Import the plugin code
import FilePondPluginImageEdit from 'filepond-plugin-image-edit';
import FilePondPluginImagePreview from 'filepond-plugin-image-preview';
import FilePondPluginImageValidateSize from 'filepond-plugin-image-validate-size';
import FilePondPluginImageExifOrientation from 'filepond-plugin-image-exif-orientation';
import FilePondPluginFileValidateType from 'filepond-plugin-file-validate-type';
import FilePondPluginFileValidateSize from 'filepond-plugin-file-validate-size';
import fileField from './fields/file-field';
fileField(FilePond, jQuery,FilePondPluginImageEdit,FilePondPluginImagePreview,FilePondPluginImageValidateSize,FilePondPluginImageExifOrientation,FilePondPluginFileValidateType,FilePondPluginFileValidateSize)

/**
 * Keep in the end to give ability register all hooks
 */
jQuery('.fus-form').each((i, val) => {
    front_post_form(val, jQuery, SlimSelect, Swal)
})


import './fields/bytemd-md-editor';

import textArea from './fields/tinymce';
textArea(jQuery)

import googleMap from './fields/google-map';
googleMap(jQuery)