module.exports = {

    // компиляция LESS
    compile: {
        options: {
            paths: ['web/css/']
        },
        files: {
            'web/css/global.css': ['web/css/global.less']
        }
    },

    // компиляция и минификация LESS
    compress: {
        options: {
            paths: ['web/css/'],
                compress: true
        },
        files: {
            'web/css/global.min.css': ['web/css/global.less']
        }
    }
};