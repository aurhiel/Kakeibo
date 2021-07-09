const Encore = require('@symfony/webpack-encore');
const GoogleFontsPlugin = require("@beyonk/google-fonts-webpack-plugin");
const CopyWebpackPlugin = require("copy-webpack-plugin");

// Manually configure the runtime environment if not already configured yet by the "encore" command.
// It's useful when you use tools that rely on webpack.config.js file.
if (!Encore.isRuntimeEnvironmentConfigured()) {
    Encore.configureRuntimeEnvironment(process.env.NODE_ENV || 'dev');
}

Encore
    // directory where compiled assets will be stored
    .setOutputPath('public/build/')
    // public path used by the web server to access the output path
    .setPublicPath('/build')
    // only needed for CDN's or sub-directory deploy
    //.setManifestKeyPrefix('build/')

    /*
     * ENTRY CONFIG
     *
     * Each entry will result in one JavaScript file (e.g. app.js)
     * and one CSS file (e.g. app.css) if your JavaScript imports CSS.
     */

     // Vendors
     // .createSharedEntry('entries', './shared_entries.js') // new way to add vendor on Encore^0.24.0
     // .createSharedEntry('vendors', ['jquery', 'bootstrap']) // old way (Encore^0.20.0)

     // will create public/build/kakeibo.js and public/build/kakeibo.css
     // .addEntry('kakeibo-app', './assets/vue/kakeibo.js')
     .addEntry('kakeibo',       './assets/js/kakeibo.js')
     .addEntry('kb-home',       './assets/js/kb-home.js')
     .addEntry('kb-admin',      './assets/js/kb-admin.js')
     // .addEntry('kb-dashboard', './assets/js/kb-dashboard.js')
     // .addEntry('kb-statistic', './assets/js/kb-statistic.js')

     // CSS only
     .addStyleEntry('kakeibo-dark',         './assets/css/kakeibo-dark.scss')
     .addStyleEntry('kakeibo--blue',        './assets/css/kakeibo--blue.scss')
     .addStyleEntry('kakeibo--blue-dark',   './assets/css/kakeibo--blue-dark.scss')

    // enables the Symfony UX Stimulus bridge (used in assets/bootstrap.js)
    // .enableStimulusBridge('./assets/controllers.json')

    // When enabled, Webpack "splits" your files into smaller pieces for greater optimization.
    .splitEntryChunks()

    // will require an extra script tag for runtime.js
    // but, you probably want this, unless you're building a single-page app
    .enableSingleRuntimeChunk()

    /*
     * FEATURE CONFIG
     *
     * Enable & configure other features below. For a full
     * list of features, see:
     * https://symfony.com/doc/current/frontend.html#adding-more-features
     */
    .cleanupOutputBeforeBuild()

    .enableBuildNotifications()


    /*
     * PLUGINS
     *
     */
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
    .addPlugin(new CopyWebpackPlugin({
      patterns: [
        {
          from: './assets/static'//, to: './'
        }
      ]
    }))

    /*
     * [...] FEATURE CONFIG
     *
     */
    .enableSourceMaps(!Encore.isProduction())
    // enables hashed filenames (e.g. app.abc123.css)
    .enableVersioning(Encore.isProduction())

    .configureBabel((config) => {
        config.plugins.push('@babel/plugin-proposal-class-properties');
    })

    // enables @babel/preset-env polyfills
    .configureBabelPresetEnv((config) => {
        config.useBuiltIns = 'usage';
        config.corejs = 3;
    })

    // enables Sass/SCSS support
    .enableSassLoader(function(sassOptions) {}, {
      resolveUrlLoader: false
    })

    // uncomment if you use TypeScript
    //.enableTypeScriptLoader()

    // uncomment if you use React
    //.enableReactPreset()

    // uncomment to get integrity="..." attributes on your script & link tags
    // requires WebpackEncoreBundle 1.4 or higher
    //.enableIntegrityHashes(Encore.isProduction())

    // uncomment if you're having problems with a jQuery plugin
    .autoProvidejQuery()
;

module.exports = Encore.getWebpackConfig();
