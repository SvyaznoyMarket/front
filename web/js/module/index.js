define(
    [
        'require', 'jquery', 'underscore', 'mustache', 'jquery.slides', 'jquery.slick'
    ],
    function (
        require, $, _, mustache
    ) {
        var $body = $('body');

		$('.items-slider-slides').each(function() {
			var
				current = $(this).data('slick-slider');

			$('.js-main-items-slider-' + current)
				.fadeIn()
				.slick({
					dots: true,
					infinite: false,
					prevArrow: '.js-main-items-slider-btn-prev-' + current,
		   			nextArrow: '.js-main-items-slider-btn-next-' + current,
		   			responsive: [
				    {
				      breakpoint: 480,
				      settings: {
				        slidesToShow: 2,
	        			slidesToScroll: 2
				      }
				    }
				  	]
				}).parent().removeClass('m-loader')
		});

		$('.js-main-banner-slider')
			.fadeIn()
			.slick({
				autoplaySpeed: 5000,
				autoplay: true,
				dots: true,
				infinite: true,
				centerMode: true,
				slidesToShow: 1,
	        	slidesToScroll: 1,
	        	focusOnSelect: true,
	        	arrows: false,
	        	centerPadding: '150px',
	        	responsive: [
				    {
				      breakpoint: 900,
				      settings: {
			            centerPadding: '50px'
				      }
				    },
				    {
				      breakpoint: 750,
				      settings: {
				      	centerMode: false
				      }
				    }
			    ]
			}).parent().removeClass('m-loader');
    }
);