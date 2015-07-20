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

        $('#birthday').click(function(){
           $(this).nextAll('#date-helper').click();
        });

    }
);