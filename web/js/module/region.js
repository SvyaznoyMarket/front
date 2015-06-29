define(
    [
        'jquery',
        'module/analytics.google',
        'jquery.ui'
    ],
    function ($, googleAnalytics) {
        // автоподстановка регионов
        var $regionSetInput = $('#js-regionSet-input');
        $regionSetInput.autocomplete({
            autoFocus: true,
            appendTo: '#js-region-autocomplete',
            source: function(request, response) {
                $.ajax({
                    url: $regionSetInput.data('url'),
                    dataType: 'json',
                    data: {
                        q: request.term
                    },
                    success: function(data) {
                        response($.map(data.result, function(item) {
                            return {
                                label: item.name,
                                value: item.name,
                                url: item.url,
                                dataGa: {'m_city_selected': ['send', 'event', 'm_city_selected', item.name]}
                            };
                        }));
                    }
                });
            },
            minLength: 3,
            select: function(e, ui) {
                console.info('select:js-regionSet-input', e, ui);

                var $form = $($regionSetInput.data('formSelector'));

                $form.attr('action', ui.item.url);

                // ga
                googleAnalytics.handle(ui.item.dataGa, $(e.target), e);
                $form.data('gaSubmit', {'m_city_changed': ['send', 'event', 'm_city_changed', ui.item.label]})
            },
            open: function() {
                //$(this).removeClass('ui-corner-all').addClass('ui-corner-top');
                $('.ui-autocomplete').css({'left' : 0, 'top' : '5px', 'width' : '100%'});
            },
            close: function() {
                //$(this).removeClass('ui-corner-top').addClass('ui-corner-all');
            },
            messages: {
                noResults: '',
                results: function(amount) {
                    return '';
                }
            }
        });


        var selectCity = function() {
            var body = $('body'),
                popupCity = $('.jsCitySelectBox')
            // end of vars

            var
            /**
             * Показываем попап выбора города
            */
            selectCityPopup = function selectCityPopup() {
                var topPopup = $('.header').height() + 20;

                popupCity.enterPopup({
                    popupCSS : {top: topPopup, marginTop: 0}
                });
            };
            //end of functions

            body.on('click', '.jsSelectCity', selectCityPopup);
        };

        selectCity();

        // очистка поля ввода региона
        $('.js-regionSet-clear').on('click', function(e) {
            var $input = $($(e.target).data('inputSelector'))
            ;

            $input.val('');

            e.preventDefault();
        });
    }
);