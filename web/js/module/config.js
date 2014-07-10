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
                userLoaded: 'user_loaded',
                cartLoaded: 'cart_loaded'
            }
        }, $('body').data('config'));
    }
);