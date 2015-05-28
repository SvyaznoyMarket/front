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
                    data      = $.parseJSON($($el.data('dataSelector')).html())
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

                require(['module/yandexmaps'], function(ymaps) {
                    ymaps.initMap($map, mapData).done(function(map) {
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
