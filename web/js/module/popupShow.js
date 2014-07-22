define(
    ['jquery', 'jquery.popup'],
    function ($) {
        var $body = $('body'),

        showPopupDef = function (e) {
            e.stopPropagation();

            console.log('showPopupHint');

            $('.js-popup-middle').enterPopup();

            e.preventDefault();
        };

        $body.on('click', '.js-popup-middle-link', showPopupDef);
    }
);