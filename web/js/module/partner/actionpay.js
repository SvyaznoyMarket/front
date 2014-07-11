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

                if (_.isEmpty(data)) {
                    data = {};
                }

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
                console.info('partner', 'actionpay', 'APRT_DATA', window.APRT_DATA);

                require(['//rt.actionpay.ru/code/enter/']);
            }
        ;

        $body.on(config.event.productAddedToCart + ' ' + config.event.productRemovedFromCart, function(e, data) {
            if (('undefined' == typeof(window.APRT_SEND)) || !data.product) {
                return false;
            }

            var sendData = {};

            if (config.event.productAddedToCart == e.type) {
                sendData.pageType = 8;
            } else if (config.event.productRemovedFromCart == e.type) {
                sendData.pageType = 9;
            }

            sendData.currentProduct = {
                id: data.product.id,
                name: data.product.name,
                price: data.product.price,
                quantity: data.product.quantity
            };

            console.info('partner', 'actionpay', 'APRT_SEND', sendData);

            window.APRT_SEND(sendData);
        });

        return {
            handle: handle
        }
    }
);