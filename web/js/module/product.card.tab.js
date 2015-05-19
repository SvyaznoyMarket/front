define(
    ['jquery'],
    function($) {

        var $window = $(window);
        var body = $('body');
        var $tabWrap = $('.js-tab-wrap');
        var $tabMenuItem = $('.js-tabs-item');
        var $tabList = $tabWrap.find('.js-tab');
        var $tab = $tabList.find('.js-cont');
        var $tabsContainer = $('.js-tabs');
        var $moreReviewsButton = $('.js-productReviewList-more');
        var activeTab = 0;

        // event listeners
        $window.on('orientationchange', function(){
            setTimeout(function(){
                setContainerHeight({tabIndex: activeTab});
                scrollTabsMenuIfNotVisible($tabMenuItem.eq(activeTab));
            }, 200);
        });
        $tabMenuItem.on('click', tabChange);
        $moreReviewsButton.on('click', showMoreReviews);

        // init module
        setDataAttributes();
        setFirstTabVisible();
        setContainerHeight();


        function setDataAttributes() {
            var i = 0;

            $tabMenuItem.each(function() {
                $(this).attr({
                    'data-tab': i
                });
                i +=1;
            });

            i = 0;
            $tab.each(function() {
                $(this).attr({
                    'data-desc': "tab-" + i
                });
                i +=1;
            });
        }

        function setFirstTabVisible() {
            $tab.eq(0).css({
                zIndex: 130,
                opacity: 1
            });
        }

        function setContainerHeight(options) {
            var tab;

            if (options && options.tabIndex) {
                tab = $tab.eq(options.tabIndex);
            } else if (options && options.$tab) {
                tab = options.$tab;
            } else {
                tab = $tab.data('desc', 0);
            }

            $tabWrap.css({
                height: tab.height()
            });
        }

        function isTabVisible ($tab) {
            var tab = $tab[0];

            var rect = tab.getBoundingClientRect();

            return (
            rect.top >= 0 &&
            rect.left >= 0 &&
            rect.bottom <= (window.innerHeight || document.documentElement.clientHeight) &&
            rect.right <= (window.innerWidth || document.documentElement.clientWidth)
            );
        }

        function tabChange(event) {

            event.preventDefault();

            var $self = $(this);
            var tabLinkId = $self.data('tab');
            var tabId = $tab.filter('[data-desc="tab-' + tabLinkId + '"]');
            var tabIndex = $tabMenuItem.index($self);

            hideActiveTab($tab.eq(activeTab));
            showTab(tabId);
            scrollTabsMenuIfNotVisible($self);
            toggleHighlightTabMenu($self);
            setContainerHeight({$tab: tabId});

            activeTab = tabIndex;
        }

        function toggleHighlightTabMenu($menuItem) {
            $tabMenuItem.removeClass('productDescTab_item-active');
            $menuItem.addClass('productDescTab_item-active');
        }

        function hideActiveTab($tab) {
            $tab.css({
                zIndex: -1,
                opacity: 0
            });
        }

        function showTab($tab) {
            $tab.css({
                zIndex: 120,
                opacity: 1
            });
        }

        function scrollTabsMenuIfNotVisible($tab) {
            if ( !isTabVisible($tab) ) {
                var tabIndex = $tabMenuItem.index($tab);
                var scrollAmount = 0;

                for (var i = 0; i < tabIndex; i++) {
                    scrollAmount += $tabMenuItem.eq(i).width();
                }

                $tabsContainer.animate({
                    scrollLeft: scrollAmount
                }, 400);
            }
        }

        function showMoreReviews(event) {
            event.preventDefault();

            $tab.on('DOMNodeInserted', function() {
                var $self = $(this);

                $tabWrap.stop(true, true).animate({
                    'height': $self.height()
                });
            });
        }

    }
);
