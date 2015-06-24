define(
    ['jquery', 'underscore', 'mustache', 'jquery.popup'],
    function ($, _, mustache) {
        var $body = $('body'),

        showPopup = function (e) {
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
            inputSearch = $('.js-search-form-input'),
            container   = $('.js-search-suggest'),
            template    = $('#tpl-search-suggest').html(),
            header      = $('.js-header'),
            searchClass = 'search',

            submitSearch = function ( event ) {
                var
                    searchInputVal = $('.js-search-form-input').val(),
                    url = '/search/autocomplete?q=' + searchInputVal;

                event.stopPropagation();

                if ( searchInputVal !== '' && searchInputVal.length > 3 ) {
                    $.ajax({
                        type: 'POST',
                        url: url,
                        success: successSearch,
                        error: errorSearch
                    });
                }
            },

            closeSearch = function() {
                header.removeClass(searchClass);
                $body.css({'overflow':'visible'});
                inputSearch.val('');
                container.hide().empty();
            },

            clearSuggest = function( event ) {
                event.preventDefault();
                event.stopPropagation();

                inputSearch.val('');
                $body.css({'overflow':'visible'});
                inputSearch.trigger('focus');
                container.hide().empty();
            },

            successSearch = (function () {
                var
                    timeWindow = 500, // time in ms
                    timeout,

                    successSearch = function ( result ) {
                        console.log(result);

                        var
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

                        html = mustache.render(template, {suggestData: suggestData});
                        $body.css({'overflow':'hidden'});
                        container.show().html(html);
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
        $body.on('keyup focus click', '.js-search-form-input', submitSearch);
        $body.on('click', '.js-search-input-clear', clearSuggest);
        $body.on('click', closeSearch);
    }
);