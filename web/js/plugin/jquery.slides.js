(function($) {
    $.fn.slidesbox = function(params) {
        var
            w = $(window);
        // end of vars

        return this.each(function() {
            var
                options = $.extend({},
                    $.fn.slidesbox.defaults,
                    params),

                $self = $(this),

                slides = $self.find(options.slidesSelector),
                item = $self.find(options.itemSelector),
                cont = $self.find(options.slidesContSelector),

                leftBtn = $self.find(options.leftBtnSelector),
                rightBtn = $self.find(options.rightBtnSelector),
                pager = $self.find(options.pagerSelector),

                leftClass = 'slidesImg_item-left',
                centerClass = 'slidesImg_item-center',
                rightClass = 'slidesImg_item-right',

                slidesDataLength = $self.data('value').length,

                // напрвавление прокрутки next => ( direction == 0 ), prev => ( direction == 1 )
                direction = 0,
                // номер передаваемого элемента массива, изначально 0, 1, 2 уже подгружены
                index = 2,
                // id баннера
                id,
                // url ссылки баннера
                itemUrl,
                // адрес картики
                contSrc,

                interval;
            // end of vars

            var
                setData = function setData() {
                    var i = 0;

                    item.each(function() {
                        id = $self.data('value')[i].id;
                        itemUrl = $self.data('value')[i].url;
                        contSrc = $self.data('value')[i].image;

                        $(this).attr('data-ga-click', '{&quot;default&quot;:[&quot;send&quot;,&quot;event&quot;,&quot;m_carousel_click&quot;,&quot;' + id + '&quot;]}');
                        $(this).attr('href', itemUrl);
                        $(this).find(cont).attr('src', contSrc);

                        i++;
                    });
                },

                slidesResize = function slidesResize() {

                    slidesH = $self.find(options.slidesContSelector).height();

                    $self.css({
                        'height': slidesH + 15
                    });
                    slides.css({
                        'height': slidesH
                    });
                },

                nextSlides = function nextSlides() {
                    // if ( index >= slidesDataLength - 1 && direction == 0 || ( slidesDataLength - index ) == 3 ) {
                    //     index = 0;
                    // } else if ( index == slidesDataLength - 1 && direction == 1) {
                    //     index = 2;
                    // } else if ( index == slidesDataLength - 2 && direction == 1) {
                    //     index = 1;
                    // } else {
                    //     if ( direction == 1 ) {
                    //         index = index + 3;
                    //     } else {
                    //         index++;
                    //     }
                    // }

                    // direction = 0;

                    index++;

                    index = Math.round((index/slidesDataLength-Math.floor(index/slidesDataLength))*slidesDataLength);

                    id = $self.data('value')[index].id;
                    itemUrl = $self.data('value')[index].url;
                    contSrc = $self.data('value')[index].image;

                    slidesImgCenter = $self.find('.slidesImg_item-center');
                    slidesImgCenter.removeClass(centerClass).addClass(leftClass);
                    slidesImgCenter.next().removeClass(rightClass).addClass(centerClass);

                    slides.append('<a href="' + itemUrl + '" class="js-ga-click js-slides-img-item slidesImg_item slidesImg_item-right" data-ga-click="{&quot;default&quot;:[&quot;send&quot;,&quot;event&quot;,&quot;m_carousel_click&quot;,&quot;' + id + '&quot;]}"><img src="' + contSrc + '" class="js-slides-img-cont slidesImg_cont"></a>');
                    $('.slidesImg_item-left').prev().remove();

                    pagerCustom();

                    console.log(index)
                },

                prevSlides = function prevSlides() {
                    // if ( index == 2 && direction == 0 || index == 0 && direction == 1) {
                    //     index = slidesDataLength - 1;
                    // } else if ( index == 0 && direction == 0 ) {
                    //     index = slidesDataLength - 3;
                    // } else if ( index == 1 && direction == 0 ) {
                    //     index = slidesDataLength - 2;
                    // } else {
                    //     if ( direction == 0 ) {
                    //         index = index - 3;
                    //     } else {
                    //         index--;
                    //     }
                    // }

                    // direction = 1;

                    console.log(index);

                    index--;

                    index = Math.round((index/slidesDataLength-Math.floor(index/slidesDataLength))*slidesDataLength);

                    id = $self.data('value')[index].id;
                    itemUrl = $self.data('value')[index].url;
                    contSrc = $self.data('value')[index].image;

                    slidesImgCenter = $self.find('.slidesImg_item-center');
                    slidesImgCenter.removeClass(centerClass).addClass(rightClass);
                    slidesImgCenter.prev().removeClass(leftClass).addClass(centerClass);

                    slides.prepend('<a href="' + itemUrl + '" class="js-ga-click js-slides-img-item slidesImg_item slidesImg_item-left" data-ga-click="{&quot;default&quot;:[&quot;send&quot;,&quot;event&quot;,&quot;m_carousel_click&quot;,&quot;' + id + '&quot;]}"><img src="' + contSrc + '" class="js-slides-img-cont slidesImg_cont"></a>');
                    $('.slidesImg_item-right').next().remove();

                    pagerCustom();
                },

                addPager = function addPager() {
                    var pagerHtml = '';

                    sliderPager = $('<div class="js-slides-img-pag slidesImg_pager" />');

                    if (slidesDataLength > 0) {
                        $self.append(sliderPager);
                    };

                    for (var i = 0; i <= slidesDataLength - 1; i++) {
                        pagerHtml += '<div class="js-slides-img-pag-item slidesImg_pager_item" data-slide-index="' + i + '" />';
                    };

                    sliderPager.html(pagerHtml);
                    $self.find(options.pagerSelector).css({
                        'margin-left': -$self.find(options.pagerSelector).width() / 2
                    });
                    pagerCustom();
                },

                pagerCustom = function pagerCustom() {
                    var pagerItem = $self.find('.js-slides-img-pag-item'),
                        pagerItemData = pagerItem.data('slide-index');

                    pagerItem.each(function() {
                        $(this).removeClass('slidesImg_pager_item-active');

                            $(this).addClass('slidesImg_pager_item-active');

                    });
                };
            //end of functions

            setData();
            addPager();
            w.on('load resize', slidesResize);
            rightBtn.on('click', nextSlides);
            leftBtn.on('click', prevSlides);

            $self.touchwipe({
                min_move_x: 20,
                min_move_y: 20,
                wipeLeft: function() {
                    nextSlides();
                },
                wipeRight: function() {
                    prevSlides();
                }
            });

            if ( item.length > 1 ) {
                interval = setInterval(nextSlides, 6000);
            }
        });
    };

    $.fn.slidesbox.defaults = {
        slidesSelector: '.js-slides-img-list',
        itemSelector: '.js-slides-img-item',
        slidesContSelector: '.js-slides-img-cont',

        leftBtnSelector: '.js-slides-img-left',
        rightBtnSelector: '.js-slides-img-right',
        pagerSelector: '.js-slides-img-pag'
    };

    $('.js-slides-img').slidesbox();
})(jQuery);