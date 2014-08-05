define(
    [
        'require', 'jquery', 'underscore', 'module/config'
    ],
    function (
        require, $, _, config
    ) {
        var
            handle = function(action, data, $el) {
                window.google_tag_params = data.tagParams || {};
                window.google_conversion_id = 1001659580;
                window.google_conversion_label = "nphXCKzK6wMQvLnQ3QM";
                window.google_custom_params = window.google_tag_params;
                window.google_remarketing_only = true;

                require(['//www.googleadservices.com/pagead/conversion.js']);
            }
        ;

        return {
            handle: handle
        }
    }
);