define(
    ['jquery', 'jquery.popup'],
    function ($) {
        var $body = $('body'),

        showPopup = function (e) {
            var
                header      = $('.js-header'),
                searchClass = 'search';

            header.addClass(searchClass);
            e.stopPropagation();
            e.preventDefault();


            // MSITE-156
            $($('.js-search-form').data('inputSelector')).focus();
        };

        $body.on('click', '.js-searchLink', showPopup);

        $('.js-search-form').on('submit', function(e) {
            var $input = $($(e.target).data('inputSelector'));

            if ($input.length && ($input.val().length < 2)) { // FIXME: вынести в data-атрибут
                e.preventDefault();
            }
        });

        var
            submitSearch = function () {
                var
                    searchInputVal = $('.js-search-form-input').val(),
                    url = '/search/autocomplete?q=' + searchInputVal;

                $.ajax({
                    type: 'POST',
                    url: url,
                    success: successSearch,
                    error: errorSearch
                });
            },

            successSearch = function( result ) {
                console.log(result);
            },

            errorSearch = function( jqXHR, textStatus, errorThrown ) {
                console.log(jqXHR);
                console.log(textStatus);
                console.log(errorThrown);
            };

        $body.on('click', 'js-searchLink', showPopup);
        $('.js-search-form-input').on('change', submitSearch);
    }
);