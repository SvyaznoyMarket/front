define(
    ['jquery', 'underscore', 'mustache', 'jquery.popup'],
    function ($, _, mustache) {
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

                if ( searchInputVal !== '' && searchInputVal.length > 3 ) {
                    $.ajax({
                        type: 'POST',
                        url: url,
                        success: successSearch,
                        error: errorSearch
                    });
                }
            },

            successSearch = (function () {
                var
                    timeWindow = 500, // time in ms
                    timeout,

                    successSearch = function ( result ) {
                        console.log(result);

                        var
                            container   = $('.js-search-suggest'),
                            template    = $('#tpl-search-suggest').html(), 
                            suggestData = {
                                categories: {
                                    category: []
                                },
                                products: {
                                    product: []
                                }
                            },
                            html;

                        if ( result.hasOwnProperty('categories') && _.isArray(result.categories) && result.categories.length ) {
                            suggestData.categories.category = result.categories;
                            suggestData.categories.hasCategories = true;
                        }

                        if ( result.hasOwnProperty('products') && _.isArray(result.products) && result.products.length ) {
                            suggestData.products.product = result.products;
                            suggestData.products.hasProducts = true;
                        }

                        console.log(suggestData);

                        html = mustache.render(template, {suggestData: suggestData});
                        container.html(html);
                    };
                // end of vars

                return function() {
                    var
                        context = this,
                        result = arguments;
                    // end of vars

                    clearTimeout(timeout);

                    timeout = setTimeout(function() {
                        successSearch.apply(context, result);
                    }, timeWindow);
                };
            }()),

            errorSearch = function( jqXHR, textStatus, errorThrown ) {
                console.log(jqXHR);
                console.log(textStatus);
                console.log(errorThrown);
            };

        $body.on('click', 'js-searchLink', showPopup);
        $('.js-search-form-input').on('keyup change', submitSearch);
    }
);