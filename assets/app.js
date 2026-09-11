/*
 * Welcome to your app's main JavaScript file!
 *
 * We recommend including the built version of this JavaScript file
 * (and its CSS file) in your base layout (base.html.twig).
 */

// any CSS you require will output into a single css file (app.css in this case)
import './css/app.scss';

// Load image assets
const imagesContext = require.context('./images', true, /\.(png|jpg|jpeg|gif|ico|svg|webp)$/);
imagesContext.keys().forEach(imagesContext);

// start the Stimulus application
import './bootstrap';

// jQuery and third-party libraries
const $ = require('jquery');
window.$ = window.jQuery = $;

// Bootstrap 5 (includes Popper automatically)
require('bootstrap');

// DataTables with Bootstrap 5 styling
const dataTables = require('datatables.net/js/dataTables.mjs');
window.DataTable = dataTables.default || dataTables.DataTable || dataTables;
require('datatables.net-bs5/js/dataTables.bootstrap5.mjs');

// Custom application JavaScript
require('./js/event');
require('./js/footer');
require('./js/redirect');
require('./js/suit');
require('./js/table');
require('./js/form');
require('./js/tinymce');
require('./js/registrations');
