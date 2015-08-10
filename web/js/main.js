var
    date = new Date(),
    debug = 'true' == document.getElementById('js-enter-debug').getAttribute('content'),
    version = document.getElementById('js-enter-version').getAttribute('content'),
    moduleName = 'module/' + (document.getElementById('js-enter-module').getAttribute('content') || 'default')
;

console.info('Init app', debug, version, moduleName);

require.config({
    urlArgs: 't=' + version,
    baseUrl: '/js' + (debug ? '' : '/build'),
    //baseUrl: '/js',
    paths: {
        //'jquery': 'http://yandex.st/jquery/2.1.0/jquery',
        //'jquery'            : 'vendor/jquery-1.11.0',
        'jquery'                : ['http://yandex.st/jquery/1.8.3/jquery', 'vendor/jquery-1.8.3'],
        'jquery.cookie'         : 'vendor/jquery/jquery.cookie-1.4.1',
        'jquery.ui'             : 'vendor/jquery/jquery-ui',
        'jquery.ui.touch-punch' : 'vendor/jquery/jquery.ui.touch-punch-0.2.3',
        'jquery.popup'          : 'plugin/jquery.popup',
        'jquery.modal'          : 'plugin/jquery.modal',
        'jquery.deparam'        : 'plugin/jquery.deparam',
        'jquery.enterslide'     : 'plugin/jquery.enterslide',
        'jquery.touchwipe'      : 'plugin/jquery.touchwipe',
        'jquery.photoswipe'     : 'plugin/jquery.photoswipe',
        'jquery.slides'         : 'plugin/jquery.slides',
        'jquery.scrollTo'       : 'plugin/jquery.scrollTo',
        'jquery.maskedinput'    : 'plugin/jquery.maskedinput',
        'jquery.smartbanner'    : 'plugin/jquery.smartbanner',
        'jquery.slick'          : 'plugin/slick.min',
        'jquery.kladr'          : 'plugin/jquery.kladr',
        'heightFixIos'          : 'plugin/heightFixIos',

        'underscore'         : ['http://yandex.st/underscore/1.6.0/underscore', 'vendor/underscore-1.6.0'],
        'mustache'           : 'vendor/mustache-0.8.2',
        'modernizr'          : 'vendor/modernizr.custom',
        'boilerplate.helper' : 'vendor/boilerplate.helper-4.1.0',

        'yandexmaps' : (debug) ? 'http://api-maps.yandex.ru/2.1/?load=package.full&lang=ru-RU&mode=debug' : 'http://api-maps.yandex.ru/2.1/?load=package.full&lang=ru-RU&mode=release',

        'kladr' : 'vendor/kladr/core',

        'direct-credit' : 'http://direct-credit.ru/widget/api_script_utf'
    },

    shim: {
        'jquery': {
            exports: 'jQuery'
        },
        'jquery.ui': {
            deps: ['jquery']
        },
        'jquery.ui.touch-punch': {
            deps: ['jquery', 'jquery.ui']
        },
        'jquery.enterslide': {
            deps: ['jquery']
        },
        'jquery.popup': {
            deps: ['jquery']
        },
        'jquery.modal': {
            deps: ['jquery']
        },
        'jquery.touchwipe': {
            deps: ['jquery']
        },
        'jquery.photoswipe': {
            deps: ['jquery', 'jquery.touchwipe']
        },
        'jquery.slides': {
            deps: ['jquery', 'jquery.touchwipe']
        },
        'jquery.scrollTo': {
            deps: ['jquery']
        },
        'jquery.maskedinput': {
            deps: ['jquery']
        },
        'jquery.smartbanner': {
            deps: ['jquery']
        },
        'jquery.kladr': {
            deps: ['jquery', 'kladr']
        },
        'underscore': {
            exports: '_'
        },
        'mustache': {
            exports: '_'
        },
        'modernizr': [],
        'boilerplate.helper': [],

        'yandexmaps': {
            exports: 'ymaps'
        },

        'kladr': {
            deps: ['jquery']
        },

        'direct-credit': []
    }
});

// отладка
if (debug) {
    require(['module/debug']);
}

// основные скрипты
require(
    [
        'require',
        'module/config',
        'modernizr',
        'boilerplate.helper',
        'jquery',
        'jquery.cookie',
        'jquery.ui', 'jquery.ui.touch-punch', 'jquery.popup',
        'jquery.touchwipe',
        'module/default',
        'module/analytics.google',
        'module/util',
        'module/jira',
        'module/navigation',
        'module/region',
        'module/search',
        'module/popupShow',
        'jquery.modal',
        'module/widget',      // виджеты
        'module/cart.common', // кнопка купить, спиннер
        //'module/order',
        'module/product.catalog.common',
		'module/siteVersionSwitcher',
		'module/slotButton',
        'module/addReview'
    ],
    function(require, config) {
        $.cookie.defaults.path = '/';
        $.cookie.defaults.domain = config.cookie.domain;

        // модуль страницы
        require([moduleName], function(module) {
            // партнерский модуль
            setTimeout(function() { require(['module/partner']); }, 600);
        });
    }
);
