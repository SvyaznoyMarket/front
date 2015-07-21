define(
    [
        'jquery', 'underscore', 'jquery.maskedinput'
    ],
    function (
        $, _
    ) {
        var
            $controlGroup = $('.p-control-group'),
            $input = $('.p-control-input'),
            $label = $('.p-control-label'),

            showLabel = function showLabel(){
                var $this = $(this);
                console.log('checked');

                if ( $this.val() == ''){
                    $this.parent('.p-control-group:not(.p-control-select-group)').find('.p-control-label').removeClass('visible');
                } else {
                    $this.parent('.p-control-group:not(.p-control-select-group)').find('.p-control-label').addClass('visible');
                }
            }
        ;

        $.each($input, showLabel);

        $input.keyup(showLabel);


        // хаки для даты
        var $birthdayTextInput = $('#birthday');
        var $birthdayDateInput = $('#birthday-helper');

        function handleBirthdayTextFocus() {
            $birthdayDateInput.focus();
            $birthdayDateInput.css({
                zIndex: 10
            });

            $birthdayTextInput.css({
                zIndex: -1
            });
        }

        function handleBirthdayDateChange() {
            // не уверен насчет формата в разных браузерах, в вебките так
            setDateForBirthdayTextInput( $(this).val().split('-').reverse().join('.') )
        }

        function setDateForBirthdayTextInput(val) {
            $birthdayTextInput.css({
                zIndex: 10
            });

            $birthdayDateInput.css({
                zIndex: -1
            });

            $birthdayTextInput.val(val);
        }

        $birthdayTextInput.focus(handleBirthdayTextFocus);
        $birthdayDateInput.change(handleBirthdayDateChange);
    }
);