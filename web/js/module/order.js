define(
    [
        'require', 'jquery', 'underscore', 'mustache', 'module/util'
    ],
    function (
        require, $, _, mustache, util
    ) {
    	console.log('order');
        var $body = $('body');

        $('.js-order-user-submit').click( function() {
        	var userForm      = $('.js-order-user-form'),
        		userFormField = $('.js-order-user-field'),
        		index,
        		massage;

        	userFormField.each( function ( index ) {
        		for( var i = 0; i < userForm.data('error').length; i++ ) {
	        		index = userForm.data('error')[i].field;
	        	}
        	})
        });
    }
);