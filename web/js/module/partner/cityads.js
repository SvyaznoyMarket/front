define(
    [
        'require', 'jquery', 'underscore', 'module/config'
    ],
    function (
        require, $, _, config
    ) {
        var $body = $('body'),

            handle = function(action, data, $el) {
                if ('cart' == action) {
                    window.xcnt_basket_products = data.productId;
                    window.xcnt_basket_quantity = data.productQuantity;

                    console.info('partner', 'cityads', action, data.productId, data.productQuantity);
                } else if ('product.card' == action) {
                    window.xcnt_product_id = data.product ? data.product.id : null;

                    console.info('partner', 'cityads', action, data.product.id);
                }

                require(['//x.cnt.my/async/track/?r=' + Math.random()]);
            }
        ;

        return {
            handle: handle
        }
    }
);