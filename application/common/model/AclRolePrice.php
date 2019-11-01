<?php
/**
 * Created by Moonpie Studio.
 * User: JohnZhang
 * Date: 2019/4/23
 * Time: 17:00
 */

namespace app\common\model;



class AclRolePrice extends BaseModel
{
    const GOODS_TYPE = 1;
    public function source()
    {
        return $this->belongsTo(AclRole::class, 'from_role', 'id');
    }
    public function target()
    {
        return $this->belongsTo(AclRole::class, 'to_role', 'id');
    }
    public function getDeadlineAttr($deadline)
    {
        return new \DateTimeImmutable($deadline);
    }
    public function setDeadlineAttr($deadline)
    {
        if($deadline instanceof \DateTimeInterface) {
            return $deadline->format('Y-m-d H:i:s');
        }
        return $deadline;
    }

    public function getGoodsType()
    {
        return static::GOODS_TYPE;
    }

    public function getGoodsName()
    {
        return $this->source->title . '到' . $this->target->title . '角色升级成本';
    }


    public function getBuyPrice()
    {
        return $this->getData('price');
    }
}