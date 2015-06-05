(function($) {

    $.fn.lightbox_me = function(options) {
        console.log('lightbox_me')

        return this.each(function() {

            var
                opts = $.extend({}, $.fn.lightbox_me.defaults, options),
                $body = $('body'),
                $overlay = $(),
                $self = $(this),
                $iframe = $('<iframe id="foo" style="border: none; margin: 0; padding: 0; position: absolute; width: 100%; height: 100%; top: 0; left: 0; filter: mask();"/>');

            if (opts.showOverlay) {
                //check if there's an existing overlay, if so, make subequent ones clear
               var $currentOverlays = $(".js_lb_overlay:visible");
                if ($currentOverlays.length > 0){
                    $overlay = $('<div class="lb_overlay_clear js_lb_overlay"/>');
                } else {
                    $overlay = $('<div class="' + opts.classPrefix + '_overlay js_lb_overlay"/>');
                }
            }

            /*----------------------------------------------------
               DOM Building
            ---------------------------------------------------- */
            $body.append($self.hide()).append($overlay);


            /*----------------------------------------------------
               Overlay CSS stuffs
            ---------------------------------------------------- */

            // set css of the overlay
            if (opts.showOverlay) {
                setOverlayHeight(); // pulled this into a function because it is called on window resize.
                $overlay.css({ position: 'absolute', width: '100%', top: 0, left: 0, right: 0, bottom: 0, display: 'none', zIndex: 1015 });
                if (!$overlay.hasClass('lb_overlay_clear')){
                    $overlay.css(opts.overlayCSS);
                }
            }

            /*----------------------------------------------------
               Animate it in.
            ---------------------------------------------------- */
               //
            if (opts.showOverlay) {
                $overlay.fadeIn(opts.overlaySpeed, function() {
                    $self[opts.appearEffect](opts.lightboxSpeed, function() { setOverlayHeight(); opts.onLoad()});
                });
            } else {
                $self[opts.appearEffect](opts.lightboxSpeed, function() { opts.onLoad()});
            }

            /*----------------------------------------------------
               Hide parent if parent specified (parentLightbox should be jquery reference to any parent lightbox)
            ---------------------------------------------------- */
            if (opts.parentLightbox) {
                opts.parentLightbox.fadeOut(200);
            }

            if (opts.modal) {
                $body.addClass('full-screen');
            }

            if (opts.fullScreen) {
                detectWidth();
                $(window).on('resize', detectWidth);
            }


            /*----------------------------------------------------
               Bind Events
            ---------------------------------------------------- */

            $(window).resize(setOverlayHeight);

            $(window).on('keyup.lightbox_me', observeKeyPress);

            if (opts.closeClick) {
                $overlay.click(function(e) { closeLightbox(); e.preventDefault; });
            }
            $self.on('click', opts.closeSelector, function(e) {
                closeLightbox(); e.preventDefault();
            });
            $self.on('close', closeLightbox);


            /*----------------------------------------------------
               Private Functions
            ---------------------------------------------------- */

            // если ширина экрана больше 639 пиксел
            function detectWidth() {
                var
                    currentWidth = $(window).width()
                ;

                if ( currentWidth < 639 ) {
                    $body.addClass('full-screen');
                } else {
                    $body.removeClass('full-screen');
                }
            }

            /* Remove or hide all elements */
            function closeLightbox() {
                var s = $self[0].style;
                opts.beforeClose();
                if (opts.destroyOnClose) {
                    $self.add($overlay).remove();
                } else {
                    $self.add($overlay).hide();
                }

                $iframe.remove();
                $body.removeClass('full-screen');

                        // clean up events.
                $self.undelegate(opts.closeSelector, "click");
                $self.unbind('close', closeLightbox);

                $(window).unbind('resize', setOverlayHeight);
                $(window).unbind('keyup.lightbox_me');
                opts.onClose();
            }


            /* Function to bind to the window to observe the escape/enter key press */
            function observeKeyPress(e) {
                if((e.keyCode == 27 || (e.DOM_VK_ESCAPE == 27 && e.which==0)) && opts.closeEsc) closeLightbox();
            }


            /* Set the height of the overlay
                    : if the document height is taller than the window, then set the overlay height to the document height.
                    : otherwise, just set overlay height: 100%
            */
            function setOverlayHeight() {
                if ($(window).height() < $(document).height()) {
                    $overlay.css({height: $(document).height() + 'px'});
                     $iframe.css({height: $(document).height() + 'px'});
                } else {
                    $overlay.css({height: '100%'});
                }
            }
        });
    };

    $.fn.lightbox_me.defaults = {

        // animation
        appearEffect: "fadeIn",
        appearEase: "",
        overlaySpeed: 250,
        lightboxSpeed: 300,

        // close
        closeSelector: ".js-modal-close",
        closeClick: true,
        closeEsc: true,

        // behavior
        destroyOnClose: true,
        showOverlay: true,
        fullScreen: false,
        modal: true,

        // callbacks
        onLoad: function() {},
        onClose: function() {},
        beforeClose: function() {},

        // style
        classPrefix: 'lb',
        overlayCSS: {background: 'black', opacity: .3}
    }
})(jQuery);
