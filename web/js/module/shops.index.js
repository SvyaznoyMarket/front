define(
    [
        'jquery', 'underscore', 'yandexmaps'
    ],
    function (
        $, _, ymaps
    ) {

        var $searchPointsForm = $('.js-search-points-form');
        var $searchPointsInput = $('.js-search-points-input');

        $searchPointsForm.on('submit', handleSearchFormEvent);


        function handleSearchFormEvent(evt) {
            evt.preventDefault();

            var searchValue = $searchPointsInput.val();

            $.ajax({
                url: 'ajax/get-coordinates-by-adress',
                data: { phrase: searchValue},
                type: 'POST',
                success: function(data) {
                    console.dir(data);
                }
            });

        }



        //ymaps.ready(init);
        //var myMap,
        //    myPlacemark;
        //
        //function init(){
        //    myMap = new ymaps.Map("map", {
        //        center: [55.76, 37.64],
        //        zoom: 7
        //    });
        //
        //    myPlacemark = new ymaps.Placemark([55.76, 37.64], {
        //        hintContent: 'Москва!',
        //        balloonContent: 'Столица России'
        //    });
        //
        //    myMap.geoObjects.add(myPlacemark);
        //}

    }
);