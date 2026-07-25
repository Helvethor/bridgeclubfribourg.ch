const $ = require('jquery');
const tinymce = require('tinymce/tinymce');

$(document).ready(function() {
    tinymce.init({
        selector: '.tinymce',
        base_url: '/build/tinymce',
        suffix: '.min',
        license_key: 'gpl',
        height: 400
    });
});
