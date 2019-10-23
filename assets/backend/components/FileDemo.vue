<template>
    <div class="page-home row-content am-cf">
        <!-- 商城统计 -->
        <div class="row">
            <div class="am-u-sm-12 am-u-md-12 am-u-lg-12 am-margin-bottom">
                <div class="widget am-cf">
                    <div class="widget-head">
                        <div class="widget-title">统计</div>
                    </div>
                    <div class="widget-body am-cf">
                        <div class="am-u-sm-12 am-u-md-6 am-u-lg-3">
                            <div class="widget-card card__blue am-cf">
                                <div class="card-header">红包任务完成数</div>
                                <div class="card-body">
                                    <div class="card-value">0</div>
                                    <div class="card-description">累计完成红包任务的数量</div>
                                    <span class="card-icon iconfont icon-goods"></span>
                                </div>
                            </div>
                        </div>

                        <div class="am-u-sm-12 am-u-md-6 am-u-lg-3">
                            <div class="widget-card card__red am-cf">
                                <div class="card-header">红包任务奖励金额</div>
                                <div class="card-body">
                                    <div class="card-value">0元</div>
                                    <div class="card-description">累计发放的红包任务金额</div>
                                    <span class="card-icon iconfont icon-user"></span>
                                </div>
                            </div>
                        </div>

                        <div class="am-u-sm-12 am-u-md-6 am-u-lg-3">
                            <div class="widget-card card__violet am-cf">
                                <div class="card-header">昨日关注</div>
                                <div class="card-body">
                                    <div class="card-value">0</div>
                                    <div class="card-description">昨日关注总数量</div>
                                    <span class="card-icon iconfont icon-order"></span>
                                </div>
                            </div>
                        </div>

                        <div class="am-u-sm-12 am-u-md-6 am-u-lg-3">
                            <div class="widget-card card__primary am-cf">
                                <div class="card-header">昨日回关</div>
                                <div class="card-body">
                                    <div class="card-value">0</div>
                                    <div class="card-description">昨日回关总数量</div>
                                    <span class="card-icon iconfont icon-haoping2"></span>
                                </div>
                            </div>
                        </div>

                    </div>
                </div>
            </div>
        </div>
        <div class="row">
            <file-library is-default="true" v-bind:group-list="testData" />
        </div>
    </div>
</template>

<script>
  import FileLibrary from '@/backend/components/FileLibrary'

  export default {
    name: "FileDemo",
    data() {
      return {
        testData: [{group_id: 2, group_name: '测试分类'}]
      }
    },
    components: {
      FileLibrary
    }
  }
</script>

