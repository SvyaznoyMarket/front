define(
    [
        'jquery', 'underscore', 'jquery.maskedinput'
    ],
    function (
        $, _
    ) {
        var
            $input = $('.p-control-input'),

            showLabel = function showLabel(){
                var $this = $(this);

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
        var $birthdayTextInput = $('.js-birthday-input');
        var $birthdayDateInput = $('.js-birthday-input-helper');

        function handleBirthdayTextFocus() {
            $birthdayDateInput.css({
                zIndex: 10
            });

            $birthdayTextInput.css({
                zIndex: -1
            });

            $birthdayDateInput.focus();
            $birthdayDateInput.click();
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
        $birthdayTextInput.click(handleBirthdayTextFocus);
        $birthdayDateInput.change(handleBirthdayDateChange);
        $('#mobile').mask("+7 (999) 999-99-99");
        $('#phone').mask("+7 (999) 999-99-99");

        //hack for inputs cursor on iOS
        $(".private section").scroll(function () {
            var selected = $(this).find("input:focus");
            selected.blur();
            $('body').removeClass('fixfixed');
        });

        $(".private section").find('input').on('focus',function(){
            var scroll = $(this).offset().top;
            $(this).scrollTop(scroll);
        });


    }
);