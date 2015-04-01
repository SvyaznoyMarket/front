define(
    [
        'require', 'jquery', 'underscore'
    ],
    function (
        require, $, _
    ) {
        var $body = $('body'),

            handle = function(dataGa, $el, e) {
                if (!_.isObject(dataGa)) {
                    dataGa = JSON.parse(dataGa);
                }

                if (('undefined' != typeof ga) && _.isObject(dataGa)) {

                    _.each(dataGa, function(data, handlerName) {
                        console.info('ga', handlerName, data);

                        if (!handlers[handlerName]) {
                            handlerName = 'default';
                        }

                        handlers[handlerName](data, $el, e);
                    });
                }
            },

            handlers = {
                'default': function(dataGa, $el, e) {
                    ga.apply(ga, dataGa);
                },

                'm_add_to_basket': function(dataGa, $el, e) {
                    var dataValue = $el.data('value');

                    _.each(dataGa, function(v, k) {
                        if ('{product.sum}' == v) {
                            if (dataValue && dataValue.product) {
                                dataGa[k] = dataValue.product.price * dataValue.product.quantity;
                            }
                        }
                    });

                    ga.apply(ga, dataGa);
                }
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