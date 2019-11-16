<?php


namespace app\common\service\plugin\acl;


use app\common\model\AclResource;
use app\common\model\AclRole;
use app\common\model\AclUserAclGrant;
use app\common\service\plugin\PluginElement;
use app\common\service\plugin\PluginManager;
use Drupal\Component\Graph\Graph;
use EasyWeChat\Kernel\Support\Arr;

class AclManager
{
    protected $pluginManager;
    protected $pluginElement;

    public function __construct(PluginManager $pluginManager)
    {
        $this->pluginManager = $pluginManager;
    }

    public function getPluginElement()
    {
        return $this->pluginElement;
    }

    /**
     * @return PluginManager
     */
    public function getPluginManager()
    {
        return $this->pluginManager;
    }

    public function install(PluginElement $pluginElement, $force = false)
    {
        $this->pluginElement = $pluginElement;
        $configs = $this->getSortedConfig();
        //dump($configs); exit;
        AclRole::startTrans();
        $now = time();
        try {
            //首先处理角色
            $roles = Arr::get($configs, 'roles', []);
            $drop_roles = [];
            foreach ($roles as $role_code => $config) {
                if ($config['data']['deprecated']) {
                    $drop_roles[] = $role_code;
                } else {
                    $record = AclRole::where('code', $role_code)->find();
                    if (!$record) {//如果没有记录
                        $record = new AclRole();
                        $result = false !== $record->save([
                                'code' => $role_code, 'title' => Arr::get($config, 'data.title', ''),
                                'description' => Arr::get($config, 'data.description', '')
                            ]);//, Arr::only($config['data'], ['title', 'description'])));
                        if (!$result) {
                            AclRole::rollback();
                            return false;
                        }
                    }
                    //这里同步角色父级信息
                    $parents = [];
                    if (!empty($config['data']['parents'])) {
                        $parents = AclRole::whereIn('code', $config['data']['parents'])->column('id');
                    }
                    $sync = empty($parents) ? [] : array_fill_keys($parents, ['create_at' => $now, 'update_at' => $now]);
                    $record->parents()->sync($sync, true);
                }
            }
            //清理待删除角色
            $drop_role_ids = [];
            if (!empty($drop_roles)) {
                $drop_role_ids = AclRole::whereIn('code', $drop_roles)->column('id');
                $result = false !== AclRole::whereIn('id', $drop_role_ids)->delete();
                if (!$result) {
                    AclRole::rollback();
                    return false;
                }
            }
            //稍后是资源
            $resources = Arr::get($configs, 'resources', []);
            $drop_resources = [];
            foreach ($resources as $resource_code => $config) {
                //
                if ($config['data']['deprecated']) {
                    $drop_resources[] = $resource_code;
                } else {
                    $record = AclResource::where('code', $resource_code)->find();
                    if (!$record) {//如果没有记录
                        $record = new AclResource();
                        $result = false !== $record->save([
                                'code' => $resource_code,
                                'resource_id' => Arr::get($config, 'data.class', ''),
                                'title' => Arr::get($config, 'data.title', ''),
                                'description' => Arr::get($config, 'data.description', ''),
                            ]);
                        if (!$result) {
                            AclRole::rollback();
                            return false;
                        }
                    }
                    //这时候处理父资源信息
                    $parent_code = Arr::get($config, 'data.parent', null);
                    if (!$parent_code) $pid = null;
                    else $pid = AclResource::where('code', $config['data']['id'])->value('id');
                    if ($pid > 0) {
                        $result = false !== $record->save(['pid' => $pid]);
                        if (!$result) {
                            AclRole::rollback();
                            return false;
                        }
                    }
                }
            }
            //清理待删除角色
            $drop_resource_ids = [];
            if (!empty($drop_resources)) {
                $drop_resource_ids = AclResource::whereIn('code', $drop_resources)->column('id');
                $result = false !== AclResource::whereIn('id', $drop_resource_ids)->delete();
                if (!$result) {
                    AclRole::rollback();
                    return false;
                }
            }
            //最后是权限
            $grants = Arr::get($configs, 'grants', []);
            foreach ($grants as $grant) {
                if ($grant['deprecated']) {
                    //@todo 删除的规则如何去做
                } else {
                    $record = new AclUserAclGrant();
                    $role_id = $resource_id = null;
                    if (!empty($grant['roleId'])) $role_id = AclRole::where('code', $grant['roleId'])->value('id');
                    if (!empty($grant['resourceId'])) $resource_id = AclResource::where('code', $grant['resourceId'])->value('id');
                    $result = false !== $record->save([
                            'role_id' => $role_id, 'resource_id' => $resource_id,
                            'title' => $grant['title'], 'assertion' => Arr::get($grant, 'assertion', ''),
                            'privileges' => Arr::get($grant, 'privileges', []),
                            'use_code' => 1, 'granted' => $grant['allowed'],
                        ]);
                    if (!$result) {
                        AclRole::rollback();
                        return false;
                    }
                }
            }
            if (!empty($drop_role_ids)) {
                $result = false !== AclUserAclGrant::whereIn('role_id', $drop_role_ids)->delete();
                if (!$result) {
                    AclRole::rollback();
                    return false;
                }
            }
            if (!empty($drop_resource_ids)) {
                $result = false !== AclUserAclGrant::whereIn('resource_id', $drop_resource_ids)->delete();
                if (!$result) {
                    AclRole::rollback();
                    return false;
                }
            }
            AclRole::commit();
            return true;
        } catch (\Exception $e) {
            AclRole::rollback();
            throw $e;
        }
    }

