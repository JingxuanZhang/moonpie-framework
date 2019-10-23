<!--
  - Copyright (c) 2018-2019.
  -  This file is part of the moonpie production
  -  (c) johnzhang <875010341@qq.com>
  -  This source file is subject to the MIT license that is bundled
  -  with this source code in the file LICENSE.
  -->

<template>
  <form
    class="am-form tpl-form-line-form"
    v-bind:method="usePost ? 'post' : 'get'"
    v-bind:enctype="useFile ? 'multipart/form-data' : ''"
  >
    <div class="widget-body">
      <fieldset>
        <slot name="header" />
        <slot name="main" />
        <slot name="footer">
          <div class="am-form-group">
            <div class="am-u-sm-9 am-u-sm-push-3 am-margin-top-lg">
              <button type="submit" class="j-submit am-btn am-btn-secondary">提交</button>
            </div>
          </div>
        </slot>
      </fieldset>
    </div>
  </form>
</template>

<script>
export default {
  name: "BaseForm",
  mounted() {
    let $form = $(this.$el)
    let option = {
      buildData: () => {
        return this.collectFormData()
      }
    }
    if(typeof this.handleFormSubmit == 'function' && this.handleFormSubmit.toString() !== 'function(){}') {
      console.log(this.handleFormSubmit.toString())
      option.done = this.handleFormSubmit
    }
    if(this.useFrame){
      $form.superFrameForm(option)
    }else {
      $form.superForm(option)
    }
  },
  props: {
    useFile: {
      type: Boolean,
      default: false
    },
    usePost: {
      type: Boolean,
      default: true
    },
    useFrame: {
      type: Boolean,
      default: false
    },
    initConfig: {
      type: Object
    },
    collectFormData: {
      type: Function,
      required: true
    },
    handleFormSubmit: {
      type: Function
    }
  }
};
</script>

<style scoped lang="scss">
</style>