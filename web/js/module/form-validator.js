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

                    resetFieldError($field);
                });
            },
            validate = function($form, data){
                _.each(data, function(item, i){
                    showFieldError($form.find('[data-field="'+item.field+'"]'), item);
                });

            },

            validateRequired = function($form) {
                var
                    isValid = true,
                    message
                ;

                console.info('form', $form);

                $form.find('[required]').each(function(i, el) {
                    var $field = $(el);
                    var message = '';
                    //console.log($field);

                    if (!$field.val().length) { // поле пустое
                        isValid = false;
                        //если поле имеет параметр no-msg - вывод сообщения об ошибке не требуется.
                        if (typeof nomsg === typeof undefined || nomsg === false) {
                            message = $field.data('requiredMessage') || 'Поле пустое';
                        }

                        showFieldError($field, {name: message});
                    }
                });

                return {
                    isValid: isValid
                };
            },

            showFieldError = function($field, error) {
                //console.log(error, $field);
                var $parent = $field.parent().addClass('error');
                //выводим сообщение об ошибке только если его еще нет.
                if (!$parent.parent().find('label.error').length){
                    $parent.parent().append('<label class="error">'+error.name+'</label>');
                }

            },

            resetFieldError = function($field) {
                //console.info('reset error', $field);
                $field.parent().removeClass('error').parent().find('label.error').remove();

            }
        ;

        return {
            init: init,
            validate: validate,
            validateRequired: validateRequired,
            showFieldError: showFieldError,
            resetFieldError: resetFieldError
        };
    }
);