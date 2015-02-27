define(
    ['jquery', 'mustache', 'module/config', 'jquery.popup', 'jquery.maskedinput'],
    function ($, Mustache, config) {
        var
            $body = $('body'),
            errorCssClass = 'textfield-err',
            popupTemplate =
			'<div class="js-slotButton-popup popupBox popupBox-bid">' +
                '<div class="popupBox_close js-slotButton-popup-close" style="display: block;"></div>' +

                '<div class="popupBox_inn">' +
                    '<form action="/orders/slot/create" method="post">' +
                        '<input type="hidden" name="productId" value="{{productId}}" />' +
                        '<input type="hidden" name="sender" value="{{sender}}" />' +

                        '<div class="popupBox_title">Отправить заявку</div>' +

                        '{{#full}}' +
                            '<ul class="lst-tree">' +
                                '<li class="lst-tree__i lst-tree__i--tl">Закажите обратный звонок и уточните:</li>' +
                                '<li class="lst-tree__i"><i style="background-color: #c1d837" class="lst-tree__bul"></i> комплектность мебели и техники;</li>' +
                                '<li class="lst-tree__i"><i style="background-color: #c1d837" class="lst-tree__bul"></i> условия доставки, сборки и оплаты.</li>' +
                            '</ul>' +
                        '{{/full}}' +

                        '<div class="popupBox-bid__err js-slotButton-popup-errors" style="display: none;"></div>' +

                        '<div class="orderU_fld js-slotButton-popup-element">' +
                            '<input class="orderU_tx textfield js-slotButton-popup-phone" type="text" name="phone" value="{{userPhone}}" placeholder="8 (___) ___-__-__" data-mask="8 (xxx) xxx-xx-xx" />' +
                            '<label class="orderU_lbl orderU_lbl-str">Телефон</label>' +
                            '<span class="js-slotButton-popup-element-error" style="display: none">Неверный формат телефона</span>' +
                        '</div>' +

                        '<div class="orderU_fld js-slotButton-popup-element">' +
                            '<input class="orderU_tx textfield js-slotButton-popup-email" type="text" name="email" value="{{userEmail}}" placeholder="mail@domain.com" />' +
                            '<label class="orderU_lbl">E-mail</label>' +
                            '<span class="js-slotButton-popup-element-error" style="display: none">Неверный формат email</span>' +
                        '</div>' +

                        '<div class="orderU_fld">' +
                            '<label class="orderU_lbl">Имя</label>' +
                            '<input class="orderU_tx textfield" type="text" name="name" value="{{userName}}" />' +
                        '</div>' +

                        '<div class="popupBox-bid__check js-slotButton-popup-element js-slotButton-popup-check"><input type="checkbox" class="customInput customInput-checkbox js-slotButton-popup-confirm" name="confirm" id="confirm" value="1" /> <label class="customLabel" for="confirm">Я ознакомлен и согласен с информацией {{#partnerOfferUrl}}<a class="underline" href="{{partnerOfferUrl}}" target="_blank">{{/partnerOfferUrl}}о продавце и его офертой{{#partnerOfferUrl}}</a>{{/partnerOfferUrl}}</label></div>' +
                        '<div class="popupBox-bid__vendor">Продавец-партнёр: {{partnerName}}</div>' +

                        '<div class="popupBox-bid__footnote">' +
                            '<button type="submit" class="js-slotButton-popup-submitButton btn6 popupBox-bid__btn">Отправить заявку</button>' +
                        '</div>' +

                        '{{#full}}' +
                            '<div class="popupBox-bid__footnote">' +
                                '<a href="{{productUrl}}" class="lnk--goto-card underline">Перейти в карточку товара</a>' +
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

            testEmail = function(email) {
                var re = /^(([^<>()[\]\\.,;:\s@\"]+(\.[^<>()[\]\\.,;:\s@\"]+)*)|(\".+\"))@((\[[0-9]{1,3}\.[0-9]{1,3}\.[0-9]{1,3}\.[0-9]{1,3}\])|(([a-zA-Z\-0-9]+\.)+[a-zA-Z]{2,}))$/;
                return re.test(email);
            },

            showError = function($input) {
                var $element = $input.closest('.js-slotButton-popup-element');
                var $errtarget = ($input.attr('type') == 'checkbox') ? $checkbox : $input;

                //($input.attr('type') == 'checkbox') && ($errtarget = $checkbox);

                $errtarget.addClass(errorCssClass);

                $element.find('.js-slotButton-popup-element-error').show();
            },

            hideError = function($input) {
                var $element = $input.closest('.js-slotButton-popup-element');
                var $errtarget = ($input.attr('type') == 'checkbox') ? $checkbox : $input;

                $errtarget.removeClass(errorCssClass);

                $element.find('.js-slotButton-popup-element-error').hide();
            },

            validatePhone = function($form, disableFail) {
                var $phoneInput = $('.js-slotButton-popup-phone', $form);

                if (!/8\(\d{3}\)\d{3}-\d{2}-\d{2}/.test($phoneInput.val().replace(/\s+/g, ''))) {
                    if (!disableFail) {
                        showError($phoneInput);
                    }

                    return false;
                } else {
                    hideError($phoneInput);
                    return true;
                }
            },

            validateEmail = function($form, disableFail) {
                var $emailInput = $('.js-slotButton-popup-email', $form);

                if ($emailInput.val().length != 0 && !testEmail($emailInput.val())) {
                    if (!disableFail) {
                        showError($emailInput);
                    }

                    return false;
                } else {
                    hideError($emailInput);
                    return true;
                }
            },

            validateConfirm = function($form, disableFail) {
                var $confirmInput = $('.js-slotButton-popup-confirm', $form);

                if (!$confirmInput[0].checked) {
                    if (!disableFail) {
                        showError($confirmInput);
                    }

                    return false;
                } else {
                    hideError($confirmInput);
                    return true;
                }
            },

            validate = function($form) {
                var isValid = true;

                if (!validatePhone($form)) {
                    isValid = false;
                }

                if (!validateEmail($form)) {
                    isValid = false;
                }

                if (!validateConfirm($form)) {
                    isValid = false;
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
                    full: data.isFull,
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
                $phone = $('.js-slotButton-popup-phone', $form),
                $email = $('.js-slotButton-popup-email', $form),
                $confirm = $('.js-slotButton-popup-confirm', $form);
                $checkbox = $('.js-slotButton-popup-check',$form);

            function close() {
                $popup.hide(0, function() {
                    $popup.remove(); // Удаляем, т.к. каждый раз создаётся попап с новыми данными (для нового товара)
                });
                $contentHidden.css({'opacity' : 1, 'height' : 'auto', 'overflow' : 'visible'});
            }

            (function() {
                $content.append($popup);
                $('html, body').animate({scrollTop: 0}, 'fast');
                $popup.show(0);
                $contentHidden.css({'overflow' : 'hidden', 'opacity' : 0, 'height' : 0});

                $close.click(function(e) {
                    e.preventDefault();
                    close();
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

            $phone.blur(function() {
                validatePhone($form);
            });

            $phone.keyup(function() {
                validatePhone($form, true);
            });

            $email.blur(function() {
                validateEmail($form);
            });

            $email.keyup(function() {
                validateEmail($form, true);
            });

            $confirm.click(function() {
                validateConfirm($form, true);
            });

            $form.submit(function(e) {
                e.preventDefault();

                $errors.empty().hide();

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
                            e.preventDefault();
                            close();
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