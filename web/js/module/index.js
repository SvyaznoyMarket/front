define(
    [
        'require', 'jquery', 'underscore', 'mustache', 'jquery.slides', 'jquery.slick'
    ],
    function (
        require, $, _, mustache
    ) {
        var $body = $('body');

		$('.goods-slider-slides').each(function() {
			var current = $(this).data('slick-slider');

			$('.js-main-goods-slider-' + current).slick({
				autoplaySpeed: 2000,
				dots: true,
				infinite: false,
				prevArrow: '.js-main-goods-slider-btn-prev-' + current,
	   			nextArrow: '.js-main-goods-slider-btn-next-' + current,
	   			responsive: [
			    {
			      breakpoint: 480,
			      settings: {
			        slidesToShow: 2,
        			slidesToScroll: 2
			      }
			    }
			  ]
			});
		})
    }
);