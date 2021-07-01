var Encore = require('@symfony/webpack-encore');
var GoogleFontsPlugin = require("@beyonk/google-fonts-webpack-plugin");
var CopyWebpackPlugin = require("copy-webpack-plugin");

Encore
    // the project directory where compiled assets will be stored
    .setOutputPath('public/build/')
    // the public path used by the web server to access the previous directory
    .setPublicPath('/build')
    // the public path you will use in Symfony's asset() function - e.g. asset('build/some_file.js')
    .setManifestKeyPrefix('build/')

    // Vendors
    .createSharedEntry('entries', './shared_entries.js') // new way to add vendor on Encore^0.24.0
    // .createSharedEntry('vendors', ['jquery', 'bootstrap']) // old way (Encore^0.20.0)

    // will create public/build/kakeibo.js and public/build/kakeibo.css
    // .addEntry('kakeibo-app', './assets/vue/kakeibo.js')
    .addEntry('kakeibo',      './assets/js/kakeibo.js')
    .addEntry('kb-home',      './assets/js/kb-home.js')
    // .addEntry('kb-dashboard', './assets/js/kb-dashboard.js')
    // .addEntry('kb-statistic', './assets/js/kb-statistic.js')

    // CSS only
    // .addStyleEntry('kakeibo',       './assets/css/kakeibo.scss')
    .addStyleEntry('kakeibo-dark',  './assets/css/kakeibo-dark.scss')
    .addStyleEntry('kakeibo--blue',  './assets/css/kakeibo--blue.scss')
    .addStyleEntry('kakeibo--blue-dark',  './assets/css/kakeibo--blue-dark.scss')

    // will require an extra script tag for runtime.js
    // but, you probably want this, unless you're building a single-page app
    .enableSingleRuntimeChunk()

    // Cleaning things
    .cleanupOutputBeforeBuild()

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

    // Copy static files like fonts, images, ...
    .addPlugin(new CopyWebpackPlugin([
      { from: './assets/static' }
    ]))

    // Source maps for production environment
    .enableSourceMaps(!Encore.isProduction())

    // the following line enables hashed filenames (e.g. app.abc123.css)
    .enableVersioning(Encore.isProduction())

    // enable Vue support
    // .enableVueLoader()

    // uncomment if you use TypeScript
    //.enableTypeScriptLoader()

    // Display notifications on webpack's builds
    // .enableBuildNotifications()

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
