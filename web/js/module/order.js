define(
    [
        'require', 'jquery', 'underscore', 'mustache', 'module/util', 'module/config', 'jquery.maskedinput',
        'module/order/user.form', 'module/order/common', 'module/order/toggle'
    ],
    function(
        require, $, _, mustache, util, config
    ) {
        var
            Storage = {
                cache: {},

                get: function( selector, criteria ) {
                    var
                        key   = selector + '-' + criteria,
                        value = Storage.cache[key] ? Storage.cache[key] : $.parseJSON($(selector).html())
                    ;

                    if ( !Storage.cache[key] ) {
                        Storage.cache[key] = value;
                    }

                    return value;
                },

                set: function( selector, criteria, value ) {
                    var
                        key = selector + '-' + criteria
                    ;

                    Storage.cache[key] = value;
                }
            },

            addressMap = null,
            pointMap = null,

            $body                  = $('body'),
            $deliveryForm          = $('.js-order-delivery-form'),
            $pointMap              = $('#pointYandexMap'),
            $addressMap            = $('#addressYandexMap'),
            $mapContainer          = $('#yandexMap-container'),
            $balloonTemplate       = $('#tpl-order-delivery-marker-balloon'),
            $pointPopupTemplate    = $('#tpl-order-delivery-point-popup'),
            $calendarTemplate      = $('#tpl-order-delivery-calendar'),
            $addressPopupTemplate  = $('#tpl-order-delivery-address-popup'),
            $modalWindowTemplate   = $('#tpl-modalWindow'),

            $discountPopupTemplate = $('#tpl-order-delivery-discount-popup'),

            initPointMap = function($container, options) {
                var defer = $.Deferred();

                if (pointMap) {
                    defer.resolve($container);
                }

                require(['yandexmaps'], function(ymaps) {
                    ymaps.ready(function() {
                        try {
                            pointMap = new ymaps.Map(
                                $container.attr('id'),
                                {
                                    center: [options.center.lat, options.center.lng],
                                    zoom: options.zoom
                                },
                                {
                                    autoFitToViewport: 'always'
                                }
                            );

                            pointMap.geoObjects.events.remove('click'); // TODO: можно убрать
                            pointMap.geoObjects.events.add('click', function (e) {
                                var placemark = e.get('target');

                                pointMap.balloon.open(e.get('coords'), mustache.render($balloonTemplate.html(), placemark.properties.get('point')));
                            });

                            defer.resolve($container);
                        } catch (error) {
                            console.error(error);

                            defer.reject(error);
                        }
                    });
                });

                return defer;
            },

            initAddressMap = function($container, options) {
                var defer = $.Deferred();

                if (addressMap) {
                    defer.resolve($container);
                }

                require(['yandexmaps'], function(ymaps) {
                    ymaps.ready(function() {
                        try {
                            addressMap = new ymaps.Map(
                                $container.attr('id'),
                                {
                                    center: [options.center.lat, options.center.lng],
                                    zoom: options.zoom
                                },
                                {
                                    autoFitToViewport: 'always'
                                }
                            );
                            defer.resolve($container);
                        } catch (error) {
                            console.error(error);

                            defer.reject(error);
                        }
                    });
                });

                return defer;
            },

            initSmartAddress = function($context) {
                var
                    $form = $context.find('.js-smartAddress-form'),
                    $mapContainer = $($form.data('mapContainerSelector'))
                ;

                console.info('$mapContainer', $mapContainer);

                require(['jquery.kladr'], function() {
                    console.info('config.kladr', config.kladr);

                    $.kladr.setDefault({
                        token: config.kladr.token,
                        key: config.kladr.key,
                        type: $.kladr.type.street,
                        parentType: $.kladr.type.city,
                        parentId: config.kladr.city.id,

                        parentInput: '.js-smartAddress-form',
                        //verify: true,
                        select: function (obj) {
                            //setLabel($(this), obj.type);
                            //$tooltip.hide();
                        },
                        check: function (obj) {
                            var $input = $(this);

                            if (obj) {
                                //setLabel($input, obj.type);
                                //$tooltip.hide();
                            }
                            else {
                                //showError($input, 'Введено неверно');
                            }
                        },
                        checkBefore: function () {
                            var $input = $(this);

                            if (!$.trim($input.val())) {
                                //$tooltip.hide();
                                return false;
                            }
                        },
                        change: function (obj) {
                            console.info(obj);

                            updateAddressMap($mapContainer, $form);
                        }
                    });

                    $form.find('.js-smartAddress-input').each(function(k, el) {
                        var $el = $(el);

                        $el.kladr('type', $el.data('kladrType'));
                    });
                });
            },

            changeSplit = function( data ) {
                $.ajax({
                    url: $deliveryForm.attr('action'),
                    data: data,
                    type: 'post',
                    timeout: 30000
                }).done(function(response) {
                    $($deliveryForm.data('containerSelector')).html(response)
                }).always(function() {
                    console.info('unblock screen');
                });
            },

            updatePointTab = function(e, toggle) {
                var
                    $el = $(this),
                    index = parseInt($el.data('index')),
                    $selected,
                    $container,
                    $filterForm = $($el.data('filterFormSelector'))
                ;

                if (false !== toggle) {
                    index = (0 == index) ? 1 : 0;
                }

                $selected = $el.find('[data-index="' + index + '"]');
                $container = $($selected.data('containerSelector'));

                $el.data('index', index);
                $el.find('[data-index]').each(function(i, el) {
                    var $el = $(el);

                    $el.hide();
                    $($el.data('containerSelector')).hide();
                });

                filterPoints($el.data('storageSelector'), $filterForm);

                $selected.show();
                $container
                    .show()
                    .trigger('update', [
                        Storage.get($el.data('storageSelector'), 'filtered')
                    ])
                ;
            },

            updatePointMap = function(e, data) {
                var
                    $container = $(this),
                    options = $container.data('mapOption'),
                    ready = function() {
                        var placemark;

                        console.info('update point map ...');

                        if (!$container.find('#' + $pointMap.attr('id')).length) {
                            $container.append($pointMap);
                        }

                        pointMap.setCenter([options.center.lat, options.center.lng], options.zoom);
                        pointMap.balloon.close();
                        pointMap.geoObjects.removeAll();
                        pointMap.container.fitToViewport();

                        _.each(data.points, function(point) {
                            try {
                                placemark = new ymaps.Placemark(
                                    [point.lat, point.lng],
                                    {
                                        point: point,
                                        hintContent: point.name
                                    },
                                    {
                                        iconLayout: 'default#image',
                                        iconImageHref: '/img/markers/' + point.icon,
                                        iconImageSize: [28, 39],
                                        iconImageOffset: [-14, -39],
                                        //visible: visibility,
                                        zIndex: ('shops' == point.group.token) ? 1000 : 0
                                    }
                                );
                                pointMap.geoObjects.add(placemark);
                            } catch (e) {
                                console.error(e, point);
                            }
                        });
                    }
                ;

                if (pointMap) {
                    ready();
                } else {
                    initPointMap($pointMap, options).done(ready);
                }
            },

            updatePointList = function(e, data) {
                var
                    $container = $(this),
                    partial = $pointPopupTemplate.data('partial')['page/order/delivery/point-list']
                ;

                console.info('update point list ...');

                $container.html(mustache.render(partial, data));
            },

            updatePointFilter = function(e) {
                var
                    $el = $(this),
                    $form = $($el.data('formSelector')),
                    $tab = $($form.data('tabSelector'))
                ;

                console.info('$tab', $tab);

                $tab.trigger('update', [false]); // не переключать, просто обновить
            },

            // получаем точки доставки и фильтруем их по выбранным параметрам фильтрации getFilterParams()
            filterPoints = function(selector, $form) {
                var
                    data      = Storage.get(selector, 'base'),
                    params    = getFilterParams($form),
                    newData = {},
                    key,
                    pointAdd
                ;

                _.extend(newData, data);

                newData.points = data.points.filter(function(points) {
                    for ( key in points ) {
                        if ( params[key] && params[key].length && points[key] && points[key].hasOwnProperty('value') ) {

                            pointAdd = params[key].indexOf(points[key].value.toString());

                            if ( pointAdd === -1 ) {
                                return false;
                            }
                        }
                    }
                    return true;
                });
                Storage.set(selector, 'filtered', newData);
            },

            // формируем массив параметров фильтрации точек доставки
            getFilterParams = function($form) {
                var
                    $input = $form.find('input'),
                    params = {}
                ;

                $input.each(function(key) {
                    var
                        $this = $(this),
                        data  = $this.data('value');

                    key = data.name;

                    if ( typeof params[key] === 'undefined' ) {
                        params[key] = [];
                    }

                    if ( $this.prop('checked') == true ) {
                        params[key].push(data.value.toString());
                    }
                });

                return params;
            },

            showPointPopup = function(e) {
                var
                    $el           = $(this),
                    $modalWindow  = $($modalWindowTemplate.html()).appendTo($body),
                    data          = Storage.get($el.data('storageSelector'), 'filtered'),
                    modalTitle    = $el.data('modal-title'),
                    modalPosition = $el.data('modal-position')
                ;

                e.stopPropagation();

                $modalWindow.find('.js-modal-title').text(modalTitle);
                $modalWindow.addClass(modalPosition);

                $modalWindow.lightbox_me({
                    onLoad: function() {
                        $modalWindow.find('.js-modal-content').append(mustache.render($pointPopupTemplate.html(), data, $pointPopupTemplate.data('partial')));
                    },
                    beforeClose: function() {
                        $mapContainer.append($pointMap);
                    }
                });

                e.preventDefault();

                setTimeout(function() {
                    require(['yandexmaps'], function() {});
                }, 1000)
            },

            showAddressPopup = function( e ) {
                var
                    $el           = $(this),
                    $modalWindow  = $($modalWindowTemplate.html()).appendTo($body),
                    modalTitle    = $el.data('modal-title'),
                    modalPosition = $el.data('modal-position'),
                    data          = $.parseJSON($($el.data('dataSelector')).html())
                ;

                require(['jquery.kladr'], function() {});
                require(['yandexmaps'], function() {});

                $modalWindow.find('.js-modal-title').text(modalTitle);
                $modalWindow.addClass(modalPosition);

                $modalWindow.lightbox_me({
                    onLoad: function() {
                        $modalWindow.find('.js-modal-content').append(mustache.render($addressPopupTemplate.html(), data));

                        initSmartAddress($modalWindow);

                        require(['yandexmaps'], function() {
                            var $mapContainer = $($el.data('mapContainerSelector'));

                            console.info('$mapContainer', $mapContainer, $mapContainer.data());
                            updateAddressMap($mapContainer);
                        });
                    },
                    beforeClose: function() {
                        $mapContainer.append($addressMap);
                    },
                    modalCSS: {top: '60px'}
                });
            },

            updateAddressMap = function($container) {
                var zoom = 4;

                var address = $.kladr.getAddress('.js-smartAddress-form', function (objs) {
                    var result = config.kladr.city.name + '';

                    console.info('objs', objs);

                    if ($.type(objs.street) === 'object') {
                        $.each(objs, function (i, obj) {
                            var name = '',
                                type = ''
                            ;

                            if ($.type(obj) === 'object') {
                                name = obj.name;
                                type = ' ' + obj.type;

                                switch (obj.contentType) {
                                    case $.kladr.type.city:
                                        zoom = 10;
                                        break;
                                    case $.kladr.type.street:
                                        zoom = 13;
                                        break;
                                    case $.kladr.type.building:
                                        zoom = 16;
                                        break;
                                }
                            }
                            else {
                                name = obj;
                            }

                            console.info('obj', obj, name);

                            if (result) result += ', ';
                            result += type + '' + name;
                        });
                    } else {
                        result = '';
                    }

                    return result;
                });

                var
                    options = $container.data('mapOption'),
                    ready = function() {
                        var placemark;

                        console.info('update address map ...');

                        if (!$container.find('#' + $addressMap.attr('id')).length) {
                            $container.append($addressMap);
                        }

                        //addressMap.setCenter([options.center.lat, options.center.lng], options.zoom);
                        addressMap.balloon.close();
                        addressMap.geoObjects.removeAll();
                        addressMap.container.fitToViewport();
                    };

                if (addressMap) {
                    ready();
                } else {
                    initAddressMap($addressMap, options).done(ready);
                }

                console.info('address', address);
                if (address) {
                    require(['yandexmaps'], function(ymaps) {
                        if (!addressMap) return;

                        var geocode = ymaps.geocode(address);

                        geocode.then(function (res) {
                            addressMap.balloon.close();
                            addressMap.geoObjects.removeAll();

                            var position = res.geoObjects.get(0).geometry.getCoordinates(),
                                placemark = new ymaps.Placemark(position, {}, {});

                            addressMap.geoObjects.add(placemark);
                            addressMap.setCenter(position, zoom);
                        });
                    });
                }
            },

            // показать календарь
            showCalendar = function( e ) {
                e.stopPropagation();

                var
                    $el           = $(this),
                    data          = $.parseJSON($($el.data('dataSelector')).html()),
                    $modalWindow  = $($modalWindowTemplate.html()).appendTo($body),
                    modalTitle    = $el.data('modal-title'),
                    modalPosition = $el.data('modal-position');

                $modalWindow.find('.js-modal-title').text(modalTitle);
                $modalWindow.addClass(modalPosition);

                $modalWindow.lightbox_me({
                    fullScreen: true,
                    modal: false,
                    onLoad: function() {
                        $modalWindow.find('.js-modal-content').append(mustache.render($calendarTemplate.html(), data));
                    }
                });
            },

            showDiscountPopup = function( e ) {
                e.stopPropagation();
                console.info('showDiscountPopup');

                var
                    $el           = $(this),
                    data          = {},
                    $modalWindow  = $($modalWindowTemplate.html()).appendTo($body),
                    modalTitle    = $el.data('modal-title'),
                    modalPosition = $el.data('modal-position'),
                    $discountContainer
                ;

                $modalWindow.find('.js-modal-title').text(modalTitle);
                $modalWindow.addClass(modalPosition);

                $modalWindow.lightbox_me({
                    onLoad: function() {
                        $modalWindow.find('.js-modal-content').append(mustache.render($discountPopupTemplate.html(), data));

                        $discountContainer = $('.js-user-discount-container');

                        if ($discountContainer.length && $discountContainer.data('url')) {
                            $.get($discountContainer.data('url')).done(function(response) {
                                $discountContainer.html(response.content);
                            })
                        }
                    },
                    centered: false
                });

                e.preventDefault();
            },

            applyDiscount = function(e) {
                $.ajax({
                   'url': $deliveryForm.attr('action'),
                   'data': $form.serializeArray(),
                    type: 'post',
                    timeout: 30000
                }).done(function(response) {

                }).always(function() {
                    console.info('unblock screen');
                });
            }
        ;

        $body.on('click', '.js-order-delivery-form-control', function(e) {
            var
                $el = $(e.currentTarget),
                data = $el.data('value')
            ;

            e.stopPropagation();
            e.preventDefault();

            if (data) {
                changeSplit(data);
            }
        });
        $body.on('click', '.js-order-delivery-pointPopup-link', showPointPopup);
        $body.on('click', '.js-order-delivery-addressPopup-link', showAddressPopup);
        $body.on('click', '.js-order-delivery-discountPopup-link', showDiscountPopup);
        $body.on('click', '.js-order-delivery-point-tab-link', updatePointTab);
        $body.on('update', '.js-order-delivery-point-tab-link', updatePointTab);
        $body.on('click', '.js-order-delivery-celendar-link', showCalendar);
        $body.on('update', '.js-order-delivery-map-container', updatePointMap);
        $body.on('update', '.js-order-delivery-point-container', updatePointList);
        $body.on('change', '.js-order-delivery-point-filter', updatePointFilter);
        $body.on('submit', '.js-smartAddress-form', function(e) {
            var
                $form = $(this),
                data = $form.serializeArray()
            ;

            e.stopPropagation();
            e.preventDefault();

            changeSplit(data);
        });
        $body.on('submit', '.js-certificate-check', applyDiscount);
    }
);
