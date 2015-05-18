define(
    ['jquery', 'jquery.popup'],
    function ($) {
    	console.log('order.common!');

    	var $body = $('body'),
            orderWrapPrent = $('.custom-select');
    		orderWrap = $('.js-order-box'),
    		orderClsr = $('.js-order-box-closer'),
    		orderBtn = $('.js-order-btn'),
    		deliveryType = $('.js-delivery-type-name'),
    		deliveryTypeBox = $('.js-delivery-type');
    	// end of vars

    	var
    		// открываем блоки в оформлении заказа
	    	orderBoxSwitch = function orderBoxSwitch( event ) {
				event.preventDefault();

				var $self = $(this),
					orderBtnData = $self.data('btn'),
					orderBox = orderWrap.filter('[data-box="data-'+orderBtnData+'"]');
					orderBoxNot = orderWrap.not('[data-box="data-'+orderBtnData+'"]');

				orderBoxNot.removeClass('box-show').closest(orderWrapPrent).removeClass('active');

				orderBox.toggleClass('box-show').closest(orderWrapPrent).toggleClass('active');
			},

			// закрываем блоки в оформлении заказа
			orderBoxClose = function orderBoxClose () {
				var $self = $(this);

				$self.closest('.js-order-box').removeClass('box-show');
			},

			// попап оплаты
			showPopupBox = function showPopupBox(e) {
	            e.stopPropagation();

	            $('.js-popup-box').enterPopup({
	            	popupCSS : {'max-width' : 300},
	            	closeSelector: ".popupFl_clsr"
	            });

	            e.preventDefault();
	        },

	        // попап календаря
			showPopupBoxCelendar = function showPopupBoxCelendar(e) {
	            e.stopPropagation();

	            $('.js-popup-box-celendar').enterPopup({
	            	popupCSS : {'max-width' : 308},
	            	closeSelector: ".popupFl_clsr"
	            });
	        },

	        // выбор способа доставки
			changeDeliveryType = function changeDeliveryType() {
				var $self = $(this),
					typeId = $self.attr('data-type');

				deliveryType.removeClass('active');
				deliveryTypeBox.removeClass('box-show').closest(orderWrapPrent).removeClass('active');

				$self.addClass('active');
				$("#"+typeId).addClass('box-show').closest(orderWrapPrent).addClass('active');
			};
		// end of functions

		orderBtn.on('click', orderBoxSwitch);
		orderClsr.on('click', orderBoxClose);
		deliveryType.on('click', changeDeliveryType);

		$body.on('click', '.js-popup-link', showPopupBox);
		$body.on('click', '.js-popup-box-celendar-link', showPopupBoxCelendar);
});