<?php
/**
 * Copyright (c) 2018-2019.
 * This file is part of the moonpie production
 * (c) johnzhang <875010341@qq.com>
 * This source file is subject to the MIT license that is bundled
 * with this source code in the file LICENSE.
 */

namespace app\common\util\exception;

/**
 * Exception class for when XML parsing with an XSD schema file path or a callable validator produces errors unrelated
 * to the actual XML parsing.
 *
 * @author Ole Rößner <ole@roessner.it>
 */
class InvalidXmlException extends XmlParsingException
{
}
