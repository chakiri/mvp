var Encore = require('@symfony/webpack-encore');
//To access env variable in assets
var dotenv = require('dotenv');


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
     * Add 1 entry for each "page" of your app
     * (including one that's included on every page - e.g. "app")
     *
     * Each entry will result in one JavaScript file (e.g. app.js)
     * and one CSS file (e.g. app.css) if your JavaScript imports CSS.ev
     */
    .addEntry('app', './assets/app.js')
    .addEntry('admin', './assets/scripts/admin.js')
    .addEntry('header', './assets/scripts/header.js')
    .addEntry('dashboard', './assets/scripts/dashboard.js')
    .addEntry('form', './assets/scripts/form.js')
    .addEntry('modals', './assets/scripts/modals.js')
    .addEntry('wamp', './assets/scripts/wamp.js')
    .addEntry('chat', './assets/scripts/chat.js')
    .addEntry('favoris', './assets/scripts/favoris.js')
    .addEntry('extern', './assets/scripts/extern.js')
    .addEntry('home', './assets/scripts/home.js')
    .addEntry('map', './assets/scripts/map.js')
    .addEntry('service', './assets/scripts/service.js')
    .addEntry('image', './assets/scripts/image.js')

    // When enabled, Webpack "splits" your files into smaller pieces for greater optimization.
    .splitEntryChunks()

    // will require an extra script tag for runtime.js
    // but, you probably want this, unless you're building a single-page app
    //.enableSingleRuntimeChunk()
    .disableSingleRuntimeChunk()

    /*
     * FEATURE CONFIG
     *
     * Enable & configure other features below. For a full
     * list of features, see:
     * https://symfony.com/doc/current/frontend.html#adding-more-features
     */
    .cleanupOutputBeforeBuild()
    .enableBuildNotifications()
    .enableSourceMaps(!Encore.isProduction())
    // enables hashed filenames (e.g. app.abc123.css)
    .enableVersioning(Encore.isProduction())

    // enables @babel/preset-env polyfills
    .configureBabelPresetEnv((config) => {
        config.useBuiltIns = 'usage';
        config.corejs = 3;
    })

    //Copies Images assets into build
    .copyFiles({
        from: './assets/images',
        to: 'images/[path][name].[hash:8].[ext]'
    })

    // define the environment variables
    .configureDefinePlugin(options => {
        const env = dotenv.config();

        if (env.error) {
            throw env.error;
        }

        //Define env variable for js files
        options['process.env'].APP_ENV = JSON.stringify(env.parsed.APP_ENV);
        options['process.env'].WAMP_PORT = JSON.stringify(env.parsed.WAMP_PORT);
        options['process.env'].GOOGLE_TAG_MANAGER_ID = JSON.stringify(env.parsed.GOOGLE_TAG_MANAGER_ID);
    })

    // enables Sass/SCSS support
    //.enableSassLoader()

    // uncomment if you use TypeScript
    //.enableTypeScriptLoader()

    // uncomment to get integrity="..." attributes on your script & link tags
    // requires WebpackEncoreBundle 1.4 or higher
    //.enableIntegrityHashes(Encore.isProduction())

    // uncomment if you're having problems with a jQuery plugin
    //.autoProvidejQuery()

    // uncomment if you use API Platform Admin (composer req api-admin)
    //.enableReactPreset()
    //.addEntry('admin', './assets/admin.js')
;

module.exports = Encore.getWebpackConfig();
