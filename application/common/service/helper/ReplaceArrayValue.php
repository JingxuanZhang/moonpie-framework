<?php
/**
 * Copyright (c) 2018-2019.
 * This file is part of the moonpie production
 *  (c) johnzhang <875010341@qq.com>
 * This source file is subject to the MIT license that is bundled
 *  with this source code in the file LICENSE.
 */

namespace app\common\service\helper;


use app\common\service\base\InvalidConfigException;

/**
 * Object that represents the replacement of array value while performing [[ArrayHelper::merge()]].
 *
 * Usage example:
 *
 * ```php
 * $array1 = [
 *     'ids' => [
 *         1,
 *     ],
 *     'validDomains' => [
 *         'example.com',
 *         'www.example.com',
 *     ],
 * ];
 *
 * $array2 = [
 *     'ids' => [
 *         2,
 *     ],
 *     'validDomains' => new \yii\helpers\ReplaceArrayValue([
 *         'yiiframework.com',
 *         'www.yiiframework.com',
 *     ]),
 * ];
 *
 * $result = \yii\helpers\ArrayHelper::merge($array1, $array2);
 * ```
 *
 * The result will be
 *
 * ```php
 * [
 *     'ids' => [
 *         1,
 *         2,
 *     ],
 *     'validDomains' => [
 *         'yiiframework.com',
 *         'www.yiiframework.com',
 *     ],
 * ]
 * ```
 *
 * @author Robert Korulczyk <robert@korulczyk.pl>
 * @since 2.0.10
 */
class ReplaceArrayValue
{
    /**
     * @var mixed value used as replacement.
     */
    public $value;

    /**
     * Constructor.
     * @param mixed $value value used as replacement.
     */
    public function __construct($value)
    {
        $this->value = $value;
    }

    /**
     * Restores class state after using `var_export()`.
     *
     * @param array $state
     * @return ReplaceArrayValue
     * @throws InvalidConfigException when $state property does not contain `value` parameter
     * @see var_export()
     * @since 2.0.16
     */
    public static function __set_state($state)
    {
        if (!isset($state['value'])) {
            throw new InvalidConfigException('Failed to instantiate class "Instance". Required parameter "id" is missing');
        }
        return new self($state['value']);
    }
}