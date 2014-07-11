define(
    [
        'require', 'jquery', 'underscore', 'module/config'
    ],
    function (
        require, $, _, config
    ) {
        var $body = $('body'),

            handle = function(action, data, $el) {
                var
                    cart = $body.data('cart') || {products: []}
                ;

                // товары в корзине
                data.basketProducts = [];
                _.each(cart.products, function(product) {
                    if (!product.id) return;

                    data.basketProducts.push({
                        id: product.id,
                        name: product.name,
                        price: product.price,
                        quantity: product.quantity
                    });
                });

                window.APRT_DATA = data;
                console.info('partner', 'actionpay', window.APRT_DATA);

                require(['//rt.actionpay.ru/code/enter/']);
            }
        ;

        return {
            handle: handle
        }
    }
);