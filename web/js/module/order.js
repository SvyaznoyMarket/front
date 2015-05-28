define(
    [
        'require', 'jquery', 'underscore', 'mustache', 'module/util', 'jquery.maskedinput',
        'module/order/user.form', 'module/order/common', 'module/order/toggle', 'jquery.modal'
    ],
    function(
        require, $, _, mustache, util
    ) {
        var
            $body = $('body'),
            $deliveryForm = $('.js-order-delivery-form'),
            $map = $('#yandexMap'),

            changeSplit = function(e) {
                e.stopPropagation();

                var
                    $el = $(this)
                ;

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

                e.preventDefault();
            },

            showPointPopup = function(e) {
                e.stopPropagation();

                $(this).modal();

                var
                    $el       = $(this),
                    $content  = $('.js-modal-content'),
                    $template = $('#tpl-order-delivery-point-popup'),
                    data      = $.parseJSON($($el.data('dataSelector')).html()) // TODO: выполнять один раз, результат записывать в переменную
                ;

                $content.append(mustache.render($template.html(), data));

                e.preventDefault();

                require(['yandexmaps'], function(ymaps) {});
            },

            // показать календарь
            showCalendar = function(e) {
                e.stopPropagation();

                $(this).modal();

                var
                    $el       = $(this),
                    $content  = $('.js-modal-content'),
                    $template = $('#tpl-order-delivery-calendar'),
                    data      = $.parseJSON($($el.data('dataSelector')).html())
                ;

                $content.append(mustache.render($template.html(), data));
            },

            showMap = function(e) {
                e.stopPropagation();

                var
                    $el = $(e.currentTarget),
                    $container = $($el.data('containerSelector')),
                    mapData = $el.data('mapData')
                ;

                require(['module/yandexmaps'], function(maps) {
                    maps.initMap($map, mapData).done(function(map) {
                        var
                            placemark,
                            points = $.parseJSON($($el.data('dataSelector')).html()).points // TODO: выполнять один раз, результат записывать в переменную
                        ;

                        map.setCenter([mapData.center.lat, mapData.center.lng], mapData.zoom);
                        map.geoObjects.removeAll();
                        map.container.fitToViewport();

                        _.each(points, function(point){
                            try {
                                placemark = new maps.ymaps.Placemark(
                                    [point.lat, point.lng],
                                    {
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

                        $container.append($map.show());
                    });
                });
            }
        ;

        $body.on('click', '.js-order-delivery-form-control', changeSplit);
        $body.on('click', '.js-order-delivery-pointPopup-link', showPointPopup);
        $body.on('click', '.js-order-delivery-map-link', showMap);
        $body.on('click', '.js-order-delivery-celendar-link', showCalendar);
    }
);
