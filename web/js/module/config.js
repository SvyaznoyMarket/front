define(
    ['jquery', 'underscore'],
    function ($, _) {
        $.ajaxSetup({
            timeout: 30000,
            statusCode: {
                401: function () {
                    window.location.href = '/login?redirect_to=' + window.location.pathname;
                },
                403: function () {
                    window.location.href = '/login?redirect_to=' + window.location.pathname;
                }
            }
        });

        return _.extend({
            cookie: {
                domain: null,
                lifetime: null
            },
            credit: {
                cookieName: null
            },
            event: {
                productAddedToCart: 'cart_product_added',
                productRemovedFromCart: 'cart_product_removed'
            }
        }, $('body').data('config'));
    }
);