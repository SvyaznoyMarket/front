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
        var boundsDrawed = false;
        var previousSearchPhrase;

        var defaultRegion = $('body').data('config').region.name;

        $searchPointsForm.on('submit', handleSearchFormEvent);
        $partnerIcon.on('click', handlePartnerClickEvent);
        //ymaps.ready(initCart);

        (function initPartnersSlugs(){
            $partnerIcon.each(function(){
                selectedPartners[$(this).data('partnerSlug')] = false;
            });
        })();

        //(function initialLoadPoints(){
        //    makeRequest();
        //})();

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
                zoom: 10
            });

            yaMap.container.fitToViewport();
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
                //beforeSend: function() {
                //    if (myCollection) {
                //        myCollection.removeAll();
                //    }
                //},
                success: function(result) {

                    var tpl = $('#tpl-points-list').html();
                    $('.points__list').html(Mustache.render(tpl, result.data));

                    //if (result.data.coordinates) {
                    //    var center = result.data.coordinates.center || false;
                    //    var upperLeftCorner = result.data.coordinates.angles[0].split(' ');
                    //    var bottomLeftCorner = result.data.coordinates.angles[2].split(' ');
                    //
                    //    yaMap.setCenter([center.latitude, center.longitude]);
                    //}

                    //
                    //if (!boundsDrawed || (filter.phrase !== previousSearchPhrase)) {
                    //    var bounds = new ymaps.GeoObject({
                    //        geometry: {
                    //            type: 'Rectangle',
                    //            coordinates: [
                    //                [upperLeftCorner[1], upperLeftCorner[0]],
                    //                [bottomLeftCorner[1], bottomLeftCorner[0]]
                    //
                    //            ]
                    //        }
                    //    }, {
                    //        draggable: false,
                    //        fillColor: '#ffff0022',
                    //        strokeColor: '#3caa3c88',
                    //        strokeWidth: 1
                    //    });
                    //
                    //    yaMap.geoObjects.add(bounds);
                    //    boundsDrawed = true;
                    //    previousSearchPhrase = filter.phrase;
                    //}


                    //myCollection = new ymaps.GeoObjectCollection();
                    //for (var i = 0; i < result.data.points.length; i++) {
                    //    var currentPoint = result.data.points[i];
                    //
                    //    myCollection.add(new ymaps.Placemark([currentPoint.lat, currentPoint.long],{
                    //        balloonContent: currentPoint.partner
                    //    }, {
                    //        iconLayout: 'default#image',
                    //        iconImageHref: currentPoint.marker
                    //    }));
                    //
                    //}
                    //
                    //yaMap.geoObjects.add(myCollection);

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

            makeRequest(filter);

        }

    }
);