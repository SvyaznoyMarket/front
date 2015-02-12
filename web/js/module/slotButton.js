define(
    ['jquery', 'mustache', 'module/config', 'jquery.popup', 'jquery.maskedinput'],
    function ($, Mustache, config) {
        var
            $body = $('body'),
            errorCssClass = 'textfield-err',
            popupTemplate =
			'<div class="js-slotButton-popup popupBox popupBox-bid" style="position: absolute; z-index: 1035;">' +
				'<div class="popupBox_close js-slotButton-popup-close" style="display: block;"></div>' +

				'<div class="popupBox_inn">' +
                    '<form action="/orders/slot/create" method="post">' +
    					'<input type="hidden" name="productId" value="{{productId}}" />' +
    					'<input type="hidden" name="sender" value="{{sender}}" />' +

    					'{{#full}}' +
                            '<div class="popupBox_title">Отправить заявку</div>' +

    						'<ul class="lst-tree">' +
                                '<li class="lst-tree__i lst-tree__i--tl">Закажите обратный звонок и уточните:</li>' +
    							'<li class="lst-tree__i"><i style="background-color: #c1d837" class="lst-tree__bul"></i> состав мебели и техники;</li>' +
    							'<li class="lst-tree__i"><i style="background-color: #c1d837" class="lst-tree__bul"></i> условия доставки, сборки и оплаты.</li>' +
    						'</ul>' +
    					'{{/full}}' +

    					'{{^full}}' +
    						'<div class="popup--request__head">Отправить заявку</div>' +
    					'{{/full}}' +

    					'<div class="js-slotButton-popup-errors" style="display: none;">' +
    					'</div>' +

    					'<div class="popup__form-group">' +
    						'<div class="orderU_fld">' +
    							'<input class="orderU_tx textfield js-slotButton-popup-phone" type="text" name="phone" value="{{userPhone}}" placeholder="8 (___) ___-__-__" data-mask="8 (xxx) xxx-xx-xx" />' +
                                '<label class="orderU_lbl orderU_lbl-str">Телефон</label>' +
    						'</div>' +
    					'</div>' +

    					'<div class="popup__form-group">' +
    						'<div class="orderU_fld">' +
                                '<input class="orderU_tx textfield" type="text" name="email" value="{{userEmail}}" placeholder="mail@domain.com" />' +
    							'<label class="orderU_lbl">E-mail</label>' +
    						'</div>' +
    					'</div>' +

    					'<div class="popup__form-group">' +
    						'<div class="orderU_fld">' +
    							'<label class="orderU_lbl">Имя</label>' +
    							'<input class="orderU_tx textfield" type="text" name="name" value="{{userName}}" />' +
    						'</div>' +
    					'</div>' +

    					'<div class="popupBox-bid__check"><input type="checkbox" class="customInput customInput-checkbox" name="confirm" id="confirm" value="1" /> <label class="customLabel" for="confirm">Я ознакомлен и согласен с информацией о продавце и его {{#partnerOfferUrl}}<a class="underline" href="{{partnerOfferUrl}}" target="_blank">{{/partnerOfferUrl}}офертой{{#partnerOfferUrl}}</a>{{/partnerOfferUrl}}</label></div>' +
    					'<div class="popupBox-bid__vendor">Продавец-партнёр: {{partnerName}}</div>' +

    					'<div class="popupBox-bid__footnote">' +
    						'<button type="submit" class="js-slotButton-popup-submitButton btn6 popupBox-bid__btn">Отправить заявку</button>' +
    					'</div>' +

    					'{{#full}}' +
    						'<div class="popupBox-bid__footnote">' +
    							'<a href="{{productUrl}}" class="lnk--goto-card js-slotButton-popup-close">Перейти в карточку товара</a>' +
    						'</div>' +
    					'{{/full}}' +
    				'</form>'+
                '</div>' +
			'</div>',

            popupResultTemplate =
                '<div class="popupBox_title">Ваша заявка № {{orderNumber}} отправлена</div>' +
                '<div class="popupBox-bid__footnote">' +
                    '<button type="submit" class="js-slotButton-popup-okButton btn6 popupBox-bid__btn">Ок</button>' +
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
                $content = $('.js-content'),
                $contentHidden = $('.js-content-hidden'),
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
                $close = $('.js-slotButton-popup-close', $popup),
                $form = $('form', $popup),
                $errors = $('.js-slotButton-popup-errors', $form),
                $phone = $('.js-slotButton-popup-phone', $form);

            (function() {
                $content.prepend($popup);
                $('html, body').animate({scrollTop: 0}, 'fast');
                $popup.slideDown().show(0);
                $contentHidden.css({'overflow' : 'hidden'}).delay(100).animate({'opacity' : 0, 'height' : 0});

                $close.click(function(e) {
                    e.preventDefault();
                    $popup.slideUp(400, function() {
                        $popup.remove(); // Удаляем, т.к. каждый раз создаётся попап с новыми данными (для нового товара)
                    });
                    $contentHidden.css({'opacity' : 1, 'height' : 'auto', 'overflow' : 'visible'});
                });
            })();

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

            $phone.keyup(function(e){
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

            $phone.focus();
        });
    }
);