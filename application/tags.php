<?php
/**
 * Created by Moonpie Studio.
 * User: JohnZhang
 * Date: 2019/5/27
 * Time: 9:17
 */
return [
    'console_init' => [
        ['\app\common\behavior\ConsoleBehavior', 'prepareThinkPHPQueueCommand'],//添加TP-queue命令
    ],
];