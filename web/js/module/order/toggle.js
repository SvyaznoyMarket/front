define(
    [
        'jquery'
    ],
    function($) {
        var
            $body       = $('body')
        ;

            toggleBox = function toggleBox( e ) {
                var
                    $toggleWrap = $('.js-toggle-wrap'),
                    toggleClass = 'toggle--active',
                    isActive,
                    $el = $(e.target)
                ;

                isActive = $el.closest('.js-toggle-wrap').hasClass(toggleClass);

                if ( !isActive ) {
                    $toggleWrap.removeClass(toggleClass);
                    $(this).closest('.js-toggle-wrap').addClass(toggleClass);
                } else {
                    $(this).closest('.js-toggle-wrap').removeClass(toggleClass);
                }
            },

            toggleBoxClose = function toggleBoxClose( e ) {
                var
                    $container  = $('.js-toggle-wrap'),
                    toggleClass = 'toggle--active'
                ;

                if ( !$container.is( e.target ) && $container.has( e.target ).length === 0 ) {
                    $container.removeClass(toggleClass);
                }
            }
        ;
    	// end of vars

    	$body.on('click', '.js-toggle-link', toggleBox);

        $body.on('click', toggleBoxClose);
    }
)