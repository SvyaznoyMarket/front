define(
    [
        'require', 'jquery', 'underscore', 'module/util'
    ],
    function(
        require, $, _, util
    ) {
        var
            push = function(data) {
                if (typeof _gaq === 'object') {
                    console.info('_gaq.push', ['_trackEvent', 'Воронка_m.enter'].concat(data));
                    _gaq.push.apply(_gaq, ['_trackEvent', 'Воронка_m.enter'].concat(data));
                }
                if (typeof ga === 'function') {
                    console.info('ga', ['send', 'event', 'Воронка_m.enter'].concat(data));
                    ga.apply(window, ['send', 'event', 'Воронка_m.enter'].concat(data));
                }
            }
        ;

        return {
            push: push
        };
    }
);
