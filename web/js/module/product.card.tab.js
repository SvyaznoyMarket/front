define(
    ['jquery'],
    function($) {

        var body = $('body'),

            tabWrap = $('.js-tab-wrap'),
            tabWrapWidth = $('.js-tab-selector').width(),
            tabItem = $('.js-tabs-item'),
            posLeft = 0,

            tabList = tabWrap.find('.js-tab'),
            tab = tabList.find('.js-cont'),
            tabCount = tab.length,

            $w = $(window),
            $wwidth = $w.width();
        // end of vars

        var
        /*
         * Добавление атрибутов к элементам описания товара
         */
            addData = function addData() {
                //добавляем атрибут к табу data-tab
                var i = 0;
                tabItem.each(function() {
                    var $self = $(this);

                    $self.attr({
                        'data-tab': i
                    })

                    i += 1;
                });

                //добавляем атрибут к контенту таба
                tab.each(function() {
                    var $self = $(this);

                    $self.attr({
                        'data-desc': "tab-" + i
                    })

                    i += 1;
                });
            },

            /*
             * Пересчет высоты/ширины контента табов
             */
            tabsToggle = function tabsToggle() {
                tabWrapWidth = $('.js-tab-selector').width();

                tab.css({
                    'width': tabWrapWidth
                });

                tabWrap.css({
                    'width': tabWrapWidth,
                    'height': tab.data( "desc", 0 ).height()
                });

                tabList
	                .css({
	                    'width': tabWrapWidth * tabCount
	                })
	                .stop(true, true).animate({
	                    'left': 0
	                });

                tabItem
                	.removeClass('productDescTab_item-active')
                	.first().addClass('productDescTab_item-active');
            },

            /*
             * Слайдинг табов
             */
            tabsSlide = function tabsSlide(event) {

                event.preventDefault();

                var $self = $(this),
                    tabLinkId = $self.data('tab'),
                    tabId = tab.filter('[data-desc="tab-' + tabLinkId + '"]');

                if (tabLinkId == 0) {
                    posLeft = 0;
                } else {
                    posLeft = tabWrapWidth * tabLinkId;
                }

                $('html,body').animate({
                        scrollTop: $self.offset().top - $('.header').outerHeight()
                    }, 400,
                    function() {
                        $('html,body').clearQueue();
                    }
                );

                tabItem.removeClass('productDescTab_item-active');
                $self.addClass('productDescTab_item-active');
                tabList.stop(true, true).animate({
                    'left': -posLeft
                });
                tabWrap.stop(true, true).animate({
                    'height': tabId.height()
                });
            };
        //end of function

        addData();
        tabsToggle();
        tabItem.on('click', tabsSlide);

        /*
         * пересчет высоты блока отзывов при нажатии кнопки "еще отзывы"
         */

        $('.js-productReviewList-more').on('click', function(e) {
            e.preventDefault();

	        tab.on('DOMNodeInserted', function() {
	            var $self = $(this);

	            tabWrap.stop(true, true).animate({
	                'height': $self.height()
	            });
	        });
	    });

        /*
         * Проверка изменения ширины страницы
         */
        $w.on('resize', function() {
            if ($(this).width() != $wwidth) {
                tabsToggle();
            }
        });
    }
);
