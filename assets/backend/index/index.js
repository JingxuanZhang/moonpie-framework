/*
 * Copyright (c) 2018-2019.
 *  This file is part of the moonpie production
 *  (c) johnzhang <875010341@qq.com>
 *  This source file is subject to the MIT license that is bundled
 *  with this source code in the file LICENSE.
 */

import Vue from 'vue'
import ElementUtil from '@/backend/element-ui'
import UploadLocalConfig from '@/backend/components/upload/UploadLocalConfig'
import 'element-ui/lib/theme-chalk/index.css'

Vue.use(ElementUtil)

const app = new Vue({
  components: {
    UploadLocalConfig
  }
})
app.$mount('#app')