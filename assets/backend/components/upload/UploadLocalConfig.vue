<!--
  - Copyright (c) 2018-2019.
  -  This file is part of the moonpie production
  -  (c) johnzhang <875010341@qq.com>
  -  This source file is subject to the MIT license that is bundled
  -  with this source code in the file LICENSE.
  -->

<template>
    <base-form v-bind:collect-form-data="onCollectData">
        <template v-slot:main>
            <div class="am-form-group">
                <label class="am-u-sm-3 am-u-lg-2 am-form-label form-require">默认容器</label>
                <div class="am-u-sm-9 am-u-end">
                    <el-switch active-value="1" inactive-value="0" active-text="是" inactive-text="否" v-model="config.active"/>
                </div>
            </div>

            <div class="am-form-group">
                <label class="am-u-sm-3 am-u-lg-2 am-form-label form-require">存储根路径</label>
                <div class="am-u-sm-9 am-u-end">
                    <el-input v-model="config.setting['base path']" />
                </div>
            </div>
            <div class="am-form-group">
                <label class="am-u-sm-3 am-u-lg-2 am-form-label form-require">文件权限</label>
                <div class="am-u-sm-9 am-u-end">
                    <el-input v-model="config.setting.permissions.file" />
                </div>
            </div>
            <div class="am-form-group">
                <label class="am-u-sm-3 am-u-lg-2 am-form-label form-require">文件夹权限</label>
                <div class="am-u-sm-9 am-u-end">
                    <el-input v-model="config.setting.permissions.dir" />
                </div>
            </div>
        </template>
    </base-form>
</template>

<script>
  import BaseForm from '@/backend/components/basic/BaseForm'

  export default {
    name: "UploadLocalConfig",
    components: {BaseForm},
    props: ['initConfig'],
    methods: {
      onCollectData() {
        return {config: this.config}
      }
    },
    data() {
      return {
        config: this.initConfig || {}
      }
    }
  }
</script>

<style scoped>

</style>