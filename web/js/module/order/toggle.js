define(
    [
        'jquery'
    ],
    function($) {
        var
            toggleBox = function toggleBox(e) {
                var
                    $el = $(e.target),
                    $toggleWrap = $('.js-toggle-wrap'),
                    toggleClass = 'toggle--active',
                    isActive;
                // end of vars

                isActive = $el.closest('.js-toggle-wrap').hasClass(toggleClass);

                if ( !isActive ) {
                    $toggleWrap.removeClass(toggleClass);
                    $(this).closest('.js-toggle-wrap').addClass(toggleClass);
                } else {
                    $(this).closest('.js-toggle-wrap').removeClass(toggleClass);
                }
            }
    	// end of functions

    	$('body').on('click', '.js-toggle-link', toggleBox);
    }
)