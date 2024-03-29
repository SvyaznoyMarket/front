define(
    [
        'require', 'jquery', 'underscore', 'mustache', 'module/util', 'module/config',
        'jquery.photoswipe', 'module/product.view', 'module/product.card.kit', 'jquery.slick',
        'module/product.card.tab' // todo: выпилить после завершения аб теста
    ],
    function (
        require, $, _, mustache, util, config
    ) {
        var $body = $('body');

        // запрос слайдеров
        var recommendedUrls = [];
        $('.js-productSlider').each(function(i, el) {
            var url = $(el).data('url');
            if (!url) return; // continue

            recommendedUrls.push(url);
        });
        recommendedUrls = _.uniq(recommendedUrls);

        _.each(recommendedUrls, function(url) {
            $.get(url).done(function(response) {
                _.each(response.result.widgets, function(templateData, widgetSelector) {
                    if (!_.isObject(templateData) || !widgetSelector) {
                        console.warn('slider', widgetSelector, templateData);
                        return;
                    }

                    var $widget = $(widgetSelector);

                    if (templateData.count <= 0 && $widget) {
                        $widget.remove();
                        return;
                    }

                    console.info('slider', templateData, $widget);

                    $widget.trigger('render', templateData);
                    $body.trigger('render');

                    var $parent = $widget.parents('.js-container');
                    if ($parent.length) {
                        $parent.show();
                        $($parent.data('tabSelector')).animate({width: 'show'});
                    }

                });

                $('.js-productSliderList').each(function(){
                    var dataGa = $(this).parents('.js-productSlider').data('ga');

                    $(this).slick({
                        infinite: false,
                        slidesToShow: 6,
                        slidesToScroll: 6,
                        arrows: false,
                        responsive: [
                            {
                                breakpoint: 800,
                                settings: {
                                    slidesToShow: 6,
                                    slidesToScroll: 6
                                }
                            },
                            {
                                breakpoint: 700,
                                settings: {
                                    slidesToShow: 5,
                                    slidesToScroll: 5
                                }
                            },
                            {
                                breakpoint: 600,
                                settings: {
                                    slidesToShow: 4,
                                    slidesToScroll: 4
                                }
                            },
                            {
                                breakpoint: 500,
                                settings: {
                                    slidesToShow: 3,
                                    slidesToScroll: 3
                                }
                            },
                            {
                                breakpoint: 340,
                                settings: {
                                    slidesToShow: 2,
                                    slidesToScroll: 2
                                }
                            }
                        ]
                    });

                    $(this).find('.sliderControls_btn').data('gaClick', dataGa);
                });
            });
        });

        // слайдер основного изображения
        $('.js-detailSlider').slick({
            infinite: false,
            dots: true,
            arrows: false,
            nextArrow: '<span class="sliderControls_btn sliderControls_btn__right js-ga-click"></span>',
            prevArrow: '<span class="sliderControls_btn sliderControls_btn__left js-ga-click"></span>',
            customPaging: function(slider, i) {
                return '<span class="slider_pag_i"></span>';
            },
            appendDots: '.slider_pag'
        });


        // direct-credit
        var $creditPayment = $('.js-creditPayment');
        console.info('creditPayment', $creditPayment);
        var dataValue = $creditPayment.data('value');
        _.isObject(dataValue) && require(['module/direct-credit', 'direct-credit'], function(directCredit) {
            dataValue.product.quantity = 1;

            directCredit.getPayment(
                dataValue.partnerId,
                dataValue.sessionId,
                dataValue.product,
                function (result) {
                    var $template = $($creditPayment.data('templateSelector')),
                        $price = $($creditPayment.data('priceSelector')),
                        price = Math.ceil(result.payment);

                    $price.html(mustache.render($template.html(), {
                        shownPrice: util.formatCurrency(price)
                    }));

                    $creditPayment.show();
                }
            );
        });


        // kit
        $('.js-productKit-reset').on('click', function(e) {
            var
                $reset = $(e.target),
                resetValue = $reset.data('value'),
                $spinners = $($reset.data('spinnerSelector'))
            ;

            $spinners.each(function(i, el) {
                var
                    $el = $(el),
                    buttonDataValue = $($el.data('buttonSelector')).data('value'),
                    dataValue = $el.data('value'),
                    product = resetValue.product[dataValue.product.id]
                ;

                if (!product || !buttonDataValue || !buttonDataValue.product || !buttonDataValue.product[product.id]) {
                    return true; // continue
                }

                buttonDataValue.product[product.id].quantity = product.quantity;
                $el.val(product.quantity);
            });
        });


        // reviews
        $('.js-productReviewList-more').on('click', function(e) {
            e.preventDefault();

            try {
                var
                    $moreLink = $(this),
                    $listContainer = $($moreLink.data('containerSelector')),
                    url = $listContainer.data('url'),
                    dataValue = $listContainer.data('value')
                ;

                if (url && (true !== $moreLink.data('disabled'))) {
                    $.get(url, dataValue)
                        .done(function(response) {
                            if (_.isObject(response.result) && dataValue && $listContainer.length) {
                                dataValue.page = response.result.page;
                                dataValue.count = response.result.count;
                                dataValue.limit = response.result.limit;

                                if (
                                    ((dataValue.page * dataValue.limit) > dataValue.count)
                                    || !response.result.count
                                ) {
                                    $moreLink.hide();
                                } else {
                                    $moreLink.show();
                                }

                                $listContainer.append(response.result.reviewBlock);
                            }
                        })
                        .always(function() {
                            $moreLink.data('disabled', false);
                        })
                    ;

                    $moreLink.data('disabled', true);
                }
            } catch (error) {
                console.error(error);
            }
        });

        // ставим куку для просмотренных товаров
        (function setProductViewedCookie(config, _, $){
            var productId = parseInt(config['productId'], 10);
            var viewedProducts = ($.cookie('product_viewed')) ? $.cookie('product_viewed').split(',') : [];

            var result = _.find(viewedProducts, function(id){ return id === productId; });

            if (!result) {
                viewedProducts.push(productId);
                $.cookie('product_viewed', viewedProducts.join(','), { expires: 7 });
            }
        })(config, _, $);

        // добавление в избранное
        (function addToFavorites(config, _, $){
            var $addToFavorites = $('.js-favorites-add');
            var ADDED_TO_FAVORITES_CLASS = 'icon-favorit--on';

            $addToFavorites.click(handleAddToFavoritesClick);


            function handleAddToFavoritesClick(evt) {
                evt.preventDefault();

                var productUi = $(this).data('productUi');

                $.post('/favorite/add', {productUi: productUi}, function(result) {

                    if (result.data.success) {
                        $addToFavorites.addClass(ADDED_TO_FAVORITES_CLASS);
                    }

                });
            }


        })(config, _, $);

// скролл к отзывам
        (function scrollToReviews(config, _, $){
            var $scrollTrigger = $('.js-review-scroll-trigger');
            var $reviewTabNav = $('.js-change-tab[data-block="js-reviews-block"]');

            $scrollTrigger.click(scrollToReviews);

            function scrollToReviews(evt) {
                evt.preventDefault();

                showTab($reviewTabNav);
            }

        })(config, _, $);

        // скролл к характеристика
        (function scrollToSpecs(config, _, $){
            var $scrollTrigger = $('.js-specs-scroll-trigger');
            var $specsTabNav = $('.js-change-tab[data-block="js-description-block"]');

            $scrollTrigger.click(scrollToReviews);

            function scrollToReviews(evt) {
                evt.preventDefault();

                showTab($specsTabNav);
            }

        })(config, _, $);

        function showTab($el) {
            $('html, body').animate({
                scrollTop: $el.offset().top - 100
            }, 500);

            $el.click();
        }

    }
);