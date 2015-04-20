; (function( $ ){

  var curSlide = 0,
      body = $('body'),
      slideWrap = $('.js-slider-wpar'),
      slideWrapWidth = slideWrap.width(),
      slideList = $('.js-slider-list'),
      slideWrapItem = slideList.find('.js-slider-list-item'),
      countItem = slideWrapItem.length,

      slidePag = $('.js-slider-pag'),

      btnSlidesLeft = $('.js-slider-btn-left'),
      btnSlidesRight = $('.js-slider-btn-right'),

      windowWidth = $(window).width(),
      windowHeight = $(window).height();
  // end of vars

  var
      /*
       * Функция проверки необходимости ресайза
       */
      checkResizeNeeding = function() {
          if ($(window).width() != windowWidth && $(window).height() != windowHeight) {
              windowWidth = $(window).width();
              windowHeight = $(window).height();

              resizeSlides();
          }
      },
      /*
        * Функция ресайза блока слайдера изображений товара
       */
      resizeSlides = function resizeSlides() {
        slideWrapItem.css({'display' : 'block', 'float' : 'left'});

        var slideWrapHeight = 350,
            slideImg = slideWrapItem.find('.js-slider-list-img');
        // end of vars

        slideWrapWidth = $('.js-slider-wpar').width();

        if ( slideWrapWidth < 360 ) {
            slideWrapHeight = slideWrapWidth;
        };

        slideList.css({'width' : slideWrapWidth * countItem});
        slideWrapItem.css({'width' : slideWrapWidth});

        //скрываем кнопки и пагинатор, если слайдер имеет один элемент
        if ( countItem <= 1 ) {
            btnSlidesLeft.hide();
            btnSlidesRight.hide();
        } else {
          btnSlidesRight.show();
          slidePag.show();
        };

        var slideListLeftNew = -1 * slideWrapWidth * curSlide;

        slideList.css({'left' : slideListLeftNew});
      },

      /*
        * Пагинация слайдера
       */
      paginationSlides = function paginationSlides() {

        if ( countItem > 1 ) {
            for ( var i = 1; i <= countItem; i++) {
              slidePag.append('<li class="slider_pag_i"></li>')
            };
          }

        $('.slider_pag_i').first().addClass('slider_pag_i-act');
      },

      /*
        * Функция прокрутки вправо блока слайдера изображений товара
       */
      nextSlides = function nextSlides() {
        curSlide++;

        var slideListLeft = $('.js-slider-list').css('left'),
            slideListLeftNew = -1 * slideWrapWidth * curSlide,

            slidePag = $('.js-slider-pag'),
            slidePagItemActive = slidePag.find('.slider_pag_i-act'),
            pagActive = 'slider_pag_i-act';

        if( curSlide >= (countItem - 1) ) {
          $('.js-slider-btn-right').hide();
        }

        if( curSlide <= (countItem - 1) ) {
          slideList.stop(true, true).animate({'left' : slideListLeftNew});

          slidePagItemActive.removeClass(pagActive);
          slidePagItemActive.next().addClass(pagActive);
        }

        if( curSlide > 0 ) {
          $('.js-slider-btn-left').show();
        }
      },

      /*
        * Функция прокрутки влево блока слайдера изображений товара
       */
      prevSlides = function prevSlides() {
        curSlide--;

        var slideListLeft = $('.js-slider-list').css('left'),
            slideListLeftNew = -1 * slideWrapWidth * curSlide,

            slidePag = $('.js-slider-pag'),
            slidePagItemActive = slidePag.find('.slider_pag_i-act'),
            pagActive = 'slider_pag_i-act';

        if ( curSlide <= 0 ) {
            $('.js-slider-btn-left').hide();
        }

        if( curSlide >= 0 ) {
          slideList.stop(true, true).animate({'left' : slideListLeftNew});

          slidePagItemActive.removeClass(pagActive);
          slidePagItemActive.prev().addClass(pagActive);
        }

        if( curSlide < (countItem - 1) ) {
          $('.js-slider-btn-right').show();
        }
      };
  // end of vars

  $('.js-slider-wpar').touchwipe({
    wipeLeft : function() {
      if ( curSlide < countItem - 1 ) {
        nextSlides();
      }
    },
    wipeRight : function() {
      if ( curSlide > 0 ) {
        prevSlides();
      }
    }
  });

  $(window).on('resize', checkResizeNeeding);
  resizeSlides();

  btnSlidesRight.on('click', nextSlides);
  btnSlidesLeft.on('click', prevSlides);

  paginationSlides();
})( jQuery );