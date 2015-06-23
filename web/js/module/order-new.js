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

        var $body        = $('body'),
            $field       = $('.js-user-field'),
            $globalError = $('.js-global-error'),
            errClass     = 'textfield-err',
            massage,
            index,
            tmpl,
            i,

            toggleBox = function toggleBox( e ) {
                var
                    $toggleWrap = $('.js-toggle-wrap'),
                    toggleClass = 'toggle--active',
                    isActive,
                    $el = $(e.target)
                ;

                isActive = $el.closest('.js-toggle-wrap').hasClass(toggleClass);

                if ( !isActive ) {
                    $toggleWrap.removeClass(toggleClass);
                    $(this).closest('.js-toggle-wrap').addClass(toggleClass);
                } else {
                    $(this).closest('.js-toggle-wrap').removeClass(toggleClass);
                }
            },

            toggleBoxClose = function toggleBoxClose( e ) {
                var
                    $container  = $('.js-toggle-wrap'),
                    toggleClass = 'toggle--active'
                ;

                if ( !$container.is( e.target ) && $container.has( e.target ).length === 0 ) {
                    $container.removeClass(toggleClass);
                }
            }
        ;
        
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

        $body.on('click', '.js-toggle-link', toggleBox);

        $body.on('click', toggleBoxClose);

        formValidator.init();
    }
);
