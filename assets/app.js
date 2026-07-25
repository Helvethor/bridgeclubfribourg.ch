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

// Bootstrap 5 (includes Popper automatically)
require('bootstrap');

// DataTables with Bootstrap 5 styling
require('datatables.net/js/dataTables.js');
require('datatables.net-bs5/js/dataTables.bootstrap5.js');

// Custom application JavaScript
require('./js/event');
require('./js/footer');
require('./js/redirect');
require('./js/suit');
require('./js/table');
require('./js/form');
require('./js/tinymce');
require('./js/registrations');
