import Encore from '@symfony/webpack-encore';
import { fileURLToPath } from 'url';
import path from 'path';
import fs from 'fs';

const __filename = fileURLToPath(import.meta.url);
const __dirname = path.dirname(__filename);
const isProduction = Encore.isProduction();
const tinyMceOutputPath = path.join(__dirname, 'public/build/tinymce');

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
     * and one CSS file (e.g. app.css) if you JavaScript imports CSS.
     */
    .addEntry('app', './assets/app.js')
    //.addEntry('page1', './assets/js/page1.js')
    //.addEntry('page2', './assets/js/page2.js')

    /*
     * FEATURE CONFIG
     *
     * Enable & configure other features below. For a full
     * list of features, see:
     * https://symfony.com/doc/current/frontend.html#adding-more-features
     */
    .enableBuildNotifications()
    .enableSourceMaps(!Encore.isProduction())
    // Keep stable output names so Twig can always resolve build/app.css and build/app.js
    .enableVersioning(false)
    // enables single runtime chunk for faster development builds
    .enableSingleRuntimeChunk()

    // enables Sass/SCSS support
    .enableSassLoader()

    // enables Stimulus bridge with controllers.json
    .enableStimulusBridge('./assets/controllers.json')

    // uncomment if you use TypeScript
    //.enableTypeScriptLoader()
;

// In dev, keep build artifacts to avoid full recopy/rebuild work on every run.
if (isProduction) {
    Encore.cleanupOutputBeforeBuild();
}

// TinyMCE runtime files are copied in production, and in dev only if not already present.
if (isProduction || !fs.existsSync(tinyMceOutputPath)) {
    Encore.copyFiles({
        from: './node_modules/tinymce',
        to: 'tinymce/[path][name].[ext]',
        pattern: /\.(js|css|map|svg|png|gif|woff2?|ttf)$/,
    });
}

const config = Encore.getWebpackConfig();

config.cache = {
    type: 'filesystem',
    buildDependencies: {
        config: [__filename],
    },
};

export default config;
