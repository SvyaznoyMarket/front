define(
    [
        'jquery', 'jquery.ui', 'jquery.slick', 'module/toggleLink'
    ],
    function($, Hammer) {
        console.log('product card new');

        var
            windows = $(window),
            body = $('body'),
            header = $('.js-header'),
            swipeContent = $('.js-full-images-content').hammer(),
            fullImagesView = fullImages();

        /**
         * Переключение табов (описание товара, аксессуары, отзывы и т.д.)
         *
         * @method      changetabHandler
         */
        function changetabHandler(event) {
            var
                productTabs = $('.js-product-tabs'),
                activeClass = 'active',
                target = $(event.currentTarget),
                targetBlock = target.attr('data-block'),

                list = productTabs.find('.js-product-tabs-menu'),
                tabs = productTabs.find('.js-change-tab'),
                blocks = productTabs.find('.js-tabs-block'),
                inner = productTabs.find('.js-product-tabs-inner')
                widthW = windows.width(),
                widthEl = 0;
            // end of vars

            tabs.each(function( index ) {
                widthEl = widthEl + tabs.eq(index).outerWidth();

                if ( index == target.index() ) {
                    return false;
                }
            })

            if ( widthEl > widthW ) {
                inner.animate({
                    scrollLeft: Math.abs(widthW - widthEl)
                }, 300);
            } else if ( widthEl == ( tabs.slice(0).outerWidth() + tabs.slice(1).outerWidth() ) ) {
                inner.animate({scrollLeft: 0}, 300);
            }

            if (target.hasClass(activeClass)) {
                return;
            }

            tabs.removeClass(activeClass);
            target.addClass(activeClass);
            blocks.stop(true, true).fadeOut(300).promise().done(function() {
                blocks.filter('.' + targetBlock).stop(true, true).fadeIn(300);
            });
        };

        /**
         * Просмотр большого изображения
         *
         * @method      fullImages
         */
        function fullImages(event) {
            var
                popup = $('.js-full-images-popup'),
                content = popup.find('.js-full-images-content'),
                bigImage = content.find('.js-full-images'),
                thumbs = popup.find('.js-full-images-thumbs'),
                miniImage = thumbs.find('.js-full-images-thumbs-item'),
                thumbsLength = miniImage.length,
                activeClass = 'active',
                openClass = 'product-full-images';

            return {
                open: function() {
                    windows.scrollTop(0);
                    body.addClass(openClass);

                    $('.js-full-images-content').slick({
                        slidesToShow: 1,
                        lazyLoad: 'ondemand',
                        slidesToScroll: 1,
                        arrows: false,
                        dots: false,
                        asNavFor: '.js-full-images-thumbs'
                    });

                    $('.js-full-images-thumbs').slick({
                        slidesToShow: 7,
                        slidesToScroll: 1,
                        lazyLoad: 'ondemand',
                        asNavFor: '.js-full-images-content',
                        dots: false,
                        arrows: true,
                        centerMode: false,
                        centerPadding: 0,
                        focusOnSelect: true,
                        responsive: [{
                            breakpoint: 600,
                            settings: {
                                centerMode: false,
                                slidesToShow: 5
                            }
                        },
                        {
                            breakpoint: 400,
                            settings: {
                                centerMode: false,
                                slidesToShow: 4
                            }
                        }]
                    });

                    fullImagesView.resize();
                },

                close: function() {
                    windows.scrollTop(0);
                    body.removeClass(openClass);
                },

                resize: function() {
                    var
                        height = windows.height() - thumbs.height() - header.height() - $('.top-banner').height();

                    content.css({
                        'height': height - 20,
                        'line-height': height - 20 + 'px'
                    });

                    $('.js-full-images-content div').css({
                        'height': height - 20,
                        'line-height': height - 20 + 'px'
                    });
                },
            }
        };



        $('.js-change-tab').on('click', changetabHandler);
        $('.js-full-images-open').on('click', fullImagesView.open);
        $('.js-full-images-popup-close').on('click', fullImagesView.close);

        $(window).on('resize', fullImagesView.resize);
    });
