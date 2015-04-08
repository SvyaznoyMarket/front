define(
    [
        'require', 'jquery', 'underscore', 'module/util'
    ],
    function (
        require, $, _, util
    ) {
        var
            $body = $('body'),

            handle = function(dataGa, $el, e) {

                if (_.isObject(dataGa)) {
                    console.info('dataGa', dataGa);
                    _.each(dataGa, function(dataGaItem) {
                        _.each(dataGaItem, function(v, k) {
                            if ('{product.sum}' == v) {
                                var dataValue = $el.data('value');
                                if (dataValue && dataValue.product) {
                                    var productId = getProductIdByArticle(dataValue.product, dataGaItem[4]);
                                    dataGaItem[k] = dataValue.product[productId].price * dataValue.product[productId].quantity;
                                }
                            }
                        });

                        util.trackGoogleAnalyticsEvent(dataGaItem);
                    });
                }
            },

            getProductIdByArticle = function(products, article) {
                var productId;
                $.each(products, function(k, v) {
                    if (v.article == article) {
                        productId = v.id;
                        return false;
                    }
                });

                return productId;
            }
        ;

        $body.on('click', '.js-ga-click', function(e) {
            var $el = $(this),
                dataGa = $el.data('gaClick')
            ;

            handle(dataGa, $el, e);
        });

        $body.on('submit', '.js-ga-submit', function(e) {
            var $el = $(this),
                dataGa = $el.data('gaSubmit')
            ;

            handle(dataGa, $el, e);
        });

        return {
            handle: handle
        };
    }
);