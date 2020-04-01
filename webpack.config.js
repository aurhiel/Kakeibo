var Encore = require('@symfony/webpack-encore');
var GoogleFontsPlugin = require("google-fonts-webpack-plugin");
var CopyWebpackPlugin = require("copy-webpack-plugin");

Encore
    // the project directory where compiled assets will be stored
    .setOutputPath('public/build/')

    // the public path used by the web server to access the previous directory
    .setPublicPath('/build')

    // the public path you will use in Symfony's asset() function - e.g. asset('build/some_file.js')
    .setManifestKeyPrefix('build/')

    //
    .cleanupOutputBeforeBuild()

    // Copy static files like fonts, images, ...
    .addPlugin(new CopyWebpackPlugin([
      { from: './assets/static' }
    ]))

    // Display notifications on webpack's builds
    // .enableBuildNotifications()

    .enableSourceMaps(!Encore.isProduction())

    // the following line enables hashed filenames (e.g. app.abc123.css)
    .enableVersioning(Encore.isProduction())

    // will create public/build/kakeibo.js and public/build/kakeibo.css
    // .addEntry('kakeibo-app', './assets/vue/kakeibo.js')
    .addEntry('kakeibo',      './assets/js/kakeibo.js')
    .addEntry('kb-dashboard', './assets/js/kb-dashboard.js')

    // CSS only
    // .addStyleEntry('kakeibo',       './assets/css/kakeibo.scss')
    .addStyleEntry('kakeibo-dark',  './assets/css/kakeibo-dark.scss')

    // Vendors
    .createSharedEntry('vendors', ['jquery', 'bootstrap'])

    // Google Fonts
    .addPlugin(new GoogleFontsPlugin({
      fonts: [
          { family: "Cabin", variants: [ "400", "700" ] },
          { family: "Arvo", variants: [ "400", "700" ] },
          { family: "Roboto Mono", variants: [ "400", "700" ] },
          { family: "Montserrat", variants: [ "300" ] },
      ],
      "path": "fonts/google/",
      "filename": "google-fonts.css"
    }))

    // enable Vue support
    // .enableVueLoader()

    // uncomment if you use TypeScript
    //.enableTypeScriptLoader()

    // uncomment if you use Sass/SCSS files
    .enableSassLoader(function(sassOptions) {}, {
      resolveUrlLoader: false
    })

    // uncomment for legacy applications that require $/jQuery as a global variable
    .autoProvidejQuery()
;

// Use polling instead of inotify
const config = Encore.getWebpackConfig();
config.watchOptions = {
    poll: true,
};

module.exports = config;
