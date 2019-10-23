/*
 * Copyright (c) 2018-2019.
 *  This file is part of the moonpie production
 *  (c) johnzhang <875010341@qq.com>
 *  This source file is subject to the MIT license that is bundled
 *  with this source code in the file LICENSE.
 */
let mergePackages = require("@userfrosting/merge-package-dependencies")
let fs = require('fs')
let path = require('path')
let PluginAsset = require('../plugin.config')

//首先声明路径常量
let target_package = path.resolve(__dirname, '../package.json')
let target_package_copy = path.resolve(__dirname, '../package-backup.json')
const origin_package_json = JSON.parse(fs.readFileSync(target_package))

let template = { };
const importKeys = ["name", "version", "private", "description", "scripts", "author", "license"]
for (let [key, value] of Object.entries(origin_package_json)) {
  if(importKeys.includes(key)){
    template[key] = value
  }
}
//console.log('template is', template)
let pkgPaths = PluginAsset.getPluginPaths(__dirname, '../runtime/cache', 'plugin_asset.json')
pkgPaths = pkgPaths.map(function(pkg){return pkg.path})
console.log('pkg path is', pkgPaths)
try {
  pkgPaths.push(target_package_copy)
  fs.copyFileSync(target_package, target_package_copy)

  let result = mergePackages.yarn(template, pkgPaths, target_package)
  fs.writeFileSync(target_package, JSON.stringify(result, null, "\t"))
  console.info('rebuild package json successfully')
}catch (e) {
  console.error('merge plugin package failed with err', e)
}
