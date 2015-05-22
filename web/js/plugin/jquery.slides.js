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

                slidesLength = $self.data('value').length,

                // номер передаваемого элемента массива
                index = 0,

                // id баннера
                ui,
                // url ссылки баннера
                itemUrl,
                // адрес картики
                contSrc,

                timeoutId;
            // end of vars

            var
                setData = function setData() {
                    var slidesData = $self.data('value'),
                        i = 0;

                    //перемещаем последний элемент в начало массива
                    slidesData.unshift(slidesData[slidesLength-1]);

                    //заполняем слайдер данными
                    item.each(function() {
                        ui = slidesData[i].ui;
                        itemUrl = slidesData[i].url;
                        contSrc = slidesData[i].image;

                        $(this).attr('data-ga-click', '{&quot;default&quot;:[&quot;send&quot;,&quot;event&quot;,&quot;m_carousel_click&quot;,&quot;' + ui + '&quot;]}');
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
                    index++;

                    index = Math.round( ( index / slidesLength - Math.floor( index / slidesLength ) ) * slidesLength );
                    indexLeft = Math.round( ( ( index + 2 ) / slidesLength - Math.floor( ( index + 2 ) / slidesLength ) ) * slidesLength );

                    ui = $self.data('value')[indexLeft].ui;
                    itemUrl = $self.data('value')[indexLeft].url;
                    contSrc = $self.data('value')[indexLeft].image;

                    slidesImgCenter = $self.find('.slidesImg_item-center');
                    slidesImgCenter.removeClass(centerClass).addClass(leftClass);
                    slidesImgCenter.next().removeClass(rightClass).addClass(centerClass);

                    slides.append('<a href="' + itemUrl + '" class="js-ga-click js-slides-img-item slidesImg_item slidesImg_item-right" data-ga-click="{&quot;default&quot;:[&quot;send&quot;,&quot;event&quot;,&quot;m_carousel_click&quot;,&quot;' + ui + '&quot;]}"><img src="' + contSrc + '" class="js-slides-img-cont slidesImg_cont"></a>');
                    $('.slidesImg_item-left').prev().remove();

                    pagerCustom();
                },

                prevSlides = function prevSlides() {
                    index--;
                    index = Math.round( ( index / slidesLength - Math.floor( index / slidesLength ) ) * slidesLength );

                    ui = $self.data('value')[index].ui;
                    itemUrl = $self.data('value')[index].url;
                    contSrc = $self.data('value')[index].image;

                    slidesImgCenter = $self.find('.slidesImg_item-center');
                    slidesImgCenter.removeClass(centerClass).addClass(rightClass);
                    slidesImgCenter.prev().removeClass(leftClass).addClass(centerClass);

                    slides.prepend('<a href="' + itemUrl + '" class="js-ga-click js-slides-img-item slidesImg_item slidesImg_item-left" data-ga-click="{&quot;default&quot;:[&quot;send&quot;,&quot;event&quot;,&quot;m_carousel_click&quot;,&quot;' + ui + '&quot;]}"><img src="' + contSrc + '" class="js-slides-img-cont slidesImg_cont"></a>');
                    $('.slidesImg_item-right').next().remove();

                    pagerCustom();
                },

                addPager = function addPager() {
                    var pagerHtml = '';

                    sliderPager = $('<div class="js-slides-img-pag slidesImg_pager" />');

                    if (slidesLength > 0) {
                        $self.append(sliderPager);
                    };

                    for (var i = 0; i <= slidesLength - 1; i++) {
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

                        if ( index == $(this).data('slide-index') ) {
                             $(this).addClass('slidesImg_pager_item-active');
                        }
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
                    clearTimeout(timeoutId);
                },
                wipeRight: function() {
                    prevSlides();
                    clearTimeout(timeoutId);
                }
            });

            if ( item.length > 1 ) {
                timeoutId = setInterval(nextSlides, 6000);
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