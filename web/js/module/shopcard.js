define(
    [
        'jquery', 'underscore', 'yandexmaps', 'mustache', 'jquery.slick'
    ],
    function (
        $, _, ymaps, Mustache
    ) {
        var $shopMap = $('#shop-map');

        var yaMap;


        ymaps.ready(initCart);

        function initCart() {

            var lat = $shopMap.data('lat');
            var long = $shopMap.data('long');
            var marker = $shopMap.data('marker');

            yaMap = new ymaps.Map("shop-map", {
                center: [lat, long],
                zoom: 15,
                controls: []
            }, {
                suppressObsoleteBrowserNotifier: true
            });

            yaMap.geoObjects.add(new ymaps.Placemark(
                [lat, long],
                {},
                {
                    iconLayout: 'default#image',
                    iconImageHref: marker
                }));

            yaMap.container.fitToViewport();
        }

        $(document).ready(function(){
            $('.js-point-photos-slider').slick({
                dots: true,
                arrows: false
            });
        });

    }
);