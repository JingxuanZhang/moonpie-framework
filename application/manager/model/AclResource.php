<?php
/**
 * Created by Moonpie Studio.
 * User: JohnZhang
 * Date: 2019/4/24
 * Time: 10:07
 */

namespace app\manager\model;


use app\common\model\BaseModel;
use think\Cache;
use Zend\Permissions\Acl\Acl;

class AclResource extends BaseModel
{
    public function getAclResource()
    {
        return app('authorize.acl_res_registry')->get($this->getData('resource_id'), 'instance', ['resourceId' => $this->getData('code')]);
    }
    public static function addResourcesTo(Acl $acl)
    {
        $items = self::where('id', '>', 0)->cache('all_acl_resources')->select();
        if(empty($items)) return null;
        foreach($items as $item) {
            $inherit = $item->inherit;
            $acl->addResource($item->getAclResource(), $inherit ? $inherit->getAclResource() : null);
        }
        return true;
    }
    public function inherit()
    {
        return $this->belongsTo(self::class, 'pid', 'id');
    }
    protected function initialize()
    {
        parent::initialize();
        static::afterWrite(function($record){
            Cache::rm('all_acl_resources');
        });
        static::afterDelete(function($item){
            Cache::rm('all_acl_resources');
        });
    }
    public function delete()
    {
        //首先需要清除规则
        $result = false !== $this->rules()->delete();
        if($result) {
            return parent::delete();
        }
        return false;
    }
    public function rules()
    {
        return $this->hasMany(AclUserAclGrant::class, 'resource_id', 'id');
    }
}