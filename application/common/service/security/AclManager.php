<?php
/**
 * Created by Moonpie Studio.
 * User: JohnZhang
 * Date: 2019/4/24
 * Time: 9:15
 */

namespace app\common\service\security;


use app\common\model\AclResource;
use app\common\model\AclRole;
use app\common\model\AclUserAclGrant;
use Psr\Log\LoggerInterface;
use think\Hook;
use Zend\Permissions\Acl\Acl;

class AclManager extends Acl
{
    protected $logger;
    public function __construct(LoggerInterface $logger = null)
    {
        $this->logger = $logger;
        //接下来需要初始化系统默认的规则
        AclRole::addRolesTo($this);
        AclResource::addResourcesTo($this);
        AclUserAclGrant::addRulesTo($this);
        //接下来是程序配置的规则
        $params = ['manager' => $this];
        Hook::listen('acl_init', $params);
    }
    public function getLogger()
    {
        return $this->logger;
    }
}