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
    				$this     = $(this),
                    $html     = $('html'),
                    $body     = $('body'),
                    $position = $this.data('modal-position'),
                    $title    = $this.data('modal-title'),
                    $tamplate,
                    data = {};
    			// end of vars

                //show modal
                function show() {
                    $html.css({'overflow':'hidden'});
                    $template = $('#tpl-modalWindow');

                    data = {
                        position: $position,
                        title: $title
                    }

                    $body.append(mustache.render( $template.html(), data ));

                    var $modal    = $('.js-modal'),
                        $overlay  = $('.js-modal-overlay'),
                        $content  = $('.js-modal-content');

                    $overlay.show();
                }

                // close modal
                function destroy() {
                    var $modal    = $('.js-modal'),
                        $overlay  = $('.js-modal-overlay');

                    $modal.remove();
                    $overlay.remove();
                    $html.css({'overflow':'auto'});
                }

                show();
                $body.on('click', '.js-modal-close', destroy);
      		});
        };

        $.fn.modal.defaults = {

    	};
    }
);

