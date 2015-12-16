define(
    [
        'require', 'jquery', 'underscore', 'module/config', 'module/util'
    ],
    function (
        require, $, _, config, util
    ) {
        if (!config.partner.service.flocktory) {
            return;
        }

        var $body = $('body');

        $body.on(config.event.productAddedToCart + ' ' + config.event.productRemovedFromCart, function(e, data) {
            if (!data.products) {
                return false;
            }

            $.each(data.products, function(key, product) {
                var quantityDelta = (product.newQuantity || 0) - (product.previousQuantity || 0);

                if (quantityDelta > 0) {
                    util.partner.flocktory.send({
                        action: 'addToCart',
                        item: {
                            id: product.id,
                            price: product.price,
                            count: quantityDelta
                        }
                    });
                } else if (quantityDelta < 0) {
                    util.partner.flocktory.send({
                        action: 'removeFromCart',
                        item: {
                            id: product.id,
                            price: product.price,
                            count: Math.abs(quantityDelta)
                        }
                    });
                }
            });
        });

        return {
            handle: function(action, data, $el) {
                require(['//api.flocktory.com/v2/loader.js?site_id=' + data.siteId]);
            }
        }
    }
);