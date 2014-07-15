define(
    [
        'require', 'jquery', 'underscore', 'module/config'
    ],
    function (
        require, $, _, config
    ) {
        var $body = $('body'),

            handle = function(action, data, $el) {
                window.sonar_product = window.sonar_product || {};

                if (data.product) {
                    window.sonar_product = data.product;
                }

                if (data.category) {
                    window.sonar_product.category = data.category;
                }

                if (data.cartProduct) {
                    window.sonar_basket = { products: cartProduct };
                }

                //require(['https://eu-sonar.sociomantic.com/js/2010-07-01/adpan/enter-ru.js']);
            }
        ;

        return {
            handle: handle
        }
    }
);