/*
 * Copyright (c) 2018-2019.
 *  This file is part of the moonpie production
 *  (c) johnzhang <875010341@qq.com>
 *  This source file is subject to the MIT license that is bundled
 *  with this source code in the file LICENSE.
 */

import {Button, Container, Dialog, Form, Input, Switch, FormItem, Image, Alert} from 'element-ui'

function install(Vue) {
  Vue.use(Dialog)
  Vue.use(Container)
  Vue.use(Form)
  Vue.use(Button)
  Vue.use(Switch)
  Vue.use(Input)
  Vue.use(FormItem)
  Vue.use(Image)
  Vue.use(Alert)
}
export default install