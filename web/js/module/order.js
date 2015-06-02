define(
    [
        'require', 'jquery', 'underscore', 'mustache', 'module/util', 'module/config', 'jquery.maskedinput',
        'module/order/user.form', 'module/order/common', 'module/order/toggle'
    ],
    function(
        require, $, _, mustache, util, config
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

            initSmartAddress = function($context) {
                var
                    $form = $context.find('.js-smartAddress-form')
                ;

                require(['jquery.kladr'], function() {
                    $.kladr.setDefault({
                        token: config.kladr.token,
                        key: config.kladr.key,
                        type: $.kladr.type.street,
                        parentType: $.kladr.type.city,
                        parentId: config.kladr.city.id,

                        parentInput: '.js-smartAddress-form',
                        verify: true,
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
                        }
                    });

                    $form.find('.js-smartAddress-input').each(function(k, el) {
                        var $el = $(el);

                        $el.kladr('type', $el.data('kladrType'));
                    });
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

            getPoints = function( params ) {
                var
                    id = $('.js-delivery-type').data('data-selector'),
                    data = $.parseJSON($(id).html()),
                    points = data.points,
                    newPoints = {};

                newPoints.points = [];

                for ( var j = 0; j < params.length; j++ ) {

                    for ( var i = 0; i < points.length; i++ ) {
                        if ( params[j] == points[i].group.value || params[j] == points[i].cost.name || params[j] == points[i].date.value ) {
                            newPoints.points.push(points[i]);
                        }
                    }
                }

                $('.js-order-points-container-type-points').html(mustache.render($pointPopupTemplate.html(), newPoints));

                console.log(newPoints);
                console.log(data);
            },

            filterPoints = function( e ) {
                e.stopPropagation();
                var
                    $filterList = $('.js-order-delivery-points-filter-params-list'),
                    $inputCheck = $filterList.find('input'),
                    params = [];

                $inputCheck.each(function() {
                    if ( $(this).prop('checked') == true ) {
                        params.push(this.value);
                    }
                });

                getPoints(params);

                console.log(params);
            },

            showPointPopup = function(e) {
                e.stopPropagation();

                var
                    $el       = $(this),
                    data      = $.parseJSON($($el.data('dataSelector')).html()), // TODO: выполнять один раз, результат записывать в переменную
                    $modalWindow = $($modalWindowTemplate.html()).appendTo($body),
                    modalTitle = $el.data('modal-title'),
                    modalPosition = $el.data('modal-position');

                $modalWindow.find('.js-modal-title').text(modalTitle);
                $modalWindow.addClass(modalPosition);

                $modalWindow.lightbox_me({
                    onLoad: function() {
                        $modalWindow.find('.js-modal-content').append(mustache.render($pointPopupTemplate.html(), data, $pointPopupTemplate.data('partial')));
                        $body.css({'overflow':'hidden'});
                    },
                    beforeClose: function() {
                        $mapContainer.append($map);
                        $body.css({'overflow':'auto'});
                    },
                    centered: false
                });

                e.preventDefault();

                setTimeout(function() {
                    require(['module/yandexmaps'], function() {});
                }, 2500)
            },

            showAddressPopup = function(e) {
                var
                    $el           = $(this),
                    $modalWindow  = $($modalWindowTemplate.html()).appendTo($body),
                    modalTitle    = $el.data('modal-title'),
                    modalPosition = $el.data('modal-position'),
                    data          = {}
                ;

                require(['jquery.kladr'], function() {});

                $modalWindow.find('.js-modal-title').text(modalTitle);
                $modalWindow.addClass(modalPosition);

                $modalWindow.lightbox_me({
                    onLoad: function() {
                        $modalWindow.find('.js-modal-content').append(mustache.render($addressPopupTemplate.html(), data));

                        initSmartAddress($modalWindow);
                    },
                    modalCSS: {top: '60px'}
                });
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
                    }
                    // modalCSS: {top: '60px'},
                });
            },

            showDiscountPopup = function(e) {
                e.stopPropagation();
                console.info('showDiscountPopup');

                var
                    $el       = $(this),
                    data = {},
                    //data      = $.parseJSON($($el.data('dataSelector')).html()),
                    $modalWindow = $($modalWindowTemplate.html()).appendTo($body),
                    modalTitle = $el.data('modal-title'),
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

            showMap = function(e) {
                e.stopPropagation();

                var
                    $el = $(e.currentTarget),
                    $elText = $el.find('.js-order-delivery-map-link-text'),
                    $container = $($el.data('containerSelector')),
                    $containerPoints = $('.js-order-points-container'),
                    mapData = $el.data('mapData'),
                    showMapClass ='show-map'
                ;

                $containerPoints.toggleClass(showMapClass);
                $elText.text( $('.js-order-points-containet-type:hidden').data('order-points-type') );

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

                        $container.append($map);

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
        $body.on('change', '.js-order-delivery-points-filter-params-list input', filterPoints);
    }
);
