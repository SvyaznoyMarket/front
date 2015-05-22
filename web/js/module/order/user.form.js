define(
    [
        'require', 'jquery', 'underscore', 'mustache', 'module/util', 'jquery.maskedinput'
    ],
    function(
        require, $, _, mustache, util
    ) {
        // устанавливаем маску в поле номера телефона
        $.mask.definitions['x'] = "[0-9]";
        $('.js-field-phone').mask("+7(xxx)xxx-xx-xx", {
            placeholder: "+7(xxx)xxx-xx-xx"
        });

        $('.js-field-mnogoru').mask("xxxx xxxx", {
            placeholder: "xxxx xxxx"
        });

        var $field       = $('.js-user-field'),
            $globalError = $('.js-global-error'),
            errClass     = 'textfield-err',
            massage,
            index,
            tmpl,
            i;

        // убираем маркер ошибки при фокусе на поле
        $field.focus(function() {
        	if ( $field.hasClass(errClass) ) {
                $(this).removeClass(errClass);
                $(this)
                    .removeClass(errClass)
                    .closest('.js-user-wrap').find('.js-field-error').remove();
            }
        });

        // запрос прошел успешно
        function successForm( result ) {
            console.log('success form');
            console.log(result);

            // маркируем поля с ошибками
            if ( result.errors.length ) {
                $field.each(function(index) {
                    for ( i = 0; i < result.errors.length; i++ ) {
                        index = result.errors[i].field;
                        massage = result.errors[i].name;

                        if ( $(this).data('field-name') == index && !( $(this).hasClass(errClass) ) ) {
                            tmpl = '<div class="error-text js-field-error">' + massage + '</div>';

                            $(this)
                                .addClass(errClass)
                                .closest('.js-user-wrap').prepend(tmpl);
                        }
                    }
                })
            }

            // если ошибок нет переход на следущий шаг
            if ( result.redirect.length ) {
                window.location.href = result.redirect;
            }

            return false;
        }

        // обработка ошибок запроса
        function errorForm( jqXHR, textStatus, errorThrown ) {
            console.log('error form');
            console.log(jqXHR);
            console.log(textStatus);
            console.log(errorThrown);
        }

        // отправляем запрос с данными пользователя
        $('.js-user-submit').click(function( event ) {
            event.preventDefault();

            // отправка запроса
            var $form = $('.js-user-form'),
            	url   = $form.attr('action');

            $.ajax({
                type: 'POST',
                url: url,
                data: $form.serialize(),
                error: errorForm,
                success: successForm
            });

            return false;
        });
    }
);
