define(
    [
        'jquery'
    ],
    function($) {
    	var $toggleWrap = $('.js-toggle-wrap'),
    		$toggleLink = $toggleWrap.find('.js-toggle-link'),
    		$toggleBox  = $toggleWrap.find('.js-toggle-box'),
    		toggleClass = 'toggle--active';

    	$toggleWrap.removeClass(toggleClass);

    	$('.js-toggle-link').click( function() {
            console.log('toggle');
    		$(this).closest('.js-toggle-wrap').toggleClass(toggleClass);
    	})
    }
)