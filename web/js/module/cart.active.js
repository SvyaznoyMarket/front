/**
 * Created by alexandr.anpilogov on 15.03.16.
 */


define(
    [
        'jquery', 'mustache'
    ],
    function ( $, Mustache ) {
        if ( $(window).width() <= 768 || window.location.pathname === '/cart' ) return;

        var $body    = $('body'),

            isToggle = function ( e ) {
                var $el       = $(e.target),
                    modalCart = $('#tpl-modalCart');

                e.preventDefault();

                $body.toggleClass('cart-open');

                if ( $body.hasClass('cart-open') ) {
                    $body.append($(Mustache.render(modalCart.html())));
                    $('.js-cart').addClass('is-popup');
                } else {
                    $('.js-cart').remove();
                }

            },

            isRemove = function () {
                $body.removeClass('cart-open');

                $('.js-cart').remove();
            };

        $body.on('click', '.js-cartToggle', isToggle);
        $body.on('click', '.js-fader', isRemove);
    }
);