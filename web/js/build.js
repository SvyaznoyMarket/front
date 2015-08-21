({
    baseUrl: '.',
    dir: './build',
    paths: {
        'jquery': 'empty:',

        'jquery.cookie'         : 'vendor/jquery/jquery.cookie-1.4.1',
        'jquery.ui'             : 'vendor/jquery/jquery.ui-1.10.4.custom',
        'jquery.ui.touch-punch' : 'vendor/jquery/jquery.ui.touch-punch-0.2.3',
        'jquery.popup'          : 'plugin/jquery.popup',
        'jquery.deparam'        : 'plugin/jquery.deparam',
        'jquery.enterslide'     : 'plugin/jquery.enterslide',
        'jquery.touchwipe'      : 'plugin/jquery.touchwipe',
        'jquery.photoswipe'     : 'plugin/jquery.photoswipe',
        'jquery.slides'         : 'plugin/jquery.slides',
        'jquery.maskedinput'    : 'plugin/jquery.maskedinput',
        'jquery.smartbanner'    : 'plugin/jquery.smartbanner',

        'underscore'         : 'empty:',
        'mustache'           : 'vendor/mustache-0.8.2',
        'modernizr'          : 'vendor/modernizr.custom',
        'boilerplate.helper' : 'vendor/boilerplate.helper-4.1.0',

        'direct-credit' : 'empty:'
    },
    fileExclusionRegExp: /build.js|main.js/,
    optimize: 'uglify2'
})