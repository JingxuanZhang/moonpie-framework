/*
 * Copyright (c) 2018-2019.
 *  This file is part of the moonpie production
 *  (c) johnzhang <875010341@qq.com>
 *  This source file is subject to the MIT license that is bundled
 *  with this source code in the file LICENSE.
 */
const path = require('path')

const fs = require('fs')

const PluginAsset = {
  getPluginPaths(...json_path) {
    const read_path = path.resolve(...json_path)
    console.error('read path is', read_path)
    const map = []
    try {
      const parse_data = JSON.parse(fs.readFileSync(read_path))
      parse_data.forEach(function(plugin){
        let pkg_path = path.format({dir: plugin.basePath, base: 'package.json'})
        console.log('package json', pkg_path)
        if(fs.existsSync(pkg_path)) {
          map.push({code: plugin.code, path: plugin.basePath})
        }
      })
    }catch (e) {
      console.log('has some error', e)
    }
    return map
  },
  /**
   * 引入插件的配置信息
   * @param Encore encore
   * @param json_path
   */
  import(encore, ...json_path) {
    const read_path = path.resolve(...json_path)
    try {
      const parse_data = JSON.parse(fs.readFileSync(read_path))
      parse_data.forEach(function(plugin){
        let alias = '@/plugin/' + plugin.code, aliases = {}
        aliases[alias] = plugin.basePath
        //console.log('aliases is', aliases)
        //首先添加别名
        encore.addAliases(aliases)
        //js部分
        if(plugin.hasOwnProperty('entries')){
          plugin.entries.forEach(function($entry){
            const {name, src} = $entry
            let $src
            if(typeof src != 'Array') {
              $src = [src]
            }else {
              $src = src
            }
            $src = $src.map(function($item){
              return path.format({dir: plugin.basePath, base: $item})
            })
            //console.log('$src is', $src, plugin)
            encore.addEntry(name, $src)
          })
        }
        if(plugin.hasOwnProperty('shareEntries')){
          plugin.shareEntries.forEach(function($entry){
            const {name, src} = $entry
            let $src
            if(typeof src != 'Array') {
              $src = [src]
            }else {
              $src = src
            }
            $src.forEach(function($item){
              const calc_path = path.format({dir: plugin.basePath, base: $item})
              encore.createSharedEntry(name, calc_path)
            })
          })
        }
        //单独样式部分
        if(plugin.hasOwnProperty('styleEntries')){
          plugin.styleEntries.forEach(function($shareEntry){
            const {name, src} = $shareEntry
            let $src
            if(typeof src != 'Array') {
              $src = [src]
            }else {
              $src = src
            }
            $src = $src.map(function($item){
              return path.format({dir: plugin.basePath, base: $item})
            })
            encore.addStyleEntry(name, $src)
          })
        }
        //处理直接copy的部分
        if(plugin.hasOwnProperty('copyFiles')){
          plugin.copyFiles.forEach(function($copyFile){
            $copyFile.from = path.format({dir: plugin.basePath, base: $copyFile.from})
            encore.copyFiles($copyFile)
          })
        }
      })
    }catch (e) {
      console.log('has some error', e)
    }
    return encore
  }
}
module.exports = PluginAsset
