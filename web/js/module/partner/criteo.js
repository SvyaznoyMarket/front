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
                    data[1].id = $body.data('user').id
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