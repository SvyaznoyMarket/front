define(
    ['jquery', 'module/config'],
    function ($, config) {

        var util = {
            formatCurrency: function (price) {
                price = String(price);
                price = price.replace(',', '.');
                price = price.replace(/\s/g, '');
                price = String(Number(price).toFixed(2));
                price = price.split('.');

                if (price[0].length >= 5) {
                    price[0] = price[0].replace(/(\d)(?=(\d\d\d)+([^\d]|$))/g, '$1 ');
                }

                if (price[1] == 0) {
                    price = price.slice(0, 1);
                }

                return price.join('.');
            },

            /**
             * Логирование транзакции в Google Analytics (Classical + Universal)
             * Если в action передаётся несколько меток, то для удобства фильтрации по ним в аналитеке нужно заключать каждую метку в скобки, например: RR_покупка (marketplace)(gift)
             * {@link https://developers.google.com/analytics/devguides/collection/analyticsjs/ecommerce}
             * {@link https://developers.google.com/analytics/devguides/collection/gajs/gaTrackingEcommerce}
             *
             * @param {Object} data Пример: {transaction: {}, products: []}
             */
            trackGoogleAnalyticsTransaction: function(data) {
                try {
                    data = correctDataForAnalytics(data);
                    trackClassicAnalytics(data);
                    trackUniversalAnalytics(data);
                } catch (exception) {
                    console.error('[Google Analytics Ecommerce] %s', exception)
                }

                function correctDataForAnalytics(data) {
                    data = $.extend(true, {}, data);

                    if (!data.transaction.id) {
                        throw 'Некорректный ID транзакции';
                    }

                    if (!data.transaction.total) {
                        throw 'Некорректная сумма заказа';
                    }

                    data.transaction.id = data.transaction.id ? data.transaction.id + '' : '';
                    data.transaction.affiliation = data.transaction.affiliation ? data.transaction.affiliation + '' : '';
                    data.transaction.total = data.transaction.total ? data.transaction.total + '' : '';
                    data.transaction.tax = data.transaction.tax ? data.transaction.tax + '' : '0';
                    data.transaction.shipping = data.transaction.shipping ? data.transaction.shipping + '' : '0';
                    data.transaction.city = data.transaction.city ? data.transaction.city + '' : '';

                    data.products = $.map(data.products, function(product) {
                        if (!product.name) {
                            throw 'Некорректное название товара';
                        }

                        if (!product.price) {
                            throw 'Некорректная цена товара';
                        }

                        if (!product.quantity) {
                            throw 'Некорректное количество товара';
                        }

                        return {
                            sku: product.sku ? product.sku + '' : '',
                            name: product.name ? product.name + '' : '',
                            category: product.category ? product.category + '' : '',
                            price: product.price ? product.price + '' : '',
                            quantity: product.quantity ? product.quantity + '' : ''
                        };
                    });

                    return data;
                }

                function trackClassicAnalytics(data) {
                    if (typeof _gaq === 'object') {
                        _gaq.push([
                            '_addTrans',
                            data.transaction.id,
                            data.transaction.affiliation,
                            data.transaction.total,
                            data.transaction.tax,
                            data.transaction.shipping,
                            data.transaction.city
                        ]);

                        $.each(data.products, function(i, product) {
                            _gaq.push([
                                '_addItem',
                                data.transaction.id,
                                product.sku,
                                product.name,
                                product.category,
                                product.price,
                                product.quantity
                            ])
                        });

                        _gaq.push(['_trackTrans']);
                    } else {
                        console.warn('No Classic Google Analytics object found')
                    }
                }

                function trackUniversalAnalytics(data) {
                    if (typeof ga === 'function' && ga.getAll().length != 0) {
                        ga('require', 'ecommerce', 'ecommerce.js');

                        ga('ecommerce:addTransaction', {
                            id: data.transaction.id,
                            affiliation: data.transaction.affiliation,
                            revenue: data.transaction.total,
                            shipping: data.transaction.shipping,
                            tax: data.transaction.tax
                        });

                        $.each(data.products, function(i, product) {
                            ga('ecommerce:addItem', {
                                id: data.transaction.id,
                                name: product.name,
                                sku: product.sku,
                                category: product.category,
                                price: product.price,
                                quantity: product.quantity
                            });
                        });

                        ga('ecommerce:send');
                    } else {
                        console.warn('No Universal Google Analytics function found');
                    }
                }
            },

            trackGoogleAnalyticsEvent: function(dataGa) {
                if (dataGa[0] != 'send' || dataGa[1] != 'event') {
                    return;
                }

                console.log('ga event', dataGa);

                if (typeof dataGa[2] != 'undefined') {
                    dataGa[2] = String(dataGa[2]);
                }

                if (typeof dataGa[3] != 'undefined') {
                    dataGa[3] = String(dataGa[3]);
                }

                if (typeof dataGa[4] != 'undefined') {
                    dataGa[4] = String(dataGa[4]);
                }

                if (typeof dataGa[5] != 'undefined') {
                    dataGa[5] = Number(dataGa[5]);
                }

                if (typeof ga != 'undefined') {
                    ga.apply(ga, dataGa);
                }

                if (typeof _gaq != 'undefined') {
                    _gaq.push(['_trackEvent'].concat(dataGa.slice(2)));
                }
            },

            sendOrdersToGoogleAnalytics: function(orders) {
                if (!orders) {
                    return;
                }

                $.each(orders, function(i, order) {
                    util.trackGoogleAnalyticsTransaction({
                        transaction: {
                            id: order.numberErp,
                            affiliation: order.isPartner ? 'Партнер' : 'Enter',
                            total: order.paySum,
                            shipping: order.delivery.price,
                            city: order.region.name
                        },

                        products: $.map(order.products, function(product) {
                            return {
                                id: product.id,
                                name: product.name,
                                sku: product.article,
                                category: product.categories.length ? product.categories[0].name +  ' - ' + product.categories[product.categories.length -1].name : '',
                                price: product.price,
                                quantity: product.quantity
                            };
                        })
                    });
                });
            },

            partner: {
                flocktory: {
                    send: function(data) {
                        if (!config.partner.service.flocktory) {
                            return;
                        }

                        function send(data) {
                            window.flocktory = window.flocktory || [];
                            window.flocktory.push(data);
                        }

                        switch (data.action) {
                            case 'postcheckout':
                                var orderNumber = 0;
                                $.each(data.orders, function(key, order) {
                                    orderNumber++;

                                    send(['postcheckout', {
                                        user: {
                                            name: $.trim(order.firstName + ' ' + order.lastName),
                                            email: order.email ? order.email : order.phone + '@unknown.email', // http://flocktory.com/help
                                            sex: order.user.sex == 1 ? 'm' : (order.user.sex == 2 ? 'f' : '')
                                        },
                                        order: {
                                            id: order.numberErp,
                                            price: order.paySum,
                                            items: $.map(order.products, function(product) {
                                                return {
                                                    id: product.id,
                                                    title: product.name,
                                                    price: product.price,
                                                    image: product.images['120x120'].url,
                                                    count: product.quantity
                                                };
                                            })
                                        },
                                        spot: orderNumber > 1 ? 'no_popup' : data.spot || ''
                                    }]);
                                });
                                break;
                            default:
                                send([data.action, {item: data.item}]);
                                break;
                        }
                    }
                }
            }
        };

        return util;
    }
);