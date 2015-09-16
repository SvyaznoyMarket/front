define(
    [
    	'jquery', 'hammer', 'jquery.slick'
    ],
    function($, Hammer) {
    	console.log('product card new');

	    var
	    	windows        = $(window),
	    	body           = $('body'),
	    	header         = $('.js-header'),
	    	swipeContent   = $('.js-full-images-content').hammer(),
	    	fullImagesView = fullImages();

	    /**
	     * Переключение табов (описание товара, аксессуары, отзывы и т.д.)
	     *
	     * @method      changetabHandler
	    */
	    function changetabHandler( event ) {
	        var
	        	productTabs = $('.js-product-tabs'),
	            activeClass = 'active',
	            target      = $(event.currentTarget),
	            targetBlock = target.attr('data-block'),

	            tabs        = productTabs.find('.js-change-tab'),
	            blocks      = productTabs.find('.js-tabs-block');
	        // end of vars

	        if ( target.hasClass(activeClass) ) {
	            return;
	        }

	        tabs.removeClass(activeClass);
	        target.addClass(activeClass);
	        blocks.stop(true, true).fadeOut(300).promise().done(function() {
	            blocks.filter('.' + targetBlock).stop(true, true).fadeIn(300);
	        });
	    };

	    /**
	     * Просмотр большого изображения TODO: добаботать анимацию смены картинок
	     *
	     * @method      fullImages
	    */
	    function fullImages( event ) {
	    	var
	    		popup        = $('.js-full-images-popup'),
	    		content      = popup.find('.js-full-images-content'),
	    		bigImage     = content.find('.js-full-images'),
	    		thumbs       = popup.find('.js-full-images-thumbs'),
	    		miniImage    = thumbs.find('.js-full-images-thumbs-item'),
	    		thumbsLength = miniImage.length,
	    		activeClass  = 'active',
	    		openClass    = 'product-full-images';

		    miniImage.eq(0).addClass(activeClass);

	    	return {
	    		open: function() {
	    			windows.scrollTop(0);
			    	body.addClass(openClass);

			    	fullImagesView.resize();
	    		},

	    		close: function() {
	    			windows.scrollTop(0);
	    			body.removeClass(openClass);
	    		},

		    	resize: function() {
		    		var
		    			height = windows.height() - thumbs.height() - header.height();

		    		content.css({
		    			'height' : height - 20,
		    			'line-height' : height - 20 + 'px'
		    		});
		    	},

		    	changeImage: function( event ) {
		    		var
		    			target       = $(event.currentTarget),
		            	targetThumbs = target.attr('data-fullimg');

		           	miniImage.removeClass(activeClass);
		            target.addClass(activeClass);
	            	bigImage.attr('src', targetThumbs);
		    	},

		    	swipeLeft: function( event ) {
		    		miniImage.each(function( index ) {
		                if ( index == thumbsLength - 1 ) {
		                    return false;
		                }

		                if ( $(this).hasClass(activeClass) ) {
		                    bigImage.attr( 'src', miniImage.eq( index + 1 ).attr('data-fullimg') );
		                    miniImage.eq( index + 1 ).addClass(activeClass);
		                    $(this).removeClass(activeClass);

		                    return false;
		                }
		            })
		    	},

		    	swipeRight: function( event ) {
		    		miniImage.each(function( index ) {
		                if ( $(this).hasClass(activeClass) ) {
		                    bigImage.attr( 'src', miniImage.eq( index - 1 ).attr('data-fullimg') );
		                    miniImage.eq( index - 1 ).addClass(activeClass);
		                    $(this).removeClass(activeClass);

		                    return false;
		                }
		            })
		    	}
		    }
	    };

		$('.js-change-tab').on('click', changetabHandler);
		$('.js-full-images-open').on('click', fullImagesView.open);
		$('.js-full-images-popup-close').on('click', fullImagesView.close);
		$('.js-full-images-thumbs-item').on('click', fullImagesView.changeImage);

		swipeContent.on('swipeleft', fullImagesView.swipeLeft);
	    swipeContent.on('swiperight', fullImagesView.swipeRight);

		$(window).on('resize', fullImagesView.resize);
});
