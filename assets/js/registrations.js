const $ = require('jquery');

function show(which) {
    $('form[name$=_registration]').hide();
    $('form[name=' + which + '_registration]').show();
    $('#registration_choice').val(which);
}
$(document).ready(function() {
    var choices = $('#registration_choice');
    if (choices.length == 0)
        return;

    choices.change(function() {
        show($(this).val());
    });
    var names = $('input[id$=_registration_leftPartner],#complete_registration_rightPartner');
    names.change(function() {
        names.val($(this).val());
    });

    if ($('#registration_choice').find('option[value=complete]').length > 0)
        show('complete');
    else
        show('full');
});
