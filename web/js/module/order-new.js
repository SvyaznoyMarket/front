define(
    [
        'require', 'jquery', 'underscore', 'mustache', 'module/util', 'module/form-validator', 'jquery.maskedinput'
    ],
    function(
        require, $, _, mustache, util, formValidator
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

        
        // запрос прошел успешно
        function successForm( $form, result ) {
            console.log('success form');
            console.log(result);
            
            formValidator.validate($form, result.errors);


            // если ошибок нет переход на следущий шаг
            if ( result.redirect !=null && result.redirect.length ) {
                window.location.href = result.redirect;
            }
        }

        // обработка ошибок запроса
        function errorForm( $form, result ) {
            console.log('error form');

        }

        // отправляем запрос с данными пользователя
        $('.js-user-form').on('submit',function(event){
            
            event.preventDefault();

            // отправка запроса
            var $form = $('.js-user-form'),
            	url   = $form.attr('action');

            $.ajax({
                type: 'POST',
                url: url,
                data: $form.serialize(),
                error: function(result){ errorForm($form, result); },
                success: function(result){ successForm($form, result); }
            });

        });

        formValidator.init();
    }
);
