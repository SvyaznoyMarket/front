define(
    ['jquery'],
    function ($) {
    	console.log('order.common!');

    	var orderWrap = $('.js-order-box'),
    		orderClsr = $('.js-order-box-closer'),
    		orderBtn = $('.js-order-btn'),
    		deliveryType = $('.js-delivery-type-name'),
    		deliveryTypeBox = $('.js-delivery-type');

    	var orderBoxSwitch = function orderBoxSwitch( event ) {
			event.preventDefault();

			var $self = $(this),
				orderBtnData = $self.data('btn'),
				orderBox = orderWrap.filter('[data-box="data-'+orderBtnData+'"]');
				orderBoxNot = orderWrap.not('[data-box="data-'+orderBtnData+'"]');

			orderBoxNot.removeClass('box-show');
			orderBox.toggleClass('box-show');
		},

		orderBoxClose = function orderBoxClose () {
			var $self = $(this);

			$self.closest('.js-order-box').removeClass('box-show');
		},

		changeDeliveryType = function changeDeliveryType() {
			var $self = $(this),
				typeId = $self.attr('data-type');

			deliveryType.removeClass('orderCol_mode_i-act');
			deliveryTypeBox.removeClass('box-show');

			$self.addClass('orderCol_mode_i-act');
			$("#"+typeId).addClass('box-show');		
		};

		orderBtn.on('click', orderBoxSwitch);
		orderClsr.on('click', orderBoxClose);
		deliveryType.on('click', changeDeliveryType);
}); 