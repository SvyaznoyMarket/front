define(
    [
        'require', 'jquery', 'underscore', 'mustache', 'module/util', 'jquery.maskedinput',
        'module/order/user.form', 'module/order/common', 'module/order/toggle'
    ],
    function(
        require, $, _, mustache, util
    ) {
        var
            $body = $('body'),
            $deliveryForm = $('.js-order-delivery-form'),
            $map = $('#yandexMap'),
            $mapContainer = $('#yandexMap-container'),
            $balloonTemplate = $('#tpl-order-delivery-marker-balloon'),
            $pointPopupTemplate = $('#tpl-order-delivery-point-popup'),
            $calendarTemplate = $('#tpl-order-delivery-calendar'),
            $addressPopupTemplate = $('#tpl-order-delivery-address-popup'),
            $modalWindowTemplate = $('#tpl-modalWindow'),
            $discountPopupTemplate = $('#tpl-order-delivery-discount-popup'),

            initMap = function(map) {
                map.geoObjects.events.remove('click'); // TODO: можно убрать
                map.geoObjects.events.add('click', function (e) {
                    var
                        placemark = e.get('target')
                    ;

                    console.info('placemark', placemark, placemark.properties.get('point'));

                    map.balloon.open(e.get('coords'), mustache.render($balloonTemplate.html(), placemark.properties.get('point')));
                });
            },

            changeSplit = function(e) {
                e.stopPropagation();

                var
                    $el = $(e.currentTarget)
                ;

                if ($el.data('value')) {
                    $.ajax({
                        url: $deliveryForm.attr('action'),
                        data: $el.data('value'),
                        type: 'post',
                        timeout: 30000
                    }).done(function(response) {
                        $($deliveryForm.data('containerSelector')).html(response)
                    }).always(function() {
                        console.info('unblock screen');
                    });
                }

                e.preventDefault();
            },

            showPointPopup = function(e) {
                e.stopPropagation();

                var
                    $el       = $(this),
                    data      = $.parseJSON($($el.data('dataSelector')).html()), // TODO: выполнять один раз, результат записывать в переменную
                    $modalWindow = $($modalWindowTemplate.html()).appendTo($body),
                    modalTitle = $el.data('modal-title')
                ;

                $modalWindow.find('.js-modal-title').text(modalTitle);

                $modalWindow.lightbox_me({
                    onLoad: function() {
                        $modalWindow.find('.js-modal-content').append(mustache.render($pointPopupTemplate.html(), data));
                    },
                    beforeClose: function() {
                        $mapContainer.append($map);
                    }
                });

                e.preventDefault();

                require(['yandexmaps'], function(ymaps) {});
            },

            showAddressPopup = function(e) {
                var
                    data = {}
                ;

                mustache.render($pointPopupTemplate.html(), data);
            },

            // показать календарь
            showCalendar = function(e) {
                e.stopPropagation();

                var
                    $el       = $(this),
                    data      = $.parseJSON($($el.data('dataSelector')).html()),
                    $modalWindow = $($modalWindowTemplate.html()).appendTo($body),
                    modalTitle = $el.data('modal-title'),
                    modalPosition = $el.data('modal-position');

                $modalWindow.find('.js-modal-title').text(modalTitle);
                $modalWindow.addClass(modalPosition);

                $modalWindow.lightbox_me({
                    onLoad: function() {
                        $modalWindow.find('.js-modal-content').append(mustache.render($calendarTemplate.html(), data));
                    },
                    centered: true
                });
            },

            showDiscountPopup = function(e) {
                var
                    data = {}
                ;

                console.info('showDiscountPopup');

                mustache.render($discountPopupTemplate.html(), data);
            },

            showMap = function(e) {
                e.stopPropagation();

                var
                    $el = $(e.currentTarget),
                    $container = $($el.data('containerSelector')),
                    mapData = $el.data('mapData')
                ;

                require(['module/yandexmaps'], function(maps) {
                    maps.initMap($map, mapData, initMap).done(function(map) {
                        var
                            placemark,
                            points = $.parseJSON($($el.data('dataSelector')).html()).points // TODO: выполнять один раз, результат записывать в переменную
                        ;

                        map.setCenter([mapData.center.lat, mapData.center.lng], mapData.zoom);
                        map.balloon.close();
                        map.geoObjects.removeAll();
                        map.container.fitToViewport();

                        _.each(points, function(point){
                            try {
                                placemark = new maps.ymaps.Placemark(
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

                                map.geoObjects.add(placemark);
                            } catch (e) {
                                console.error(e);
                            }
                        });

                        $container.append($map);
                    });
                });
            }
        ;

        $body.on('click', '.js-order-delivery-form-control', changeSplit);
        $body.on('click', '.js-order-delivery-pointPopup-link', showPointPopup);
        $body.on('click', '.js-order-delivery-addressPopup-link', showAddressPopup);
        $body.on('click', '.js-order-delivery-discountPopup-link', showDiscountPopup);
        $body.on('click', '.js-order-delivery-map-link', showMap);
        $body.on('click', '.js-order-delivery-celendar-link', showCalendar);
    }
);
