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

            prepareRequest();
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

        function makeRequest(filter) {

            var filterCheck = filter || {};

            $.ajax({
                url: '/ajax/get-shops',
                data: filterCheck,
                type: 'POST',
                beforeSend: function() {
                    if (myCollection) {
                        myCollection.removeAll();
                    }
                },
                success: function(result) {
                    if (result.data.mapCenter) {
                        var center = result.data.mapCenter || false;

                        yaMap.setCenter([center.latitude, center.longitude]);
                    }

                    placePoints(result.data.points);

                }
            });
        }

        function prepareRequest(filterRequest) {

            if ($searchPointsInput.val().length > 0) {
                filter.phrase = $searchPointsInput.val() + ' ' + defaultRegion;
            }

            if (filterRequest !== undefined && filterRequest.partners) {
                filter.partners = filterRequest.partners;
            }

            filter.redirectTo = window.location.pathname || false;

            makeRequest(filter);
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
                        props.subway.push({
                            color: (currentPoint.subway[key].line) ? currentPoint.subway[key].line.color : false,
                            station: currentPoint.subway[key].name
                        });
                    }
                }



                var BalloonContentLayout = ymaps.templateLayoutFactory.createClass(
                    '<div style="margin: 10px;">' +
                    '<div style="inline-block">' +
                    '<img src="{{properties.pointLogo}}"/>' +
                    '<p>{{properties.pointName}}</p>' +
                    '</div>' +
                    '<div style="inline-block">' +
                    '{% for subway in properties.subway %}' +
                    '<p><span class="metro-bullet" style="background:{{subway.color}}; width:5px; height:5px; display:inline-block;border-radius:50%;"></span>м. {{subway.station}}</p>' +
                    '{% endfor %}' +
                    '<p>{{properties.pointAddress}}</p>' +
                    '<p><a href="{{properties.pointLink}}">Подробнее</a></p>' +
                    '</div>' +
                    '</div>'
                );

                myCollection.add(new ymaps.Placemark([currentPoint.latitude, currentPoint.longitude],props, {
                    iconLayout: 'default#image',
                    iconImageHref: currentPoint.marker,
                    balloonContentLayout: BalloonContentLayout,
                    balloonPanelMaxMapArea: 0
                }));

            }

            yaMap.geoObjects.add(myCollection);
        }

    }
);