    public function uninstall(PluginElement $pluginElement, $force = false)
    {
        $this->pluginElement = $pluginElement;
        $configs = $this->getSortedConfig();
        //dump($configs); exit;
        AclRole::startTrans();
        try {
            //首先处理权限
            $roles = Arr::get($configs, 'roles', []);
            $drop_roles = array_keys($roles);
            //清理待删除角色
            $drop_role_ids = [];
            if (!empty($drop_roles)) {
                $drop_role_ids = AclRole::whereIn('code', $drop_roles)->column('id');
            }
            //稍后是资源
            $resources = Arr::get($configs, 'resources', []);
            $drop_resources = array_keys($resources);
            //清理待删除角色
            $drop_resource_ids = [];
            if (!empty($drop_resources)) {
                $drop_resource_ids = AclResource::whereIn('code', $drop_resources)->column('id');
            }
            //最后是角色
            if (!empty($drop_role_ids)) {
                $result = false !== AclUserAclGrant::whereIn('role_id', $drop_role_ids)->delete();
                if (!$result) {
                    AclRole::rollback();
                    return false;
                }
            }
            if (!empty($drop_resource_ids)) {
                $result = false !== AclUserAclGrant::whereIn('resource_id', $drop_resource_ids)->delete();
                if (!$result) {
                    AclRole::rollback();
                    return false;
                }
            }
            if (!empty($drop_resource_ids)) {
                $result = false !== AclResource::whereIn('id', $drop_resource_ids)->delete();
                if (!$result) {
                    AclRole::rollback();
                    return false;
                }
            }
            if (!empty($drop_role_ids)) {
                $result = false !== AclRole::whereIn('id', $drop_role_ids)->delete();
                if (!$result) {
                    AclRole::rollback();
                    return false;
                }
            }
            AclRole::commit();
            return true;
        } catch (\Exception $e) {
            AclRole::rollback();
            throw $e;
        }
    }

    public function upgrade(PluginElement $pluginElement, $force = false)
    {
        return $this->install($pluginElement, $force);
    }

    protected function getSortedConfig()
    {
        $configs = $this->getPluginElement()->getElement('acl_config', []);
        $return = [];
        foreach ($configs as $field => $config) {
            if (in_array($field, ['roles', 'resources'])) {
                $graph_data = [];
                foreach ($config as $item) {
                    $graph_data[$item['id']] = [
                        'edges' => [],
                        'data' => $item,
                    ];
                    if ($field == 'roles') {
                        foreach ($item['parents'] as $parent) {
                            $parent_data = $this->resolveItem($config, $parent, $field);
                            if (!empty($parent_data)) {
                                if ($parent_data['deprecated']) $graph_data[$item['id']]['data']['deprecated'] = true;
                                $graph_data[$item['id']]['edges'][$parent] = $parent_data;
                            }
                        }
                    } else if ($field == 'resources') {
                        if (isset($item['parent'])) {
                            $parent_resource_data = $this->resolveItem($config, $item['parent'], $field);
                            if (!is_null($parent_resource_data)) {
                                if ($parent_resource_data['deprecated']) $graph_data[$item['id']]['data']['deprecated'] = true;
                                $graph_data[$item['id']]['edges'][$item['parent']] = $parent_resource_data;
                            } else {
                                throw new \LogicException(sprintf('定义的资源信息("%s")不存在', $item['parent']));
                            }
                        }
                    }
                    $graph = new Graph($graph_data);
                    $return[$field] = $graph->searchAndSort();
                }
            } else {
                $return[$field] = $config;
            }
        }
        return $return;
    }

    protected
    function resolveItem($configs, $code, $scope)
    {
        $find = null;
        foreach ($configs as $config) {
            if ($config['id'] == $code) {
                $find = $config;
                break;
            }
        }
        if (!$find) {
            switch ($scope) {
                case 'roles':
                    $record = AclRole::where('code', $code)->field(['code' => 'id', 'title', 'description'])->find();
                    if (!$record) return null;
                    break;
                case 'resources':
                    $record = AclResource::where('code', $code)->field(['code' => 'id', 'resource_id', 'title', 'description'])->find();
                    if (!$record) return null;
                    break;
                default:
                    return null;
            }
            $find['deprecated'] = false;
            $find['from_db'] = true;
        } else {
            $find['from_db'] = false;
        }
        if (!isset($find['deprecated'])) $find['deprecated'] = false;
        return $find;
    }
}