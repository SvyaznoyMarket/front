define(
    [
        'jquery', 'underscore', 'mustache'
    ],
    function (
        $, _, mustache
    ) {

        $.fn.modal = function( options ) {
        	console.log("modal");

      		return this.each(function() {
      			var options = $.extend(
    							{},
    							$.fn.enterPopup.defaults,
    							options),
    				$this    = $(this),
                    $html    = $('html'),
                    $body    = $('body'),
                    $overlay = $('.js-modal-overlay'),
                    $tamplate;
    			// end of vars

                //show modal
                function showModal() {
                    $html.css({'overflow':'hidden'});
                    $template = $('#tpl-modalWindow');
                    $body.append(mustache.render($template.html()));

                    console.log($this);
                }

                // close modal
                function destroyModal() {
                    var $modal = $('.js-modal');

                    $modal.remove();
                    $html.css({'overflow':'auto'});
                }

                showModal();
                $body.on('click', '.js-modal-close', destroyModal);
      		});
        };

        $.fn.modal.defaults = {

    	};
    }
);

