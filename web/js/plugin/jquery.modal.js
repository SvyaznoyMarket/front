; (function( $ ){

    $.fn.modal = function( options ) {
    	console.log("modal");

  		return this.each(function() {
  			var options = $.extend(
							{},
							$.fn.enterPopup.defaults,
							options),
				$this  = $(this),
                $html  = $('html'),

                $open  = $('.js-modal-show'),
                $modal = $('.js-modal'),
                $close = $modal.find('.js-modal-close');
			// end of vars

            function showModal() {
                var target = $this.data('modal');

                $html.css({'overflow':'hidden'});
                console.log($modal.filter('[data-modal="' + target + '"]'));
            }

            $open.on('click', showModal);
  		});
    };

    $.fn.modal.defaults = {

	};
    $('.js-modal-show').modal();

})( jQuery );
