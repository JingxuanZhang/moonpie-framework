<?php
/**
 * Copyright (c) 2018-2019.
 *  This file is part of the moonpie production
 *  (c) johnzhang <875010341@qq.com>
 *  This source file is subject to the MIT license that is bundled
 *  with this source code in the file LICENSE.
 */

namespace app\common\service\security;


use Zend\Permissions\Acl\ProprietaryInterface;
use Zend\Permissions\Acl\Resource\ResourceInterface;

class OwnerShipResource implements ResourceInterface, ProprietaryInterface
{
    private $inner;
    private $ownerId;
    public function __construct(ResourceInterface $inner, $ownerId)
    {
        $this->inner = $inner;
        $this->ownerId = $ownerId;
    }
    public function getResourceId()
    {
        return $this->inner->getResourceId();
    }
    public function getOwnerId()
    {
        return $this->ownerId;
    }
}