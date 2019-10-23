<template>
    <el-dialog v-bind:visible="isShow" title="图片库" class="file-library" width="840px">
        <div class="row" v-show="isShow">
            <div class="file-group">
                <ul class="nav-new">
                    <li v-bind:class="{'ng-scope': true, active: isDefault}" data-group-id="-1">
                        <a class="group-name am-text-truncate" href="javascript:void(0);" title="全部">全部</a>
                    </li>
                    <li class="ng-scope" data-group-id="0">
                        <a class="group-name am-text-truncate" href="javascript:void(0);" title="未分组">未分组</a>
                    </li>
                    <li class="ng-scope" v-bind:groupId="group.group_id" v-bind:title="group.group_name" v-for="group in groupList">
                        <a class="group-edit" href="javascript:void(0);" title="编辑分组">
                            <i class="iconfont icon-bianji"></i>
                        </a>
                        <a class="group-name am-text-truncate" href="javascript:void(0);">
                            {{ group.group_name }}
                        </a>
                        <a class="group-delete" href="javascript:void(0);" title="删除分组">
                            <i class="iconfont icon-shanchu1"></i>
                        </a>
                    </li>
                </ul>
                <a class="group-add" href="javascript:void(0);">新增分组</a>
            </div>
            <div class="file-list">
                <div class="v-box-header am-cf">
                    <div class="h-left am-fl am-cf">
                        <div class="am-fl">
                            <div class="group-select am-dropdown">
                                <button type="button" class="am-btn am-btn-sm am-btn-secondary am-dropdown-toggle">
                                    移动至 <span class="am-icon-caret-down"></span>
                                </button>
                                <ul class="group-list am-dropdown-content">
                                    <li class="am-dropdown-header">请选择分组</li>
                                    <li v-for="group in groupList">
                                        <a class="move-file-group" v-bind:groupId="group.group_id"
                                           href="javascript:void(0);">{{ group.group_name }}</a>
                                    </li>
                                </ul>
                            </div>
                        </div>
                        <div class="am-fl tpl-table-black-operation">
                            <a href="javascript:void(0);" class="file-delete tpl-table-black-operation-del"
                               data-group-id="2">
                                <i class="am-icon-trash"></i> 删除
                            </a>
                        </div>
                    </div>
                    <div class="h-rigth am-fr">
                        <div class="j-upload upload-image">
                            <i class="iconfont icon-add1"></i>
                            上传图片
                        </div>
                    </div>
                </div>
                <div id="file-list-body" class="v-box-body">
                </div>
                <div class="v-box-footer am-cf"></div>
            </div>
        </div>
    </el-dialog>
</template>

<script>

  export default {
    name: "FileLibrary",
    props: ['isDefault', 'groupList'],
    data() {
      return {
        isShow: true
      }
    }
  }
</script>
<style>
    .file-library .el-dialog__body{
        overflow: auto;
        padding: 0 1rem;
        user-select: none;
    }

    .file-library .el-dialog__body .file-group {
        float: left;
        width: 150px;
        padding-top: 20px;
    }

    .file-library .el-dialog__body .file-group .nav-new {
        overflow-y: auto;
        max-height: 340px;
    }

    .file-library .el-dialog__body  .file-group .nav-new li {
        position: relative;
        margin: .3rem 0;
        padding: .8rem 2.3rem;
    }

    .file-library  .el-dialog__body .file-group .nav-new li a i.iconfont {
        font-size: 1.4rem;
    }

    .file-library  .el-dialog__body .file-group .nav-new li a.group-name {
        color: #595961;
        font-size: 1.3rem;
    }

    .file-library .el-dialog__body .file-group .nav-new li a.group-edit {
        display: none;
        position: absolute;
        left: .6rem;
    }

    .file-library .el-dialog__body  .file-group .nav-new li a.group-delete {
        display: none;
        position: absolute;
        right: .6rem;
    }

    .file-library .el-dialog__body  .file-group .nav-new li:hover, .file-library .el-dialog__body .file-group .nav-new li.active {
        background: rgba(48, 145, 242, 0.1);
        border-radius: 6px;
    }

    .file-library .el-dialog__body .file-group .nav-new li:hover .group-name, .file-library .el-dialog__body .file-group .nav-new li.active .group-name {
        color: #0e90d2;
    }

    .file-library .el-dialog__body .file-group .nav-new li:hover .group-edit, .file-library .el-dialog__body .file-group .nav-new li:hover .group-delete {
        display: inline;
    }

    .file-library .el-dialog__body .file-group a.group-add {
        display: block;
        margin-top: 1.8rem;
        font-size: 1.2rem;
        padding: 1.2rem 2.3rem;
    }

    .file-library  .el-dialog__body .file-list {
        float: left;
    }

    .file-library  .el-dialog__body .file-list .v-box-header {
        padding: 0 2rem 0 1rem;
        margin-bottom: 10px;
    }

    .file-library .el-dialog__body .file-list .v-box-header .h-left .tpl-table-black-operation {
        margin: 0 1rem;
    }

    .file-library .el-dialog__body .file-list .v-box-header .h-left .tpl-table-black-operation a {
        padding: 6px 10px;
    }

    .file-library .el-dialog__body .file-list .v-box-header .h-left .am-dropdown-toggle {
        font-size: 1.2rem;
    }

    .file-library  .el-dialog__body .file-list .v-box-header .h-left .am-dropdown-content a {
        font-size: 1.3rem;
    }

    .file-library .el-dialog__body .file-list .v-box-header .h-rigth .upload-image .iconfont {
        font-size: 1.2rem;
    }

    .file-library .el-dialog__body .v-box-body {
        width: 660px;
    }

    .file-library .el-dialog__body .v-box-body ul.file-list-item {
        overflow-y: auto;
        height: 380px;
    }

    .file-library .el-dialog__body .v-box-body ul.file-list-item li {
        position: relative;
        cursor: pointer;
        border-radius: 6px;
        padding: 10px;
        border: 1px solid rgba(0, 0, 0, 0.05);
        float: left;
        margin: 10px;
        -webkit-transition: All 0.2s ease-in-out;
        -moz-transition: All 0.2s ease-in-out;
        -o-transition: All 0.2s ease-in-out;
        transition: All 0.2s ease-in-out;
    }

    .file-library .el-dialog__body  .v-box-body ul.file-list-item li:hover {
        border: 1px solid #16bce2;
    }

    .file-library .el-dialog__body .v-box-body ul.file-list-item li .img-cover {
        width: 120px;
        height: 120px;
        background-repeat: no-repeat;
        background-size: cover;
    }

    .file-library .el-dialog__body .v-box-body ul.file-list-item li p.file-name {
        margin: 5px 0 0 0;
        width: 120px;
        font-size: 1.3rem;
    }

    .file-library .el-dialog__body .v-box-body ul.file-list-item li.active .select-mask {
        display: block;
    }

    .file-library .el-dialog__body .v-box-body ul.file-list-item li .select-mask {
        display: none;
        position: absolute;
        top: 0;
        bottom: 0;
        left: 0;
        right: 0;
        background: rgba(0, 0, 0, 0.5);
        text-align: center;
        border-radius: 6px;
    }

    .file-library .el-dialog__body .v-box-body ul.file-list-item li .select-mask img {
        position: absolute;
        top: 50px;
        left: 45px;
    }

    .file-library .el-dialog__body .v-box-body ul.pagination {
        margin: 0;
    }

    .file-library .el-dialog__body .v-box-body ul.pagination > li > a, .file-library  .v-box-body ul.pagination > li > span {
        padding: .3rem .9rem;
        font-size: 1.3rem;
    }

</style>
