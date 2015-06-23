define(
    [
        'require', 'jquery', 'underscore', 'mustache', 'module/util', 'module/config', 'module/form-validator', 'jquery.ui', 'jquery.maskedinput',
        'module/order/user.form', 'module/order/common', 'module/toggleLink'
    ],
    function(
        require, $, _, mustache, util, config, formValidator
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
                },

                clear: function() {
                    Storage.cache = {};
                }
            },

            addressMap = null,
            pointMap = null,
            yandexmaps = null,

            $body                       = $('body'),
            $deliveryForm               = $('.js-order-delivery-form'),
            $pointMap                   = $('#pointYandexMap'),
            $addressMap                 = $('#addressYandexMap'),
            $mapContainer               = $('#yandexMap-container'),
            $balloonTemplate            = $('#tpl-order-delivery-marker-balloon'),
            $pointPopupTemplate         = $('#tpl-order-delivery-point-popup'),
            $calendarTemplate           = $('#tpl-order-delivery-calendar'),
            $addressPopupTemplate       = $('#tpl-order-delivery-address-popup'),
            $pointSuggestTemplate       = $('#tpl-order-delivery-point-suggest'),
            $discountPopupTemplate      = $('#tpl-order-delivery-discount-popup'),
            $modalWindowTemplate        = $('#tpl-modalWindow'),
            $discountScroll             = $('[data-scroll]'),

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
                                    zoom: options.zoom,
                                    controls: ['zoomControl']
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
                                    zoom: options.zoom,
                                    controls: ['zoomControl']
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

            initGeocode = function() {
                var defer = $.Deferred();

                if (null !== yandexmaps) {
                    defer.resolve(yandexmaps);
                }

                require(['yandexmaps'], function(ymaps) {
                    ymaps.ready(function() {
                        yandexmaps = ymaps;
                        defer.resolve(yandexmaps);
                    });
                });

                return defer;
            },

            initSmartAddress = function($context) {
                var
                    $form = $context.find('.js-smartAddress-form'),
                    $kladrIdInput = $($form.data('kladrIdSelector')),
                    $mapContainer = $($form.data('mapContainerSelector'))
                ;

                require(['jquery.kladr'], function() {
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
                        check: function (obj) {},
                        checkBefore: function () {
                            var $input = $(this);

                            if (!$.trim($input.val())) {
                                //$tooltip.hide();
                                return false;
                            }
                        },
                        change: function (obj) {
                            var
                                text,
                                zoom = 10,
                                address = $.kladr.getAddress('.js-smartAddress-form', function (objs) {
                                    var result = config.kladr.city.name + '';

                                    if ($kladrIdInput.length) {
                                        if ($.type(objs.building) === 'object') {
                                            $kladrIdInput.val(objs.building.id);
                                        } else if ($.type(objs.street) === 'object') {
                                            $kladrIdInput.val(objs.street.id);
                                        }
                                    }

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

                                            if (result) result += ', ';
                                            result += type + '' + name;
                                        });
                                    } else {
                                        result = '';
                                    }

                                    return result;
                                })
                            ;

                            updateAddressMap($mapContainer, address, zoom);

                            if (obj) {
                                text = (obj.type.length > 8) ? obj.typeShort : obj.type;
                                text = text.charAt(0).toUpperCase() + text.substr(1).toLowerCase();

                                if ('street' === obj.contentType) {
                                    $form.find('[data-field="streetType"]').val(obj.typeShort);
                                }

                                $(this).parent().find('label').text(text);

                            } else {
                                //showError($input, 'Введено неверно');
                            }
                        }
                    });

                    $form.find('.js-smartAddress-input').each(function(k, el) {
                        var $el = $(el);

                        $el.kladr('type', $el.data('kladrType'));
                    });
                });
            },

            changeSplit = function( data ) {
                var
                    $content    = $('.js-order-content'),
                    loaderClass = 'm-loader';

                $content.addClass(loaderClass);

                $.ajax({
                    url: $deliveryForm.attr('action'),
                    data: data,
                    type: 'post',
                    timeout: 40000
                }).done(function(response) {
                    $($deliveryForm.data('containerSelector')).html(response);
                }).always(function() {
                    console.info('unblock screen');
                    $content.removeClass(loaderClass);
                }).error(function(xhr, textStatus, error) {
                    var
                        response,
                        redirect
                    ;

                    if (xhr && (302 === xhr.status)) {
                        response = $.parseJSON(xhr.responseText) || {};
                        redirect = response.redirect || '/cart';

                        window.location.href = redirect;
                    }
                });

                Storage.clear();

                $body.trigger('beforeSplit');
            },

            updatePointTab = function(e, toggle) {
                var
                    $el = $(this),
                    index = parseInt($el.data('index')),
                    $selected,
                    $container,
                    $parentContainer,
                    $filterForm = $($el.data('filterFormSelector'))
                ;

                e.stopPropagation();

                if (false !== toggle) {
                    index = (0 == index) ? 1 : 0;
                }

                $selected = $el.find('[data-index="' + index + '"]');
                $container = $($selected.data('containerSelector'));
                $parentContainer = $($container.data('parentContainerSelector'));

                $el.data('index', index);
                $el.find('[data-index]').each(function(i, el) {
                    $(el).hide();
                });

                filterPoints($el.data('storageSelector'), $filterForm);

                $selected.show();
                $parentContainer.toggleClass($parentContainer.data('toggleClass'));
                $container.trigger('update', [Storage.get($el.data('storageSelector'), 'filtered')]);
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

                console.info('update point list ...', data);

                $container.html(mustache.render(partial, data));
                $container.find('.content-scroll').scrollTop();
            },

            updatePointFilter = function(e) {
                var
                    $el = $(this),
                    $form = $($el.data('formSelector')),
                    $tab = $($form.data('tabSelector'))
                ;

                $tab.trigger('update', [false]); // не переключать, просто обновить
            },

            // получаем точки доставки и фильтруем их по выбранным параметрам фильтрации getFilterParams()
            filterPoints = function(selector, $form) {
                var
                    center = $($form.data('suggestInputSelector')).data('center') || null,
                    data      = Storage.get(selector, 'base'),
                    params    = getFilterParams($form),
                    newData = {},
                    key,
                    latDiff,
                    lngDiff
                ;

                _.extend(newData, data);

                newData.points = data.points.filter(function(point) {
                    for (key in params) {
                        if (
                            //params[key].length
                            (-1 === _.indexOf(params[key], point[key].value))
                        ) {
                            console.info('pass', key, params[key], point[key].value);

                            return false;
                        }
                    }

                    return true;
                });

                // проверка на строку поиска
                if (center) {
                    console.info(center);
                    try {
                        _.each(newData.points, function(point, i) {
                            latDiff = Math.abs(point.lat - center[0]);
                            lngDiff = Math.abs(point.lng - center[1]);
                            point.distance = Math.sqrt(latDiff * latDiff + lngDiff * lngDiff);

                            if (point.distance > 0.2) {
                                // TODO: удалить
                            }

                            //console.info(point.name + ' ' + point.distance);
                        });

                        newData.points.sort(function(a, b) {
                            return a.distance - b.distance;
                        });
                    } catch (error) {
                        console.error(error);
                    }
                }

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
                        data  = $this.data('value')
                    ;

                    key = data.name;

                    if (true == $this.prop('checked')) {
                        if (typeof params[key] === 'undefined') {
                            params[key] = [];
                        }

                        params[key].push(data.value);
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
                    modalPosition = $el.data('modal-position'),
                    beforeSplit       = function() {
                        $modalWindow.trigger('close');
                    }
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
                        $body.off('beforeSplit', beforeSplit);
                    }
                });

                $body.on('beforeSplit', beforeSplit);

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
                    data          = $.parseJSON($($el.data('dataSelector')).html()),
                    beforeSplit   = function() {
                        $modalWindow.trigger('close');
                    }
                ;

                e.stopPropagation();

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

                            updateAddressMap($mapContainer);

                        });

                    },
                    beforeClose: function() {
                        $mapContainer.append($addressMap);
                        $body.off('beforeSplit', beforeSplit);
                    },
                    modalCSS: {top: '60px'}
                });

                $body.on('beforeSplit', beforeSplit);
            },

            updateAddressMap = function($container, address, zoom) {
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
            showCalendarPopup = function( e ) {
                var
                    $el           = $(this),
                    data          = $.parseJSON($($el.data('dataSelector')).html()),
                    $modalWindow  = $($modalWindowTemplate.html()).appendTo($body),
                    modalTitle    = $el.data('modal-title'),
                    modalPosition = $el.data('modal-position'),
                    beforeSplit       = function() {
                        setTimeout(function() { $modalWindow.trigger('close') }, 400);
                    }
                ;

                e.stopPropagation();

                $modalWindow.find('.js-modal-title').text(modalTitle);
                $modalWindow.addClass(modalPosition);

                $modalWindow.lightbox_me({
                    fullScreen: true,
                    modal: false,
                    onLoad: function() {
                        $modalWindow.find('.js-modal-content').append(mustache.render($calendarTemplate.html(), data));
                    },
                    beforeClose: function() {
                        $body.off('beforeSplit', beforeSplit);
                    }
                });

                $body.on('beforeSplit', beforeSplit);
            },

            showDiscountPopup = function( e ) {
                var
                    $el           = $(this),
                    data          = $.parseJSON($($el.data('storageSelector')).html()),
                    $modalWindow  = $($modalWindowTemplate.html()).appendTo($body),
                    modalTitle    = $el.data('modal-title'),
                    modalPosition = $el.data('modal-position'),
                    $discountContainer,
                    beforeSplit       = function() {
                        $modalWindow.trigger('close');
                    }
                ;

                e.stopPropagation();

                $modalWindow.find('.js-modal-title').text(modalTitle);
                $modalWindow.addClass(modalPosition);

                $modalWindow.lightbox_me({
                    onLoad: function() {
                        $modalWindow.find('.js-modal-content').append(mustache.render($discountPopupTemplate.html(), data));
                        $discountContainer = $('.js-user-discount-container');

                        if ($discountContainer.length && $discountContainer.data('url')) {
                            $.get($discountContainer.data('url')).done(function(response) {
                                $discountContainer.html(response.content);

                                var $form = $modalWindow.find('.js-discount-form');

                                if ($form.length) {
                                    $discountContainer.on('click', '.js-user-discount', function(e) {
                                        var
                                            $el = $(this),
                                            value = $el.data('value')
                                            $field = $form.find('[data-field="number"]')
                                        ;

                                        e.preventDefault();
                                        e.stopPropagation();

                                        $field.val(value);
                                    });
                                }
                            })
                        }
                    },
                    beforeClose: function() {
                        $body.off('beforeSplit', beforeSplit);
                    },
                    centered: false
                });



                $body.on('beforeSplit', beforeSplit);

                e.preventDefault();
            },

            searchPoint = function(e) {
                var
                    $input = $(this),
                    text = $input.val(),
                    suggestContainerSelector = $input.data('suggestContainerSelector'),
                    $suggestContainer = $(suggestContainerSelector),
                    data = {items: []},
                    geocodeParams = {}
                ;

                e.stopPropagation();

                $input.data('center', null);

                initGeocode().done(function(ymaps) {
                    if ((text.length > 1)) {
                        try {
                            text = config.kladr.city.name + ', ' + text;
                        } catch (error) {
                            console.error(error);
                        }

                        if (pointMap) {
                            geocodeParams = { boundedBy: pointMap.geoObjects.getBounds(), strictBounds: true }
                        }

                        ymaps.geocode(text, geocodeParams).then(
                            function(res){
                                res.geoObjects.each(function(obj){
                                    data.items.push({
                                        name: obj.properties.get('name'),
                                        dataCenterValue: JSON.stringify(obj.geometry.getBounds()[0]).replace(/\"/g,'&quot;'),
                                        containerSelector: suggestContainerSelector
                                    });
                                });

                                $suggestContainer.html(mustache.render($pointSuggestTemplate.html(), data));
                                $suggestContainer.show();
                            },
                            function(error){
                                console.warn('Geocode error', error)
                            }
                        );
                    }
                });
            },

            applyPointSuggest = function(e) {
                var
                    $el = $(this),
                    center = $el.data('center'),
                    $container = $($el.data('containerSelector')),
                    $input = $($container.data('inputSelector'))
                ;

                e.stopPropagation();
                e.preventDefault();

                console.info('center', center);

                try {
                    if (center) {
                        if ($input.length) {
                            $input.val($el.text());
                            $input.data('center', center);
                        }

                        if (pointMap && $pointMap.is(':visible')) {
                            pointMap.setCenter(center, 14);
                        } else {
                            $('.js-order-delivery-point-tab-link').trigger('update', [false])
                        }
                    }
                } catch (error) {
                    console.error(error);
                }

                $container.hide();
            },

            locatePoint = function(e) {
                var
                    $el = $(this),
                    center,
                    $input = $($el.data('suggestInputSelector'))
                ;

                e.stopPropagation();
                e.preventDefault();

                console.info('$input', $input);

                if (navigator.geolocation) {
                    navigator.geolocation.getCurrentPosition(function(position) {
                        center = [position.coords.latitude, position.coords.longitude];
                        console.info(center);

                        try {
                            if ($input.length) {
                                $input.val('');
                                $input.data('center', center);
                            }

                            if (pointMap && $pointMap.is(':visible')) {
                                pointMap.setCenter(center, 13);
                            } else {
                                $('.js-order-delivery-point-tab-link').trigger('update', [false])
                            }
                        } catch (error) {
                            console.error(error);
                        }
                    });
                }
            }
        ;

        $body.on('click', '.js-order-delivery-form-control', function(e) {
            var
                $el = $(this),
                data = $el.data('value')
            ;

            console.info('changeSplit', $el);

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
        $body.on('click', '.js-order-delivery-celendar-link', showCalendarPopup);
        $body.on('update', '.js-order-delivery-map-container', updatePointMap);
        $body.on('update', '.js-order-delivery-point-container', updatePointList);
        $body.on('change', '.js-order-delivery-point-filter', updatePointFilter);
        $body.on('input', '.js-order-delivery-point-search-input', searchPoint);
        $body.on('click', '.js-order-delivery-suggest-item', applyPointSuggest);
        $body.on('click', '.js-order-delivery-geolocation-link', locatePoint);
        $body.on('submit', '.js-smartAddress-form', function(e) {
            var
                $form = $(this),
                data = $form.serializeArray()
            ;

            e.stopPropagation();
            e.preventDefault();

            if (formValidator.validateRequired($form).isValid) {
                changeSplit(data);
            }
        });
        $body.on('submit', '.js-discount-form', function(e) {
            var
                $form = $(this),
                data = $form.serializeArray(),
                checkUrl = $form.data('checkUrl')
            ;

            if (checkUrl) {
                $.ajax({
                    url: checkUrl,
                    data: {
                        code: $form.find('[data-field="number"]').val()
                    },
                    type: 'post',
                    timeout: 15000
                }).done(function(response) {
                    if (true === response.success) {
                        changeSplit(data);
                    } else if (true === response.showPin) {
                        $form.find('[data-field-container="pin"]').show();
                        $form.find('[data-field="pin"]').focus();
                    } else {
                        changeSplit(data);
                    }
                }).always(function() {
                    console.info('unblock screen');
                }).error(function(xhr, textStatus, error) {
                    changeSplit(data);
                });
            } else {
                changeSplit(data);
            }

            e.stopPropagation();
            e.preventDefault();
        });
        //скролл списка фишек в попапе скидок
        $body.on('DOMNodeInserted', $discountScroll, function () {

            $('[data-scroll]').scroll(function() {
                clearTimeout($.data(this, 'scrollTimer'));
                $('[data-scroll-wrap]').addClass('scrolling');

                $.data(this, 'scrollTimer', setTimeout(function() {
                    $('[data-scroll-wrap]').removeClass('scrolling');
                }, 600));
            });

        });
        $body.on('submit', '.js-order-form', function(e) {
            var
                $form = $(this),
                $accept = $form.find('[data-field="accept"]')
            ;

            e.stopPropagation();

            if ($accept.length) {
                if ($accept.is(':checked')) {
                    $accept.parent().removeClass('error');
                } else {
                    $accept.parent().addClass('error');
                    e.preventDefault();
                }
            }
        });

        try {
            if (!navigator.geolocation) {
                $('.js-order-delivery-geolocation-link').hide();
            }
        } catch (error) {
            console.error(error);
        }

        formValidator.init();
    }
);
