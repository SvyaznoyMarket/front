define(
    [
        'jquery', 'underscore'
    ],
    function (
        $, _
    ) {
         var
            footer = $('.footer'),
            body = $('body'),
            hasNoIosClass = body.hasClass('noIOS');

        footer.removeClass('fixed');
        hasNoIosClass && body.addClass('noIOS');

        if (($('.content').height() + footer.outerHeight() + $('.header').height() < ($(window).height()) && (body.data('module')) !== 'index')) {
            body.removeClass('noIOS');
            footer.addClass('fixed');
        }
});
