define(
    [
    	'jquery', 'jquery.slick'
    ],
    function($) {
    	console.log('product card new');
    	 /**
	     * Переключение табов (описание товара, аксессуары, отзывы и т.д.)
	     *
	     * @method      changetabHandler
	     */
	    var
	    	changetabHandler = function( event ) {
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
		    },

		    fullImages = function( event ) {
		    	var 
		    		content        = $('.js-content'),
		    		fullImagesWrap = $('.js-fullimg-wrap');

		    	console.log('full images');
		    	content.hide(0);
		    	fullImagesWrap.show(0);
		    };

		$('.js-change-tab').on('click', changetabHandler);
		$('.js-fullimg-open').on('click', fullImages);
});
