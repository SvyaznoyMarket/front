define(
    [
        'jquery', 'underscore', 'module/config',
        'module/widget'
    ],
    function ($, _, config) {

        var $body = $('body'),

            addProductToCart = function(e) {
                e.stopPropagation();

                var $el = $(e.target),
                    data = $el.data(),
                    $widget = $el.data('widgetSelector') ? $($el.data('widgetSelector')) : null,
                    isWm = false
                ;

                console.info('addProductToCart', {'$el': $el, '$widget': $widget, 'data': data});

                try {
                    if (data.url) {
                        _.each(data.value.product, function(product) {
                            if (product.wmId) {
                                window.WikimartAffiliate.addGoodToCart(product.wmId);
                                $el.text('В корзине');
                                $el.attr('href', '/cart');
                                $el.data('url', null);
                                isWm = true;
                            }
                        });
                    }
                } catch (error) {
                    console.error(error);
                }

                if (isWm) {
                    e.preventDefault();
                }

                console.info({isWm: isWm});

                if (!isWm && data.url) {
                    $.post(data.url, data.value, function(response) {
                        if (_.isObject(response.result.widgets)) {
                            _.each(response.result.widgets, function(templateData, selector) {
                                if (!selector) {
                                    return;
                                }

                                $(selector).trigger('render', templateData);
                            });
                        }

                        $body.trigger(config.event.productAddedToCart, data.value);

                        // FIXME
                        $('.js-kit-close').trigger('click');
                    });

                    e.preventDefault();
                }
            },

            deleteProductFromCart = function(e) {
                e.stopPropagation();

                var $el = $(e.target),
                    data = $el.data(),
                    $spinnerWidget = $($el.data('spinnerSelector'))
                ;

                console.info('deleteProductFromCart', $el, data);

                if (data.url) {
                    $.post(data.url, data.value, function(response) {
                        if (response.redirect && (-1 !== response.redirect.indexOf('/'))) {
                            window.location.href = response.redirect;
                        }

                        if (_.isObject(response.result) && _.isObject(response.result.widgets)) {
                            _.each(response.result.widgets, function(templateData, selector) {
                                if (!selector) {
                                    return;
                                }

                                $(selector).trigger('render', templateData);
                            });
                        }

                        if ($el.data('parentContainerSelector')) {
                            var $parentContainer = $el.parents($el.data('parentContainerSelector'));
                            if ($parentContainer.length) {
                                $parentContainer.slideUp(300, function() {
                                    $parentContainer.remove();
                                });
                            }
                        }
                    });

                    e.preventDefault();

                    if ($spinnerWidget.length) {
                        var timer = $spinnerWidget.data('timer');

                        try {
                            clearTimeout(timer);
                        } catch (error) {
                            console.warn(error);
                        }
                    }

                    $body.trigger(config.event.productRemovedFromCart, data.value);
                }
            },

            changeProductQuantity = function(e, product, quantity, checkUrl, callback) {
                e.stopPropagation();

                var idSelector = $(e.target),
                    $el = $(idSelector),
                    dataValue = $el.data('value'),
                    $widget = $($el.data('widgetSelector')),
                    timer = parseInt($widget.data('timer')),
                    checkUrl = checkUrl || null,
                    handle = function(quantity, error) {
                        if (error || !_.isFinite(quantity) || (quantity < product.minQuantity) || (quantity > 999)) {
                            var error = error || {code: 'invalid', message: 'Неверное количество товара'};

                            console.info('changeProductQuantityData:js-buyButton', error, quantity, $el);

                            if (callback) callback(error);
                            return false;
                        }

                        if (dataValue.product[product.id]) {
                            dataValue.product[product.id].quantity = quantity;
                        }

                        if (callback) callback();

                        // FIXME
                        $('.js-productKit-reset').prop('checked', false);
                    }
                ;

                console.info('changeProductQuantity', {'$el' : $el, '$widget': $widget, 'product': product, 'quantity': quantity, 'checkUrl': checkUrl});


                if (checkUrl) {
                    $.post(checkUrl, {
                        products: [
                            { id: product.id, quantity: quantity }
                        ]
                    }).done(function(result) {
                        if (result.success) {
                            handle(quantity);
                        } else {
                            handle(product.quantity, {code: 'invalid', message: 'Невозможно установить такое количество'});
                        }
                    });
                } else {
                    if (('on' == $el.data('autoUpdate')) && _.isFinite(timer) && (timer > 0)) {
                        try {
                            clearTimeout(timer);
                        } catch (error) {
                            console.warn(error);
                        }

                        timer = setTimeout(function() { addProductToCart(e); }, 600);

                        $widget.data('timer', timer);
                    }

                    handle(quantity);
                }

                // FIXME: осторожно, гкод
                if ($el.hasClass('js-quickBuyButton')) {
                    var url = $el.attr('href').replace(/\d+$/, quantity);
                    $el.attr('href', url);
                }
            },

            incSpinnerValue = function(e) {
                e.stopPropagation();

                var $el = $(e.target),
                    $widget = $($el.data('widgetSelector')),
                    $target = $($el.data('buttonSelector')),
                    $value = $($el.data('valueSelector')),
                    targetDataValue = $target.data('value'),
                    dataValue = $value.data('value')
                ;

                console.info('incSpinnerValue', { '$el': $el, '$target': $target, '$value': $value, '$widget': $widget});

                var product = (targetDataValue && dataValue) ? targetDataValue.product[dataValue.product.id] : null;
                if (product) {
                    $target.trigger('changeProductQuantityData', [
                        dataValue.product,
                        product.quantity + 1,
                        dataValue.checkUrl,
                        function(error) {
                            $widget.trigger('renderValue', [product]);
                            if (error) {
                                // FIXME
                                $widget.find('.js-buySpinner-inc').css({opacity: 0.5});
                            }
                        }
                    ]);
                } else {
                    console.error('Товар не получен', product);
                }

                $el.blur();
            },

            decSpinnerValue = function(e) {
                e.stopPropagation();

                var $el = $(e.target),
                    $widget = $($el.data('widgetSelector')),
                    $target = $($el.data('buttonSelector')),
                    $value = $($el.data('valueSelector')),
                    targetDataValue = $target.data('value'),
                    dataValue = $value.data('value')
                ;

                console.info('incSpinnerValue', { '$el': $el, '$target': $target, '$value': $value, '$widget': $widget});

                var product = (targetDataValue && dataValue) ? targetDataValue.product[dataValue.product.id] : null;
                if (product) {
                    $target.trigger('changeProductQuantityData', [
                        dataValue.product,
                        product.quantity - 1,
                        dataValue.checkUrl,
                        function() {
                            $widget.trigger('renderValue', [product]);
                            // FIXME
                            $widget.find('.js-buySpinner-inc').css({opacity: 1});
                        }
                    ]);
                } else {
                    console.error('Товар не получен', product);
                }

                $el.blur();
            },

            changeSpinnerValue = function(e) {
                e.stopPropagation();

                var $el = $(e.target),
                    $widget = $($el.data('widgetSelector')),
                    $target = $($el.data('buttonSelector')),
                    targetDataValue = $target.data('value'),
                    dataValue = $el.data('value')
                ;

                console.info('changeSpinnerValue', { '$el': $el, '$target': $target, '$widget': $widget});

                var value = $el.val();
                var product = (targetDataValue && dataValue) ? targetDataValue.product[dataValue.product.id] : null;
                if ('' != value) {
                    if (product) {
                        $target.trigger('changeProductQuantityData', [
                            dataValue.product,
                            parseInt(value),
                            dataValue.checkUrl,
                            function() {
                                $widget.trigger('renderValue', [product]);
                                // FIXME
                                $widget.find('.js-buySpinner-inc').css({opacity: 1});
                            }
                        ]);
                    }
                }
            },

            renderSpinnerValue = function(e, product) {
                e.stopPropagation();

                var $el = $(e.target);

                console.info('renderSpinnerValue', $el, product);

                $el.find('.js-buySpinner-value').val(product.quantity);
            },

            setCredit = function(e) {
                e.stopPropagation();

                var $el = $(e.target);

                if ($el.is(':checked')) {
                    $.cookie(config.credit.cookieName, 1, {expires: 7});
                } else {
                    $.removeCookie(config.credit.cookieName);
                }

                e.preventDefault();
            },

            removeCredit = function(e) {
                e.stopPropagation();

                $.removeCookie(config.credit.cookieName);

                e.preventDefault();
            },

            initCredit = function($elements) {
                if (1 == $.cookie(config.credit.cookieName)) {
                    $elements.attr('checked', true);
                } else {
                    $elements.removeAttr('checked');
                }
            }
        ;


        // кнопка купить
        $body
            .on('click', '.js-buyButton', addProductToCart)
            .on('changeProductQuantityData', '.js-buyButton', changeProductQuantity)
            .on('changeProductQuantityData', '.js-quickBuyButton', changeProductQuantity)
            .on('click', '.js-deleteButton', deleteProductFromCart)

        // спиннер для кнопки купить
        $body
            .on('click dblclick contextmenu', '.js-buySpinner-inc', incSpinnerValue)
            .on('click dblclick contextmenu', '.js-buySpinner-dec', decSpinnerValue)
            .on('change keyup', '.js-buySpinner-value', changeSpinnerValue)
            .on('renderValue', '.js-buySpinner', renderSpinnerValue)
            .on('changeProductQuantityData', '.js-buySpinner-value', changeProductQuantity)

        // купить в кредит
        $body
            .on('change', '.js-creditButton', setCredit)
            .on('change', '.js-creditButton-remove', removeCredit)

        initCredit($('.js-creditButton'));

        return {
            initCredit: function() {
                initCredit($('.js-creditButton'));
            }
        }
    }
);