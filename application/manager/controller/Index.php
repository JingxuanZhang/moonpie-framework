<?php
/**
 * Copyright (c) 2018-2019.
 *  This file is part of the moonpie production
 *  (c) johnzhang <875010341@qq.com>
 *  This source file is subject to the MIT license that is bundled
 *  with this source code in the file LICENSE.
 */

namespace app\manager\controller;


class Index extends Base
{
    public function index()
    {
        $tongji = '统计结果';
        $this->assign('tongji', $tongji);
        $this->assign('page_title', '首页');
        $this->assign('redbag_send_count', 0);
        $this->assign('redbag_send_amount', 0);
        return $this->fetch();
    }
}
