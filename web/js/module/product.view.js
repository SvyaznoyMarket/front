define(
    [
    	'jquery', 'jquery.slick'
    ],
    function($) {
    	console.log('product card new');
    	
	    var
	    	windows        = $(window),
	    	body           = $('body'),
	    	header         = $('.js-header'),
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

	    function fullImages( event ) {
	    	var 
	    		popup       = $('.js-full-images-popup'),
	    		content     = popup.find('.js-full-images-content'),
	    		bigImage    = content.find('.js-full-images'),
	    		thumbs      = popup.find('.js-full-images-thumbs'),
	    		miniImage   = thumbs.find('.js-full-images-thumbs-item'),
	    		activeClass = 'active',
	    		openClass   = 'product-full-images';

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
		    	}
		    }
	    };

		$('.js-change-tab').on('click', changetabHandler);
		$('.js-full-images-open').on('click', fullImagesView.open);
		$('.js-full-images-popup-close').on('click', fullImagesView.close);
		$('.js-full-images-thumbs-item').on('click', fullImagesView.changeImage);
		$(window).on('resize', fullImagesView.resize);
});
