define(
    [
        'jquery', 'underscore', 'yandexmaps', 'mustache'
    ],
    function (
        $, _, ymaps, Mustache
    ) {

        var $searchPointsForm = $('.js-search-points-form');
        var $searchPointsInput = $('.js-search-points-input');
        var $partnerIcon = $('.js-partner-icon');
        var yaMap;
        var selectedPartners = {};
        var filter = {};
        var myCollection;

        var defaultRegion = $('body').data('config').region.name;

        $searchPointsForm.on('submit', handleSearchFormEvent);
        $partnerIcon.on('click', handlePartnerClickEvent);
        ymaps.ready(initCart);

        (function initPartnersSlugs(){
            $partnerIcon.each(function(){
                selectedPartners[$(this).data('partnerSlug')] = false;
            });
        })();


        function handleSearchFormEvent(evt) {
            evt.preventDefault();

            prepareRequest();
        }

        function handlePartnerClickEvent(evt) {
            evt.preventDefault();

            var partnerSlug = $(this).data('partnerSlug');

            if ($(this).hasClass('selected')) {
                $(this).removeClass('selected');
                selectedPartners[partnerSlug] = false;
            } else {
                $(this).addClass('selected');
                selectedPartners[partnerSlug] = true;
            }

            prepareRequest( {partners: findSelectedPartners()} );
        }

        function initCart() {
            var lat = 55.72504493;
            var long = 37.64696100;

            if ('geolocation' in navigator) {
                navigator.geolocation.getCurrentPosition(function(position) {
                    lat = position.coords.latitude;
                    long = position.coords.longitude;
                });
            }

            yaMap = new ymaps.Map("map", {
                center: [lat, long],
                zoom: 10,
                controls: []
            });

            yaMap.container.fitToViewport();

            myCollection = new ymaps.GeoObjectCollection();

            prepareRequest({}, true);
        }

        function findSelectedPartners() {
            var partners = [];

            _.each(selectedPartners, function(partner, key) {
                if (partner) {
                    partners.push(key);
                }
            });

            return partners;
        }

        function makeRequest(filter, redrawMapPoints) {

            var filterCheck = filter || {};

            $.ajax({
                url: '/ajax/get-shops',
                data: filterCheck,
                type: 'POST',
                success: function(result) {
                    if (result.data.mapCenter) {
                        var center = result.data.mapCenter || false;

                        yaMap.setCenter([center.latitude, center.longitude]);
                    }

                    if (redrawMapPoints) {
                        placePoints(result.data.points);
                    } else {
                        yaMap.setZoom(14);
                    }
                }
            });
        }

        function prepareRequest(filterRequest, redrawMapPoints) {

            if ($searchPointsInput.val().length > 0) {
                filter.phrase = $searchPointsInput.val() + ' ' + defaultRegion;
            }

            if (filterRequest !== undefined && filterRequest.partners) {
                filter.partners = filterRequest.partners;
            }

            filter.redirectTo = window.location.pathname || false;

            makeRequest(filter, redrawMapPoints || false);
        }

        function placePoints(points) {

            for (var i = 0; i < points.length; i++) {
                var currentPoint = points[i];

                var props = {
                    pointName: currentPoint.name,
                    pointLogo: currentPoint.logo,
                    pointAddress: currentPoint.address,
                    pointLink: currentPoint.link,
                    subway: []
                };

                if (currentPoint.subway) {
                    for (var key in currentPoint.subway) {
                        if (currentPoint.subway[key].line === null || currentPoint.subway[key].name === null) continue;

                        props.subway.push({
                            color: (currentPoint.subway[key].line) ? currentPoint.subway[key].line.color : false,
                            station: currentPoint.subway[key].name
                        });

                    }
                }


                var BalloonContentLayout = ymaps.templateLayoutFactory.createClass(
                    '<div class="map-baloon clearfix">'+
                        '<div class="shop-snippet">'+
                            '<div class="shop-snippet-name">'+
                            '<img src="{{properties.pointLogo}}" alt="" class="shop-snippet-name__img">'+
                            '<span class="shop-snippet-name__text">{{properties.pointName}}</span>'+
                        '</div>'+

                        '<div class="shop-snippet-info">'+
                            '{% for subway in properties.subway %}'+
                             '{{#subway}}'+
                            '<div class="shop-snippet-metro">'+
                                '<span class="shop-snippet-metro__bullet" style="background:{{subway.color}}"></span>'+
                                '<span class="shop-snippet-metro__text">м. {{subway.station}}</span>'+
                            '</div>'+
                            '{{/subway}}'+
                            '{% endfor %}'+
                            '<div class="shop-snippet-address">{{properties.pointAddress}}</div>'+
                        '</div>'+
                    '</div>'+
                    '<a class="shop-snippet__more" href="{{properties.pointLink}}">Подробнее</a>'
                );

                myCollection.add(new ymaps.Placemark([currentPoint.latitude, currentPoint.longitude],props, {
                    iconLayout: 'default#image',
                    iconImageHref: currentPoint.marker,
                    balloonContentLayout: BalloonContentLayout,
                    balloonPanelMaxMapArea: 0,
                    hideIconOnBalloonOpen: false
                }));

            }

            yaMap.geoObjects.add(myCollection);
        }

    }
);