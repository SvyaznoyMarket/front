define(
    [
        'require', 'jquery', 'underscore', 'module/util'
    ],
    function(
        require, $, _, util
    ) {
        var
            push = function(data) {
                if (typeof window._gaq === 'object') {
                    console.info('_gaq.push', ['_trackEvent', 'Воронка_m.enter'].concat(data));
                    window._gaq.push(['_trackEvent', 'Воронка_m.enter'].concat(data));
                }
                if (typeof window.ga === 'function') {
                    console.info('ga', ['send', 'event', 'Воронка_m.enter'].concat(data));
                    window.ga.apply(this, ['send', 'event', 'Воронка_m.enter'].concat(data));
                }
            }
        ;

        return {
            push: push
        };
    }
);
