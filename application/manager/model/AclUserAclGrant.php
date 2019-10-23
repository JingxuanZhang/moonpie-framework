<?php
/**
 * Created by Moonpie Studio.
 * User: JohnZhang
 * Date: 2019/4/24
 * Time: 10:30
 */

namespace app\manager\model;


use app\common\model\BaseModel;
use think\Cache;
use Zend\Permissions\Acl\Acl;

class AclUserAclGrant extends BaseModel
{
    protected $type = ['privileges' => 'array'];
    public function role()
    {
        return $this->belongsTo(AclRole::class, 'role_id', 'id');
    }
    public function resource()
    {
        return $this->belongsTo(AclResource::class, 'resource_id', 'id');
    }
    public static function addRulesTo(Acl $acl)
    {
        $items = self::where('id', '>', 0)->cache('all_acl_rules')->select();
        /** @var self $item */
        foreach($items as $item) {
            $assertion = $item->fetchAssertion();
            if($item['granted']) {
                $acl->allow($item->role, $item->resource ? $item->resource->getAclResource() : null, $item['privileges'], $assertion);
            }else {
                $acl->deny($item->role, $item->resource ? $item->resource->getAclResource() : null, $item['privileges'], $assertion);
            }
        }
        return true;
    }
    public function fetchAssertion()
    {
        $return = null;
        $assertion_code = $this->getData('assertion');
        if(!empty($assertion_code)) {
            /** @var \app\common\service\security\AssertionRegistry $registry */
            $registry = app('authorize.acl_assert_registry');
            $return = $registry->get($assertion_code, 'instance');
        }
        return $return;
    }
    public function getUseCodeAttr($attr) {
        if($attr == 0) return '使用实例';
        return '使用机读码';
    }
    public function getGrantedAttr($granted)
    {
        if($granted == 1) return '允许';
        return '拒绝';
    }
    protected function initialize()
    {
        parent::initialize();
        static::afterWrite(function($item){
            Cache::rm('all_acl_rules');
        });
        static::afterDelete(function($item){
            Cache::rm('all_acl_rules');
        });
    }
}