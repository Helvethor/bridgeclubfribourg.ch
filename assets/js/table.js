const $ = require('jquery');

$(document).ready(function() {
	$('.table-sortable').dataTable({
        paging: false,
        searching: false,
        info: false,
        autoWidth: false,
        order: [],
    });
});
