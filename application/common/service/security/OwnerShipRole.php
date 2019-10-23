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
use Zend\Permissions\Acl\Role\RoleInterface;

class OwnerShipRole implements RoleInterface, ProprietaryInterface
{
    private $inner;
    private $ownerId;
    public function __construct(RoleInterface $inner, $ownerId)
    {
        $this->inner = $inner;
        $this->ownerId = $ownerId;
    }
    public function getRoleId()
    {
        return $this->inner->getRoleId();
    }
    public function getOwnerId()
    {
        return $this->ownerId;
    }
}