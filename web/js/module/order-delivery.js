define(
    [
        'require', 'jquery', 'underscore', 'mustache', 'module/util', 'module/config', 'module/form-validator', 'module/order/analytics.google', 'jquery.ui', 'jquery.maskedinput', 'module/toggleLink'
    ],
    function(
        require, $, _, mustache, util, config, formValidator, analytics
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
            ymapsDefer = null,

            $body                       = $('body'),
            $orderContent               = $('.js-order-content'),
            loaderClass                 = 'm-body-loader',
            $deliveryForm               = $('.js-order-delivery-form'),
            deliveryData                = $deliveryForm.data('value'),
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

            initMap = function() {
                if (null === ymapsDefer) {
                    ymapsDefer = $.Deferred();

                    require(['yandexmaps'], function(ymaps) {
                        ymaps.ready(function() {
                            ymapsDefer.ymaps = ymaps;
                            ymapsDefer.resolve(ymapsDefer.ymaps);
                        })
                    });
                } else if ('resolved' === ymapsDefer.state()) {
                    ymapsDefer.resolve(ymapsDefer.ymaps);
                }

                return ymapsDefer;
            },

            initPointMap = function($container, options) {
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

                } catch (error) { console.error(error); }
            },

            initAddressMap = function($container, options) {
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
                } catch (error) { console.error(error); }
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

                                    console.info('objs', objs);

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
                                if ('street' === obj.contentType) {
                                    $form.find('[data-field="streetType"]').val(obj.typeShort);

                                    try {
                                        text = (obj.type.length > 8) ? obj.typeShort : obj.type;
                                        if (('string' === typeof text)) {
                                            text = text.charAt(0).toUpperCase() + text.substr(1).toLowerCase();
                                            $(this).parent().find('label').text(text);
                                        }
                                    } catch (error) { console.error(); }
                                }
                            } else {
                                //showError($input, 'Введено неверно');
                            }
                        },
                        labelFormat: function (obj, query) {
                            var objs;

                            if (query.oneString) {
                                if (obj.parents) {
                                    objs = [].concat(obj.parents);
                                    objs.push(obj);

                                    return $.kladr.buildAddress(objs);
                                }

                                return obj.name + (obj.typeShort && query.type == 'street' ? ' ' + obj.typeShort : '');
                            }

                            var label = '',
                                name,
                                objName,
                                queryName,
                                start;

                            name = obj.name;
                            objName = name.toLowerCase();
                            queryName = query.name.toLowerCase();
                            start = objName.indexOf(queryName);
                            start = ~start ? start : 0;

                            if (queryName.length < objName.length) {
                                label += name.substr(0, start);
                                label += '<strong>';
                                label += name.substr(start, queryName.length);
                                label += '</strong>';
                                label += name.substr(start + queryName.length);
                            } else {
                                label += '<strong>' + name + '</strong>';
                            }

                            if (obj.typeShort && query.type == 'street') {
                                label += ' ' + obj.typeShort;
                            }

                            return label;
                        }
                    });

                    $form.find('.js-smartAddress-input').each(function(k, el) {
                        var $el = $(el);

                        $el.kladr('type', $el.data('kladrType'));
                    });
                });
            },

            changeSplit = function( data ) {
                $orderContent.addClass(loaderClass);

                $.ajax({
                    url: $deliveryForm.attr('action'),
                    data: data,
                    type: 'post',
                    timeout: 40000
                }).done(function(response) {
                    $($deliveryForm.data('containerSelector')).html(response);
                }).always(function() {
                    console.info('unblock screen');
                    $orderContent.removeClass(loaderClass);
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

                toggle = false !== toggle;

                e.stopPropagation();

                if (false !== toggle) {
                    index = (0 == index) ? 1 : 0;
                }

                console.info('index', index);

                $selected = $el.find('[data-index="' + index + '"]');
                $container = $($selected.data('containerSelector'));
                $parentContainer = $($container.data('parentContainerSelector'));
                if (toggle) {
                    $parentContainer.toggleClass($parentContainer.data('toggleClass'));
                }

                $el.data('index', index);
                $el.find('[data-index]').each(function(i, el) {
                    $(el).hide();
                });

                filterPoints($el.data('storageSelector'), $filterForm);

                $selected.show();
                $container.trigger('update', [Storage.get($el.data('storageSelector'), 'filtered')]);
            },

            updatePointMap = function(e, data) {
                var
                    $container = $(this),
                    options = $container.data('mapOption'),
                    ready = function() {
                        var placemark;

                        console.info('update point map ...');
                        $('.pick-point').removeClass('m-loader');

                        if (!$container.find('#' + $pointMap.attr('id')).length) {
                            $container.html('');
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
                                        iconImageHref: '/img/points/markers/23x30/' + point.icon,
                                        iconImageSize: [23, 30],
                                        iconImageOffset: [-12, -30],
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

                $('.pick-point').addClass('m-loader');

                if (pointMap) {
                    ready();
                } else {
                    initMap().done(function(ymaps) {
                        initPointMap($pointMap, options);
                        ready();
                    });
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

                e.stopPropagation();

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
                            ($.isArray(params[key]) && -1 === _.indexOf(params[key], point[key].value))
                            || (!$.isArray(params[key]) && params[key] != point[key].value)
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
                        if (data.type == 'bool') {
                            params[key] = data.value;
                        } else {
                            if (typeof params[key] === 'undefined') {
                                params[key] = [];
                            }

                            params[key].push(data.value);
                        }
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
                        var
                            $popup = $modalWindow.find('.js-modal-content').append(mustache.render($pointPopupTemplate.html(), data, $pointPopupTemplate.data('partial'))),
                            $filterForm = $popup.find('.js-order-delivery-points-filter-form');
                      
                        $($filterForm.data('tabSelector')).trigger('update', [false]);
                    },
                    beforeClose: function() {
                        $mapContainer.append($pointMap);
                        $body.off('beforeSplit', beforeSplit);
                    }
                });

                $body.on('beforeSplit', beforeSplit);

                e.preventDefault();
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

                $modalWindow.find('.js-modal-title').text(modalTitle);
                $modalWindow.addClass(modalPosition);
                //$modalWindow.find('.js-modal-content').addClass('m-loader');

                $modalWindow.lightbox_me({
                    onLoad: function() {
                        var
                            $container,
                            options
                        ;

                        $modalWindow.find('.js-modal-content').append(mustache.render($addressPopupTemplate.html(), data));

                        initSmartAddress($modalWindow);

                        $container = $($el.data('mapContainerSelector'));
                        options = $container.data('mapOption');

                        if (!$container.find('#' + $addressMap.attr('id')).length) {
                            $container.html('');
                            $container.append($addressMap);
                        }

                        if (addressMap) {

                        } else {
                            initMap().done(function(ymaps) {
                                initAddressMap($addressMap, options);
                                //$modalWindow.find('.js-modal-content').removeClass('m-loader');
                                //addressMap.setCenter([options.center.lat, options.center.lng], options.zoom);
                                //addressMap.balloon.close();
                                //addressMap.geoObjects.removeAll();
                                //addressMap.container.fitToViewport();
                            });
                        }
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
                initMap().done(function(ymaps) {
                    var
                        geocode = ymaps.geocode(address)
                    ;

                    geocode.then(function (res) {
                        var
                            obj = res.geoObjects.get(0),
                            center = obj ? obj.geometry.getCoordinates() : null,
                            placemark
                        ;

                        if (center) {
                            addressMap.setCenter(center, zoom);

                            //addressMap.geoObjects.removeAll();
                            //placemark = new ymaps.Placemark(center, {}, {});
                            //addressMap.geoObjects.add(placemark);
                        }
                    });
                });
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
                                            value = $el.data('value'),
                                            $field = $form.find('[data-field="number"]')
                                        ;

                                        e.preventDefault();
                                        e.stopPropagation();

                                        $field.val(value);
                                        $field.closest('form').submit();
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

            openHelp = function(e) {
                e.preventDefault();
                e.stopPropagation();
                $('.js-order-delivery-point-filter-fitsAllProducts-help-popup').show();
            },

            closeHelpByBodyClick = function(e) {
                $('.js-order-delivery-point-filter-fitsAllProducts-help-popup').hide();
            },

            closeHelpByCloserClick = function(e) {
                e.preventDefault();
                $('.js-order-delivery-point-filter-fitsAllProducts-help-popup').hide();
            },

            preventHelpPopupClick = function(e) {
                e.stopPropagation();
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

                initMap().done(function(ymaps) {
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

            console.info('changeSplit', $el, $el.is(':checkbox'));

            e.stopPropagation();

            if (!$el.is(':checkbox') && !$el.is(':radio')) {
                e.preventDefault();
            }

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
        $body.on('click', '.js-order-delivery-point-filter-fitsAllProducts-help-opener', openHelp);
        $body.on('click', closeHelpByBodyClick);
        $body.on('click', '.js-order-delivery-point-filter-fitsAllProducts-help-closer', closeHelpByCloserClick);
        $body.on('click', '.js-order-delivery-point-filter-fitsAllProducts-help-popup', preventHelpPopupClick);
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

            setTimeout(function() { analytics.push(['10_1 Доставки_Доставка_ОБЯЗАТЕЛЬНО']); }, 250);
        });
        $body.on('submit', '.js-discount-form', function(e) {
            var
                $form = $(this),
                data = $form.serializeArray(),
                checkUrl = $form.data('checkUrl')
            ;

            if (checkUrl) {
                $orderContent.addClass(loaderClass);

                $.ajax({
                    url: checkUrl,
                    data: {
                        code: $form.find('[data-field="number"]').val(),
                        pin: $form.find('[data-field="pin"]').val()
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
                    $orderContent.removeClass(loaderClass);
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

        $body.on('submit', '.js-order-form', function(e) {
            var
                $form = $(this),
                $accept = $form.find('[data-field="accept"]')
            ;

            e.stopPropagation();

            if ($accept.length) {
                if ($accept.is(':checked')) {
                    $accept.parent().removeClass('error');

                    analytics.push(['15_1 Оформить_успешно_Доставка_ОБЯЗАТЕЛЬНО']);
                } else {
                    $accept.parent().addClass('error');
                    e.preventDefault();

                    analytics.push(['15_2 Оформить_ошибка_Доставка', 'Поле ошибки: accept']);
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

        if ($('.js-order-delivery-remainSumNotice').length) {
            $body.css({
                'overflow':'hidden',
                'position':'fixed'
            });
        }

        console.info('config', config);

        try {
            analytics.push(['7 Вход_Доставка_ОБЯЗАТЕЛЬНО', 'Количество заказов: ' + deliveryData.order.count]);

            $body.on('submit', '.js-regionSet-form', function(e) {
                var $form = $(this);

                analytics.push(['8 Регион_Доставка', 'Было: ' + config.region.name + ', Стало: ' + $form.find('#js-regionSet-input').val()]);
            });
            $body.on('click', '.js-regionSet-link', function(e) {
                var $el = $(this);

                analytics.push(['8 Регион_Доставка', 'Было: ' + config.region.name + ', Стало: ' + $el.text()]);
            });
            $body.on('click', '.js-order-delivery-pointPopup-link', function(e) {
                analytics.push(['10 Место_самовывоза_Доставка_ОБЯЗАТЕЛЬНО']);
            });
            $body.on('click', '.js-order-delivery-celendar-link', function(e) {
                analytics.push(['11 Срок_доставки_Доставка']);
            });
            $body.on('click', '.js-order-delivery-discountPopup-link', function(e) {
                analytics.push(['12 Код_скидки_Доставка']);
            });
            $body.on('click', '.js-order-form-accept-field', function(e) {
                analytics.push(['14 Согласен_оферта_Доставка_ОБЯЗАТЕЛЬНО']);
            });
            $body.on('click', '.js-order-delivery-form-control', function(e) {
                var $el = $(this);

                if ('date' === $el.data('type')) {
                    analytics.push(['11_1 Срок_Изменил_дату_Доставка']);
                } else if (('payment' === $el.data('type')) && ('2' === $el.val())) {
                    analytics.push(['13_1 Оплата_банковской_картой_Доставка']);
                } else if ('point' === $el.data('type')) {
                    analytics.push(['10_1 Ввод_данных_Самовывоза']);
                }
            });
        } catch (error) { console.error(error); }
    }
);
