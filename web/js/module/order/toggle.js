define(
    [
        'require', 'jquery'
    ],
    function($) {
    	var $toggleWrap = $('.js-toggle-wrap'),
    		$toggleLink = $toggleWrap.find('.js-toggle-link'),
    		$toggleBox  = $toggleWrap.find('.js-toggle-box'),
    		toggleClass = 'toggle--active';

    	$toggleWrap.removeClass(toggleClass);

    	$toggleLink.click( function() {
    		$(this).closest('.js-toggle-wrap').toggleClass(toggleClass);
    	})
    }
)