<?php

/**
 * Copyright (c) 2018-2019.
 *  This file is part of the moonpie production
 *  (c) johnzhang <875010341@qq.com>
 *  This source file is subject to the MIT license that is bundled
 *  with this source code in the file LICENSE.
 */

/**
 * Created by Moonpie Studio.
 * User: JohnZhang
 * Date: 2019/5/25
 * Time: 8:47
 */

use think\Route;

$backend_group = \think\Env::get('BACKEND_NAME', 'admin');
Route::group($backend_group, function () {
    Route::get(['backend.home', '/home'], 'manager/Index/index'); //首页
    Route::any(['backend.login', '/login'], 'manager/Login/index', ['method' => 'get|post']); //登录
    Route::any(['backend.logout', '/logout'], 'manager/Login/logout', ['method' => 'get|post']); //注销
    Route::get(['backend.admin_index', '/management/index'], 'manager/Admin/index'); //后台用户管理
    Route::rule(['backend.admin_renew', '/management/personal'], 'manager/Admin/renew', 'get|post'); //后台用户个人信息管理
    Route::get(['backend.authorize_index', '/authorize/index'], 'manager/basic.authorize/index'); //角色管理
    Route::get(['backend.authorize_res', '/authorize/res'], 'manager/basic.authorize/res'); //资源管理
    Route::get(['backend.authorize_rule', '/authorize/rule'], 'manager/basic.authorize/rules'); //规则管理
    Route::get(['backend.authorize_price', '/authorize/role-upgrade'], 'manager/basic.authorize/price'); //角色升级管理
    //角色部分
    Route::post(['backend.authorize_drop_role', '/authorize/drop-role'], 'manager/basic.authorize/drop_role'); //删除角色
    Route::rule(['backend.authorize_edit_role', '/authorize/edit-role/:id'], 'manager/basic.authorize/edit_role', 'get|post'); //编辑角色
    Route::rule(['backend.authorize_add_role', '/authorize/add-rule'], 'manager/basic.authorize/add_role', 'get|post'); //添加角色
    Route::post(['backend.authorize_query_role', '/authorize/query-role'], 'manager/basic.authorize/query_role'); //查询角色
    //资源部分
    Route::post(['backend.authorize_drop_res', '/authorize/drop-resource'], 'manager/basic.authorize/drop_res'); //删除
    Route::rule(['backend.authorize_edit_res', '/authorize/edit-resource/:id'], 'manager/basic.authorize/edit_res', 'get|post'); //编辑
    Route::rule(['backend.authorize_add_res', '/authorize/add-resource'], 'manager/basic.authorize/add_res', 'get|post'); //添加
    Route::post(['backend.authorize_query_res', '/authorize/query-resource'], 'manager/basic.authorize/query_res'); //查询资源
    //规则部分
    Route::post(['backend.authorize_drop_rule', '/authorize/drop-rule'], 'manager/basic.authorize/drop_rule'); //删除
    Route::rule(['backend.authorize_edit_rule', '/authorize/edit-rule/:id'], 'manager/basic.authorize/edit_rule', 'get|post'); //编辑
    Route::rule(['backend.authorize_add_rule', '/authorize/add-rule'], 'manager/basic.authorize/add_rule', 'get|post'); //添加
    Route::post(['backend.authorize_query_rule', '/authorize/query-rule'], 'manager/basic.authorize/query_rule'); //查询
    //检测规则
    Route::post(['backend.authorize_query_assertion', '/authorize/query-assert'], 'manager/basic.authorize/query_assertion'); //查询断言
    //角色转换配置
    Route::post(['backend.authorize_drop_price', '/authorize/drop-price'], 'manager/basic.authorize/drop_price'); //删除价格
    Route::rule(['backend.authorize_edit_price', '/authorize/edit-price/:id'], 'manager/basic.authorize/edit_price', 'get|post'); //编辑价格
    Route::rule(['backend.authorize_add_price', '/authorize/add-price'], 'manager/basic.authorize/add_price', 'get|post'); //添加价格
    //上传相关路由
    Route::post(['backend.basic_upload_move_files', '/upload-library/move-files'], 'manager/upload.library/moveFiles'); //移动文件
    Route::post(['backend.basic_upload_drop_files', '/upload-library/delete-files'], 'manager/upload.library/deleteFiles'); //删除文件
    Route::get(['backend.basic_upload_file_list', '/upload-library/file-list'], 'manager/upload.library/fileList'); //文件列表
    Route::post(['backend.basic_upload_add_group', '/upload-library/add-group'], 'manager/upload.library/addGroup'); //添加分组
    Route::post(['backend.basic_upload_edit_group', '/upload-library/edit-group'], 'manager/upload.library/editGroup'); //编辑分组
    Route::post(['backend.basic_upload_drop_group', '/upload-library/delete-group'], 'manager/upload.library/deleteGroup'); //删除分组
    Route::post(['backend.basic_upload_image', '/upload/image/[:group_id]'], 'manager/upload.upload/image'); //上传图片
    Route::get(['backend.basic_upload_config', '/upload/configuration'], 'manager/upload.upload/storageConfig'); //新增文件管理相关
    Route::rule(['backend.basic_upload_storage_config', '/upload/storage-configuration/:code'], 'manager/upload.upload/driverConfig'); //新增文件管理相关
});
