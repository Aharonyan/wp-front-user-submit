import { Editor, Viewer } from 'bytemd';
import gfm from '@bytemd/plugin-gfm';
import bytemd_highlight from '@bytemd/plugin-highlight';
import byte_ru from '../vendors/bytemd/ru.json'

const $ = jQuery;

$('.md-editor').each((i, val) => {
  let id = $(val).attr('id'),
    textarea = $(`#${id}-textarea`),
    lang = $(val).attr('locale');

  const plugins = [
    gfm(),
    bytemd_highlight()
    // Add more plugins here
  ];

  const props = {
    value: textarea.val(),
    plugins
  }

  if(lang === 'ru_RU'){
    props.locale = byte_ru
  }

  const md_editor = new Editor({
    target: document.getElementById(id), // DOM to render
    props: props,
  });

  md_editor.$on('change', (e) => {
    md_editor.$set({ value: e.detail.value });
    textarea.val(e.detail.value)
  });
})



