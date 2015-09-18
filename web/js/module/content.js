define(
    [
        'jquery'
    ],
    function (
        $
    ) {
        $('.js-scms-changeRegion').on('click',function(){
            $('.jsSelectCity').click();
        });
    }
);