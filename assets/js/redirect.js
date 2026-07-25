const $ = require('jquery');

$('.select-redirect').on('change', function() {
    const url = $(this).val();
    if (url)
        window.location.href = url;
});
