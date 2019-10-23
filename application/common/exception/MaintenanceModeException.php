<?php
/**
 * Created by Moonpie Studio.
 * User: JohnZhang
 * Date: 2019/4/10
 * Time: 14:17
 */

namespace app\common\exception;


class MaintenanceModeException extends ServiceUnavailableHttpException
{
    public $wentDownAt;
    public $willBeAvailableAt;
    public $retryAfter;
    public function __construct($time, $retryAfter = null, $message = null, \Exception $previous = null, $code = 0)
    {
        parent::__construct($retryAfter, $message, $previous, $code);
        $this->wentDownAt = \DateTime::createFromFormat(\DATE_W3C, date(\DATE_W3C, $time));

        if ($retryAfter) {
            $this->retryAfter = $retryAfter;
            $clone = clone $this->wentDownAt;
            $this->willBeAvailableAt = $clone->add(new \DateInterval("P{$this->retryAfter}S"));
        }
        parent::__construct($retryAfter, $message, $previous, $code);
    }
}