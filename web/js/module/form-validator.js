define(
    [
        'require', 'jquery', 'underscore', 'mustache'
    ],
    function (
        require, $, _, mustache
    ) {
        var
            init = function() {
                $('body').on('blur', '.js-validator-form-field', function(e) {
                    var
                        $field = $(this)
                    ;

                    resetFieldError($field)
                });
            },

            validate = function($form) {
                var
                    isValid = true,
                    message
                ;

                console.info('form', $form);

                $form.find('[required="required"]').each(function(i, el) {
                    var $field = $(el);

                    if (!$field.val().length) { // поле пустое
                        isValid = false;

                        message = 'Поле пустое';
                        showFieldError($field, {message: message});
                    }
                });

                return {
                    isValid: isValid
                };
            },

            showFieldError = function($field, error) {
                console.warn(error, $field);
            },

            resetFieldError = function($field) {
                console.info('reset error, $field', $field);
            }
        ;

        return {
            init: init,
            validate: validate,
            showFieldError: showFieldError,
            resetFieldError: resetFieldError
        };
    }
);