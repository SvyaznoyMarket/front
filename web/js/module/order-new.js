define(
    [
        'require', 'jquery', 'underscore', 'mustache', 'module/util', 'module/form-validator', 'module/order/analytics.google', 'jquery.maskedinput', 'module/toggleLink'
    ],
    function(
        require, $, _, mustache, util, formValidator, analytics
    ) {
        var
            $form = $('.js-user-form')
        ;

        // отправляем запрос с данными пользователя
        $form.on('submit',function(event){
            
            event.preventDefault();

            // отправка запроса
            var $form = $('.js-user-form'),
            	url   = $form.attr('action');

            $.ajax({
                type: 'POST',
                url: url,
                data: $form.serialize(),
                error: function() {
                    analytics.push(['6_2 Далее_ошибка_Получатель']);
                },
                success: function(result) {
                    formValidator.validate($form, result.errors);

                    // если ошибок нет переход на следущий шаг
                    if (result.redirect != null && result.redirect.length) {
                        analytics.push(['6_1 Далее_успешно_Получатель_ОБЯЗАТЕЛЬНО']);

                        window.location.href = result.redirect;
                    }

                    try {
                        if (result.errors && result.errors.length) {
                            analytics.push(['6_2 Далее_ошибка_Получатель', 'Поле ошибки: ' + _.map(result.errors, function(error) { return error.field; })]);
                        }
                    } catch (error) { console.error(error); }
                }
            });

        });

        // устанавливаем маску в поле номера телефона
        $.mask.definitions['x'] = "[0-9]";
        $form.find('[data-field="phone"]')
            .mask("+7(xxx)xxx-xx-xx", {
                placeholder: "+7(xxx)xxx-xx-xx"
            })
            .on('focus', function() {
                analytics.push(['1 Телефон_Получатель_ОБЯЗАТЕЛЬНО']);
            })
        ;
        $form.find('[data-field="email"]')
            .on('focus', function() {
                analytics.push(['2 Email_Получатель']);
            })
        ;
        $form.find('[data-field="firstName"]')
            .on('focus', function() {
                analytics.push(['3 Имя_Получатель_ОБЯЗАТЕЛЬНО']);
            })
        ;
        $form.find('[data-field="mnogoru"]')
            .mask("xxxx xxxx", {
                placeholder: "xxxx xxxx"
            })
            .on('focus', function() {
                analytics.push(['4 Начислить_баллы_Получатель']);
            })
        ;
        $('.js-auth-link').on('click', function() {
            analytics.push(['5 Войти_с_паролем_Получатель']);
        });

        formValidator.init();

        analytics.push(['1 Вход_Получатель_ОБЯЗАТЕЛЬНО']);
    }
);
