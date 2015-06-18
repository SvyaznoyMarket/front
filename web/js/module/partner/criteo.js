define(
    [
        'require', 'jquery', 'underscore', 'module/config'
    ],
    function (
        require, $, _, config
    ) {
        var $body = $('body'),

            handle = function(action, data, $el) {
                if (data[1] && ('{userId}' == data[1].id)) {
                    var user = $body.data('user');
                    data[1].id = user ? user.id : null;
                }

                window.criteo_q = window.criteo_q || [];
                window.criteo_q.push(data);

                console.info('partner', 'criteo', 'criteo_q', window.criteo_q);

                require(['//static.criteo.net/js/ld/ld.js']);
            }
        ;

        return {
            handle: handle
        }
    }
);