<!--
  - Copyright (c) 2018-2019.
  -  This file is part of the moonpie production
  -  (c) johnzhang <875010341@qq.com>
  -  This source file is subject to the MIT license that is bundled
  -  with this source code in the file LICENSE.
  -->

<template>
    <form class="am-form tpl-form-line-form" v-bind:method="usePost ? 'post' : 'get'" v-bind:enctype="useFile ? 'multipart/form-data' : ''">
        <div class="widget-body">
            <fieldset>
                <slot name="header"></slot>
                <component v-bind:is="chooseComponent" v-bind:init-config="initConfig" ref="main"/>
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
  import IncentiveAdverseConfig from './IncentiveAdverseConfig'

  export default {
    name: "BasicConfig",
    components: {
      IncentiveAdverseConfig
    },
    mounted() {
      let $form = $(this.$el)
      //console.log('form is ', $form, $form.length)
      //console.log(this.$refs.main.$data)
      $form.superForm({
        buildData: () => {
          return {[this.innerKey]: this.$refs.main.$data.config}
        }
      })
    },
    computed: {
      chooseComponent: function() {
        let ret = this.innerKey.split('_').map((item) => item.substring(0, 1).toLocaleUpperCase() + item.substring(1))
        console.log('ret is ', ret)
        return ret.join('') + 'Config'
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
      innerKey: {
        type: String
      },
      initConfig: {
        type: Object
      }
    }
  }
</script>

<style scoped>

</style>