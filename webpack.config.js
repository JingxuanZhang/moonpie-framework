/*
 * Copyright (c) 2018-2019.
 *  This file is part of the moonpie production
 *  (c) johnzhang <875010341@qq.com>
 *  This source file is subject to the MIT license that is bundled
 *  with this source code in the file LICENSE.
 */

const path = require('path')

const Encore = require('@symfony/webpack-encore')

const PluginAsset = require('./plugin.config')

PluginAsset.import(Encore, __dirname, 'runtime/cache', 'plugin_asset.json')
Encore.setOutputPath('public/static/bundle')
  .setPublicPath('/static/bundle')
  .enableSingleRuntimeChunk()
  .cleanupOutputBeforeBuild()
  .enableVersioning(Encore.isProduction())
  .enableSourceMaps(!Encore.isProduction())
  .enableSassLoader()
  .addEntry('manage/js/app', './assets/backend/js/app.js')
  .addEntry('manage/js/index', './assets/backend/index/index.js')
  .enableVueLoader()
  .splitEntryChunks()
  .copyFiles([
    {from: './assets/backend/common/css', to: 'manage/css/[path][name].[hash:8].[ext]'},
    {from: './assets/backend/common/js', to: 'manage/js/[path][name].[hash:8].[ext]'},
    {from: './assets/backend/common/img', to: 'manage/img/[path][name].[ext]'},
    {from: './assets/backend/common/fonts', to: 'manage/fonts/[path][name].[ext]'}
  ])
  .autoProvidejQuery()
  .addAliases({
    '@/backend': path.resolve(__dirname, './assets/backend'),
    '@/app': path.resolve(__dirname, 'application')
  })
  //.addEntry('manage/js/sms', '@/app/submail/view/asset/main.js')
;
module.exports = Encore.getWebpackConfig();
