define(
    ['jquery', 'yandexmaps'],
    function ($, ymaps) {
        var
            map
        ;

        return {
            ymaps: ymaps,
            initMap: function($container, data, init) {
                var defer = $.Deferred();

                if (!$container.length) {
                    throw {name: 'Не найден контейнер для карты'};
                }
                if (!$container.attr('id')) {
                    throw {name: 'Не задан атрибут id для контейнера карты'};
                }

                console.info(['module/ymaps.initMap', map, $container]);

                if (map) {
                    defer.resolve(map, $container);
                } else {
                    ymaps.ready(function() {
                        try {
                            map = new ymaps.Map(
                                $container.attr('id'),
                                {
                                    center: [data.center.lat, data.center.lng],
                                    zoom: data.zoom
                                },
                                {
                                    autoFitToViewport: 'always'
                                }
                            );

                            init(map);

                            defer.resolve(map, $container);
                        } catch (error) {
                            console.error(error);

                            defer.reject(error);
                        }
                    });
                }

                return defer.promise();
            }
        }
    }
);