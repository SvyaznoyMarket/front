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

                var
                    data          = {},
                    hasCategories = false,
                    hasProducts   = false,
                    i;

                if ( result.categories.length ) {
                    for ( i = 0; i < result.categories.length; i++ ) {
                        var category = [];
                        category[i].push({
                            name: result.categories[i].name,
                            url: result.categories[i].link,
                            img: result.categories[i].image
                        })


                    }
                    data.categories = {
                        hasCategories: true,
                        category: category
                    }
                }

                if ( result.products.length ) {
                    for ( i = 0; i < result.products.length; i++ ) {
                        var product = [];

                        product[i].push({
                            name: result.products[i].name,
                            url: result.products[i].link,
                            img: result.products[i].image
                        })


                    }
                    data.products = {
                        hasProducts: true,
                        category: category
                    }
                }

                console.log(data);

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