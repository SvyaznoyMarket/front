define(
    [
        'require', 'jquery', 'underscore', 'mustache', 'jquery.slides', 'jquery.slick'
    ],
    function (
        require, $, _, mustache
    ) {
        var $body = $('body');

		initializeBannerSlider();
		initializeProductSliders();

		function initializeBannerSlider() {
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

		function initializeProductSliders() {
			appendSliders(collectRecommendationUrls());


			function collectRecommendationUrls() {
				var recommendedUrls = [];
				$('.js-productSlider').each(function(i, el) {
					var
						url = $(el).data('url');

					if (!url) return;

					recommendedUrls.push(url);
				});
				recommendedUrls = _.uniq(recommendedUrls);

				return recommendedUrls;
			}

			function appendSliders(recommendedUrls) {
				var
					$sliders = $('.items-slider-slides');

				_.each(recommendedUrls, function(url) {
					$.get(url).done(function(response) {
						_.each(response.result.widgets, function(templateData, widgetSelector) {
							if (!_.isObject(templateData) || !widgetSelector) {
								console.warn('slider', widgetSelector, templateData);
								return;
							}

							var
								$widget = $(widgetSelector);


							if (templateData.count <= 0 && $widget) {
								$widget.parents('.grid-parent-row').addClass('grid-1cols').removeClass('grid-2cols');
								$widget.remove();
								return;
							}

							console.info('slider', templateData, $widget);


							var $parent = $widget.parents('.js-container');
							if ($parent.length) {
								$parent.show();
							}

							$widget.trigger('render', templateData);
							$body.trigger('render');
						});

						$sliders.each(function() {
							var
								dataGa        = $(this).parents('.js-productSlider').data('ga'),
								current       = $(this).data('slick-slider'),
								responsToShow = $(this).data('respons-show');

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
												slidesToShow: responsToShow,
												slidesToScroll: responsToShow
											}
										}
									]
								}).parent().removeClass('m-loader');

							$(this).parents('.items-slider').find('.items-slider-nav__btn').data('gaClick', dataGa);
						});
					});
				});
			}
		}
    }
);