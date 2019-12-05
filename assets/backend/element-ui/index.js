/*
 * Copyright (c) 2018-2019.
 *  This file is part of the moonpie production
 *  (c) johnzhang <875010341@qq.com>
 *  This source file is subject to the MIT license that is bundled
 *  with this source code in the file LICENSE.
 */

import {
  Button,
  Container,
  Dialog,
  Form,
  Input,
  Switch,
  FormItem,
  Image,
  Alert,
  Row,
  Col,
  Table,
  TableColumn,
  Pagination, Select, Option, InputNumber, Cascader, Link, DatePicker, Collapse, CollapseItem, ButtonGroup
} from 'element-ui'

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
  Vue.use(Row)
  Vue.use(Col)
  Vue.use(Table)
  Vue.use(TableColumn)
  Vue.use(Pagination)
  Vue.use(Select)
  Vue.use(Option)
  Vue.use(InputNumber)
  Vue.use(Cascader)
  Vue.use(Link)
  Vue.use(DatePicker)
  Vue.use(Collapse)
  Vue.use(CollapseItem)
  Vue.use(ButtonGroup)
}
export default install