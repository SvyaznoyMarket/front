define(
    ['jquery'],
    function ($) {
        var body = $('body'),
            w = $(window),

            wrap = $('.js-fullimg-wrap'),
            open = $('.js-fullimg-open'),
            closer = $('.js-fullimg-close'),
            hiddenCnt= $('.js-content-hidden'),

            img = $('.js-fullimg-img'),
            imgLoad = $('.fullImgCnt_img_load'),
            thmbW = $('.js-fullimg-thmb-wrap'),
            thmbImg = $('.js-fullimg-thmb-img'),
            actClass = 'fullImgCnt_thmb_i-act';
        // end of vars

            thmbW.css({ 'width' : wrap.width() });
            
            imgLoad.css({ 'width' : wrap.width() });

            $('.js-fullimg-thmb-i').first().addClass(actClass);

        var 
            /*  
             * адаптивная ширина большого изображения
            */
            imgScale = function imgScale() {

                var wh = w.innerHeight();
                var ww = w.innerWidth();
                var dh = thmbW.height();
                // вычисляем vmin
                var cw = (wh > ww) ? wrap.width() : wh - dh*2;
                
                 img.css({'max-height': cw, 'max-width': cw });
                thmbW.css({ 'width' : wrap.width() });
                
                imgLoad.css({ 'height' : cw, 'width' : cw });
            },

            /*  
             * показываем блок большого изображения
            */
            imgShow = function imgShow() {
                $('html, body').animate({scrollTop:0}, 'fast');
        
                wrap.slideDown().show(0);
                
                hiddenCnt.css({'overflow' : 'hidden'}).delay(100).animate({'opacity' : 0, 'height' : 0});
                imgScale();

                return false;
            },

            /*  
             * скрываем блок большого изображения
            */
            imgHide = function imgHide() {
                wrap.slideUp().hide(0);
                hiddenCnt.css({'opacity' : 1, 'height' : 'auto', 'overflow' : 'visible'});
                return false;
            },

            /*  
             * смена большого изображения
            */
            imgSrc = function imgSrc() {
                var $self = $(this),
                    ww = w.width();

                imgLoad.removeClass('nobg');

                if ( ww <= 800 ) {
                    src = $self.data('middleimg');

                    console.log('middle');

                } else {
                    src = $self.data('fullimg');

                    console.log('full');
                }

                thmbImg.parent().parent().removeClass(actClass);
                $self.parent().parent().addClass(actClass);

                img.fadeOut(100, function(){
                    img.attr("src", src).hide().load( function() { img.fadeIn(100) })
                });

                imgLoad.addClass('nobg');
            },

            /*  
             * прокрутка првью изображений товара
            */
            thmbSlide = function thmbSlide() {
                var thmb = $('.js-fullimg-thmb'),
                    thmbW = $('.js-fullimg-thmb-wrap'),
                    item = thmb.find('.js-fullimg-thmb-i'),

                    btnL = $('.js-fullimg-thmb-btn-l'),
                    btnR = $('.js-fullimg-thmb-btn-r'),
                    disblClass = 'fullImgCnt_thmb_btn-disbl',

                    itemW = item.width() + parseInt(item.css('marginLeft'),10) + parseInt(item.css('marginRight'),10),
                    thmbL = item.length,
                    elementOnSlide = parseInt(thmbW.width()/itemW, 10),

                    nowLeft = 0;
                // end of vars

                thmb.css({'width' : itemW * thmbL }); 

                var 
                    /*  
                     * прокрутка вправо
                    */
                    nextSlide = function nextSlide() {
                        if ( $(this).hasClass(disblClass) ) {
                            return false;
                        }

                        btnL.removeClass(disblClass);

                        if ( thmb.width() - ( thmbW.width() + nowLeft ) <= itemW ) {
                            nowLeft = nowLeft + ( thmb.width() - ( thmbW.width() + nowLeft ) );
                            btnR.addClass(disblClass);
                        }
                        else {
                            nowLeft = nowLeft + itemW;
                            btnR.removeClass(disblClass);
                        }

                        thmb.animate({'left': -nowLeft });

                        return false;
                    },
                    /*  
                     * прокрутка влево
                    */
                    prevSlide = function prevSlide() {
                        if ( $(this).hasClass(disblClass) ) {
                            return false;
                        }

                        btnR.removeClass(disblClass);

                        if ( nowLeft - itemW <= 0 ) {
                            nowLeft = 0;
                            btnL.addClass(disblClass);
                        }
                        else {
                            nowLeft = nowLeft - itemW;
                            btnL.removeClass(disblClass);
                        }

                        thmb.animate({'left': -nowLeft });

                        return false;
                    },

                    /*  
                     * резайз слайдера, показ кнопок
                    */
                    resizeW = function resizeW() {
                        nowLeft = 0;

                        btnL.addClass(disblClass);
                        btnR.addClass(disblClass);

                        if ( thmbL > elementOnSlide ) {
                            btnR.removeClass(disblClass);
                        }

                        elementOnSlide = parseInt(thmbW.width()/itemW, 10);
                    };
                // end of functions

                btnL.on('click', prevSlide);
                btnR.on('click', nextSlide);

                resizeW();
                w.on('resize', resizeW);
            };
        // end of functions

        thmbSlide();
        imgScale();
        w.on('resize', imgScale);
        open.on('click', imgShow);
        thmbImg.on('click', imgSrc);
        closer.on('click', imgHide);
    }
);