define(
    ['jquery', 'underscore'],
    function ($, _) {
        return _.extend({
            cookie: {
                domain: null,
                lifetime: null
            },
            user: {
                infoUrl: null
            },
            credit: {
                cookieName: null
            },
            event: {
                productAddedToCart: 'cart_product_added',
                productRemovedFromCart: 'cart_product_removed'
            },
            wikimart : null
        }, $('body').data('config'));
    }
);