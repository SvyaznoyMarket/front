define(
    [
        'require', 'jquery', 'underscore', 'mustache', 'module/util', 'jquery.maskedinput'
    ],
    function(
        require, $, _, mustache, util
    ) {
        console.log('order');

        // устанавливаем маску в поле номера телефона
        $.mask.definitions['x'] = "[0-9]";
        $('.js-field-phone').mask("+7(xxx)xxx-xx-xx", {
            placeholder: "+7(xxx)xxx-xx-xx"
        });

        $('.js-field-mnogoru').mask("xxxx xxxx", {
            placeholder: "xxxx xxxx"
        });
    };
);
