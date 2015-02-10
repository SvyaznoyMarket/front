define(
    ['jquery', 'mustache', 'module/config', 'jquery.popup', 'jquery.maskedinput'],
    function ($, Mustache, config) {
        var
            $body = $('body'),
            errorCssClass = 'textfield-err',
            popupTemplate =
			'<div class="js-slotButton-popup popup--request" style="position: absolute; z-index: 1035;">' +
				'<a href="#" class="js-slotButton-popup-close popup--request__close" title="Закрыть">Закрыть</a>' +

				'<form action="/orders/slot/create" method="post">' +
					'<input type="hidden" name="productId" value="{{productId}}" />' +
					'<input type="hidden" name="sender" value="{{sender}}" />' +

					'{{#full}}' +
						'<div class="popup--request__head msg--recall">Закажите обратный звонок и уточните</div>' +
						'<ul class="recall-list">' +
							'<li>Состав мебели и техники</li>' +
							'<li>Условия доставки, сборки и оплаты</li>' +
						'</ul>' +
					'{{/full}}' +

					'{{^full}}' +
						'<div class="popup--request__head">Отправить заявку</div>' +
					'{{/full}}' +

					'<div class="js-slotButton-popup-errors" style="display: none;">' +
					'</div>' +

					'<div class="popup__form-group">' +
						'<div class="input-group">' +
							'<label class="label-for-input label-phone">Телефон</label>' +
							'<input type="text" name="phone" value="{{userPhone}}" placeholder="8 (___) ___-__-__" data-mask="8 (xxx) xxx-xx-xx" />' +
						'</div>' +
						'<span class="js-slotButton-popup-error popup__form-group__error" style="display: none">Неверный формат телефона</span>' +
					'</div>' +

					'<div class="popup__form-group">' +
						'<div class="input-group">' +
							'<label class="label-for-input">E-mail</label>' +
							'<input type="text" name="email" value="{{userEmail}}" placeholder="mail@domain.com" />' +
						'</div>' +
						'<span class="js-slotButton-popup-error popup__form-group__error" style="display: none">Неверный формат email</span>' +
					'</div>' +

					'<div class="popup__form-group">' +
						'<div class="input-group">' +
							'<label class="label-for-input">Имя</label>' +
							'<input type="text" name="name" value="{{userName}}" />' +
						'</div>' +
					'</div>' +

					'<div class="popup__form-group checkbox-group"><label><input type="checkbox" name="confirm" value="1" /><i></i> Я ознакомлен и согласен с информацией о продавце и его {{#partnerOfferUrl}}<a class="underline" href="{{partnerOfferUrl}}" target="_blank">{{/partnerOfferUrl}}офертой{{#partnerOfferUrl}}</a>{{/partnerOfferUrl}}</label></div>' +
					'<div class="popup__form-group vendor">Продавец-партнёр: {{partnerName}}</div>' +

					'<div class="btn--container">' +
						'<button type="submit" class="js-slotButton-popup-submitButton btn btn--submit">Отправить заявку</button>' +
					'</div>' +

					'{{#full}}' +
						'<div class="popup__form-group msg--goto-card">' +
							'<a href="{{productUrl}}" class="lnk--goto-card">Перейти в карточку товара</a>' +
						'</div>' +
					'{{/full}}' +
				'</form>' +
			'</div>',

            popupResultTemplate =
                '<div class="popup--request__head msg--send">Ваша заявка № {{orderNumber}} отправлена</div>' +
                '<div class="btn--container">' +
                    '<button type="submit" class="js-slotButton-popup-okButton btn btn--submit">Ок</button>' +
                '</div>',

            validateEmail = function(email) {
                var re = /^(([^<>()[\]\\.,;:\s@\"]+(\.[^<>()[\]\\.,;:\s@\"]+)*)|(\".+\"))@((\[[0-9]{1,3}\.[0-9]{1,3}\.[0-9]{1,3}\.[0-9]{1,3}\])|(([a-zA-Z\-0-9]+\.)+[a-zA-Z]{2,}))$/;
                return re.test(email);
            },

            validate = function($form) {
                var isValid = true,
                    $phoneInput = $('[name="phone"]', $form),
                    $emailInput = $('[name="email"]', $form),
                    parentClass = '.popup__form-group',
                    labelClass = '.input-group';

                if (!/8\(\d{3}\)\d{3}-\d{2}-\d{2}/.test($phoneInput.val().replace(/\s+/g, ''))) {
                    isValid = false;
                    $phoneInput.addClass(errorCssClass).siblings('.js-slotButton-popup-error').show();
                    $phoneInput.parents(parentClass).children(labelClass).addClass('lbl-error');
                } else {
                    $phoneInput.removeClass(errorCssClass).siblings('.js-slotButton-popup-error').hide();
                    $phoneInput.parents(parentClass).children(labelClass).removeClass('lbl-error');
                }

                if ($emailInput.val().length != 0 && !validateEmail($emailInput.val())) {
                    isValid = false;
                    $emailInput.addClass(errorCssClass).siblings('.js-slotButton-popup-error').show();
                    $emailInput.parents(parentClass).children(labelClass).addClass('lbl-error');
                } else {
                    $emailInput.removeClass(errorCssClass).siblings('.js-slotButton-popup-error').hide();
                    $emailInput.parents(parentClass).children(labelClass).removeClass('lbl-error');
                }

                return isValid;
            },

            getFirstObjectProperty = function(object) {
                for (var key in object) {
                    if (!object.hasOwnProperty(key)) {
                        continue;
                    }

                    return object[key]
                }
            };

        $body.on('click', '.js-slotButton', function(e) {
            e.preventDefault();

            var
                $button = $(this),
                data = $button.data('value'),
                product = getFirstObjectProperty(data.product),
                $popup = $(Mustache.render(popupTemplate, {
                    full: true,
                    partnerName: product.partnerName,
                    partnerOfferUrl: product.partnerOfferUrl,
                    productUrl: product.url,
                    productId: product.id,
                    userPhone: '', // TODO подставлять значение текущего пользователя
                    userEmail: '', // TODO подставлять значение текущего пользователя
                    userName: '' // TODO подставлять значение текущего пользователя
                })),
                $form = $('form', $popup),
                $errors = $('.js-slotButton-popup-errors', $form);

            $popup.enterPopup({
                closeClick: false,
                closeSelector: '.js-slotButton-popup-close',
                destroyOnClose: true
            });

            $.mask.definitions['x'] = '[0-9]';
            $.mask.placeholder = "_";
            $.mask.autoclear = false;
            $.map($('input', $popup), function(elem, i) {
                var $elem = $(elem);
                if (typeof $elem.data('mask') !== 'undefined') {
                    $elem.mask($elem.data('mask'));
                }
            });

            $('input', $popup).blur(function(){
                validate($form);
            });

            $('[name="phone"]', $popup).keyup(function(e){
                var val = $(e.currentTarget).val();
                if (val[val.length - 1] != '_') {
                    validate($form);
                }
            });

            $form.submit(function(e) {
                e.preventDefault();

                if (!validate($form)) {
                    return;
                }

                var $submitButton = $('.js-slotButton-popup-submitButton', $form);

                $submitButton.attr('disabled', 'disabled');
                $.ajax({
                    type: 'POST',
                    url: $form.attr('action'),
                    data: $form.serializeArray(),
                    success: function(result){
                        if (result.error) {
                            $errors.text(result.error).show();
                            return;
                        }

                        $form.after($(Mustache.render(popupResultTemplate, {
                            orderNumber: result.orderNumber
                        })));

                        $form.remove();

                        $('.js-slotButton-popup-okButton', $popup).click(function() {
                            $popup.trigger('close');
                        });
                    },
                    error: function(){
                        $errors.text('Ошибка при создании заявки').show();
                    },
                    complete: function(){
                        $submitButton.removeAttr('disabled');
                    }
                })
            });
        });
    }
);