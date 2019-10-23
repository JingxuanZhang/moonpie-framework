<?php
/**
 * Created by Moonpie Studio.
 * User: JohnZhang
 * Date: 2019/4/10
 * Time: 14:15
 */

namespace app\common\exception;


use think\exception\HttpException;

class ServiceUnavailableHttpException extends HttpException
{
    public function __construct($retryAfter = null, $message = null, \Exception $previous = null, $code = 0 )
    {
        $headers = array();
        if ($retryAfter) {
            $headers = array('Retry-After' => $retryAfter);
        }
        parent::__construct(503, $message, $previous, $headers, $code);
    }
}