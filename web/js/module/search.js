define(
    ['jquery', 'underscore', 'mustache', 'jquery.popup'],
    function ($, _, mustache) {

        $('.js-search-form').on('submit', function(e) {
            var $input = $($(e.target).data('inputSelector'));

            if ($input.length && ($input.val().length < 2)) { // FIXME: вынести в data-атрибут
                e.preventDefault();
            }
        });

        var
            body          = $('body'),
            formSearch    = $('.js-search-form'),
            inputSearch   = $('.js-search-form-input'),
            suggest       = $('.js-search-suggest'),
            template      = $('#tpl-search-suggest').html(),
            header        = $('.js-header'),
            searchClass   = 'search',
            noScrollClass = 'noScroll',

            // Показ блока поиска
            showSearch = function( event ) {
                header.addClass(searchClass);
                inputSearch.trigger('focus');
                event.preventDefault();
                event.stopPropagation();
            },

            // Запрос с поисковой строкой
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

            // Закрытие блокв с полем ввода поиска
            closeSearch = function( event ) {
                var
                    target = event.target;

                if ( !formSearch.is(target) && formSearch.has(target).length === 0 ) {

                    header.removeClass(searchClass);
                    body.removeClass(noScrollClass);
                    inputSearch.val('');
                    suggest.hide().empty();
                }
            },

            // Отчистка поля ввода поиска
            clearSuggest = function( event ) {
                event.preventDefault();

                inputSearch.val('');
                body.removeClass(noScrollClass);
                inputSearch.trigger('focus');
                suggest.hide().empty();
            },

            // Ответ по поисковому запросу, показ саджеста
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

                        if ( result.categories.length > 1 || result.products.length >1 ) {
                            html = mustache.render(template, {suggestData: suggestData});
                            body.addClass(noScrollClass);
                            suggest.show().html(html);
                        }
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
        // end of vars

        // events
        body.on('click', '.js-searchLink', showSearch);
        body.on('keyup', '.js-search-form-input', submitSearch);
        body.on('click', '.js-search-input-clear', clearSuggest);

        // закрыть блок с полем ввода поиска
        body.on('click', function() {
            // закрываем по клику если блок виден а саджест не отображен
            if( formSearch.is(':visible') && !suggest.is(':visible') ) {
                closeSearch( event );
            }
            // при клике по ссылке саджеста показать лоадер
            else if ( suggest.is(':visible') ) {
                suggest.addClass('m-body-loader');
            }
        });

        // скрыть блок поиска при движении пальца по экраны, только если саджест не показан
        body.on('touchmove', function() {
            if( !suggest.is(':visible') ) {
                closeSearch( event );
            }
        });
    }
);