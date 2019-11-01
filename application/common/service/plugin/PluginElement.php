<?php
/**
 * Copyright (c) 2018-2019.
 *  This file is part of the moonpie production
 *  (c) johnzhang <875010341@qq.com>
 *  This source file is subject to the MIT license that is bundled
 *  with this source code in the file LICENSE.
 */

namespace app\common\service\plugin;


use app\common\service\plugin\asset\AssetManager;
use app\common\service\plugin\migration\MigrationManager;
use app\common\util\XmlUtils;
use EasyWeChat\Kernel\Support\Arr;

class PluginElement implements \Serializable
{
    protected $elements = [];

    public function getTitle()
    {
        return $this->getElement('title');
    }

    public function setTitle($title)
    {
        return $this->setElement('title', $title);
    }

    public function getCode()
    {
        return $this->getElement('code');
    }

    public function getVersion()
    {
        return $this->getElement('version', '1.0.0');
    }

    public function getNamespace()
    {
        return $this->getElement('namespace');
    }

    public function getRootPath()
    {
        return $this->getElement('root_path');
    }

    public function getServiceProviderClasses()
    {
        return $this->getElement('providers', []);
    }

    public function getDependData()
    {
        $depends = $this->getElement('depends', []);
        $return = [];
        foreach ($depends as $depend) {
            $dependency = $depend['module'];
            $version = isset($depend['version']) ? $depend['version'] : '';
            $value = [];
            // Split out the optional project name.
            if (strpos($dependency, ':') !== false) {
                list($project_name, $dependency) = explode(':', $dependency);
                $value['project'] = $project_name;
            }
            // We use named subpatterns and support every op that version_compare
            // supports. Also, op is optional and defaults to equals.
            $p_op = '(?<operation>!=|==|=|<|<=|>|>=|<>)?';
            // Core version is always optional: 8.x-2.x and 2.x is treated the same.
            $p_core = '(?:' . preg_quote('1.x') . '-)?';
            $p_major = '(?<major>\d+)';
            // By setting the minor version to x, branches can be matched.
            $p_minor = '(?<minor>(?:\d+|x)(?:-[A-Za-z]+\d+)?)';
            $parts = explode('(', $dependency, 2);
            $value['name'] = trim($parts[0]);
            if (!empty($version)) {
                $parts[1] = $version;
                $value['original_version'] = ' (' . $parts[1];
                foreach (explode(',', $parts[1]) as $version) {
                    if (preg_match("/^\s*$p_op\s*$p_core$p_major\.$p_minor/", $version, $matches)) {
                        $op = !empty($matches['operation']) ? $matches['operation'] : '=';
                        if ($matches['minor'] == 'x') {
                            // Drupal considers "2.x" to mean any version that begins with
                            // "2" (e.g. 2.0, 2.9 are all "2.x"). PHP's version_compare(),
                            // on the other hand, treats "x" as a string; so to
                            // version_compare(), "2.x" is considered less than 2.0. This
                            // means that >=2.x and <2.x are handled by version_compare()
                            // as we need, but > and <= are not.
                            if ($op == '>' || $op == '<=') {
                                $matches['major']++;
                            }
                            // Equivalence can be checked by adding two restrictions.
                            if ($op == '=' || $op == '==') {
                                $value['versions'][] = ['op' => '<', 'version' => ($matches['major'] + 1) . '.x'];
                                $op = '>=';
                            }
                        }
                        $value['versions'][] = ['op' => $op, 'version' => $matches['major'] . '.' . $matches['minor']];
                    }
                }
            }
            $return[] = $value;
        }
        return $return;
    }

    public function unserialize($serialized)
    {
        $items = \unserialize($serialized);
        $this->elements = $items;
    }

    public function serialize()
    {
        return \serialize($this->elements);
    }

    public function getElement($key, $default = null)
    {
        return Arr::get($this->elements, $key, $default);
    }

    public function setElement($key, $value)
    {
        if (is_null($key)) {
            $this->elements = $value;
        } else {
            Arr::set($this->elements, $key, $value);
        }
        return $this;
    }

    public function isEnable()
    {
        return $this->getElement('enabled', false);
    }

    public static function loadFromXmlFile(\SplFileInfo $info)
    {
        $schemaLocation = __DIR__ . '/schema/manifest.xsd';
        $real_path = $info->getRealPath();
        $xml = XmlUtils::loadFile($real_path, $schemaLocation);
        //$xml = XmlUtils::loadFile($info->getRealPath());
        $root_namespace = $xml->lookupNamespaceUri($xml->namespaceURI);
        $xpath = new \DOMXPath($xml);
        $xpath->registerNamespace('x', $root_namespace);
        $simple_doms = $xpath->query('/x:manifest/x:title|/x:manifest/x:description|/x:manifest/x:code|/x:manifest/x:version|/x:manifest/x:category|/x:manifest/x:namespace');
        $element = new static();
        $element->setElement('root_path', dirname($real_path) . DS);
        foreach ($simple_doms as $simple_dom) {
            $element->setElement($simple_dom->tagName, $simple_dom->textContent);
        }
        /*if($element->getCode() == 'wechat_account') {
            foreach ($simple_doms as $simple_dom) {
                var_dump($simple_dom->tagName);
            }
            exit;
        }*/
        //处理配置相关
        $file_doms = $xpath->query('/x:manifest/x:routes/x:route|/x:manifest/x:helpers/x:helper|/x:manifest/x:providers/x:provider');
        /** @var \DOMNode $file_dom */
        foreach ($file_doms as $file_dom) {
            $attributes = $file_dom->attributes;
            $element_key = $file_dom->parentNode->nodeName;
            $element_value = $element->getElement($element_key, []);
            $name = $attributes->getNamedItem('name')->textContent;
            $target = $attributes->getNamedItem('target')->textContent;
            $element_value[$name] = $target;
            $element->setElement($element_key, $element_value);
        }
        //接下来处理命令行
        $commands_doms = $xpath->query('/x:manifest/x:commands');
        /** @var \DOMNode $command_dom */
        foreach ($commands_doms as $command_dom) {
            /** @var \DOMNode $childNode */
            foreach ($command_dom->childNodes as $childNode) {
                if ($childNode->nodeName == 'command') {
                    $commands[] = $childNode->textContent;
                }
            }
        }
        if (!empty($commands)) {
            $element->setElement('commands', $commands);
        }
        //接下来处理配置相关
        $config_doms = $xpath->query('/x:manifest/x:configs/x:config');
        $configs = [];
        /** @var \DOMNode $config_dom */
        foreach ($config_doms as $config_dom) {
            $attributes = $config_dom->attributes;
            /** @var \DOMNode $attribute */
            $tmp = [];
            foreach ($attributes as $attribute) {
                $tmp[$attribute->nodeName] = $attribute->textContent;
            }
            $tmp['file'] = $config_dom->textContent;
            if (!empty($tmp)) $configs[] = $tmp;
        }
        if (!empty($configs)) $element->setElement('configs', $configs);
        //接下来是钩子
        $hook_doms = $xpath->query('/x:manifest/x:hooks/x:hook');
        $hooks = [];
        /** @var \DOMNode $hook_dom */
        foreach ($hook_doms as $hook_dom) {
            $attributes = $hook_dom->attributes;
            /** @var \DOMNode $attribute */
            $tmp = [];
            foreach ($attributes as $attribute) {
                $tmp[$attribute->nodeName] = $attribute->textContent;
            }
            if (!empty($tmp)) $hooks[] = $tmp;
        }
        if (!empty($hooks)) $element->setElement('hooks', $hooks);
        //接下来是迁移
        $migrations = [];
        $migration_doms = $xpath->query('/x:manifest/x:migrations/x:migration');
        /** @var \DOMNode $migration_dom */
        foreach ($migration_doms as $migration_dom) {
            $version = $migration_dom->attributes->getNamedItem('version')->textContent;
            if (!isset($migrations[$version])) $migrations[$version] = [];
            //这里找migrations/migration/item
            foreach($migration_dom->childNodes as $child_node) {
                if($child_node->nodeName == 'item') {
                    $migrations[$version][] = $child_node->textContent;
                }
            }
        }

        if (!empty($migrations)) $element->setElement('migrations', $migrations);
        //接下啦是前端资源
        $assets = [];

        $assets_doms = $xpath->query('/x:manifest/x:assets/*');
        /** @var \DOMNode $assets_dom */
        foreach ($assets_doms as $assets_dom) {
            $field = $assets_dom->nodeName;
            if (!isset($assets[$field])) $assets[$field] = [];
            $tmp = [];
            foreach ($assets_dom->attributes as $asset_dom_attr) {
                $tmp[$asset_dom_attr->nodeName] = $asset_dom_attr->textContent;
            }
            if (!empty($tmp)) $assets[$field][] = $tmp;
        }
        if (!empty($assets)) $element->setElement('assets', $assets);
        //最后是模块依赖
        $depends = [];
        $depends_dom = $xpath->query('/x:manifest/x:depends/x:depend');
        /** @var \DOMNode $depend_dom */
        foreach ($depends_dom as $depend_dom) {
            $tmp = [];
            foreach ($depend_dom->childNodes as $child_node) {
                if (in_array($child_node->nodeName, ['module', 'version'])) $tmp[$child_node->nodeName] = $child_node->textContent;
            }
            if (!empty($tmp)) $depends[] = $tmp;
        }
        if (!empty($depends)) $element->setElement('depends', $depends);

        //现在新增解析，处理授权部分
        $acl_config = ['roles' => [], 'resources' => [], 'grants' => []];
        $roles_dom = $xpath->query('/x:manifest/x:acl/x:roles/x:role');
        /** @var \DOMNode $role_dom*/
        $role_keys = ['id', 'title', 'description', 'deprecated'];
        foreach ($roles_dom as $role_dom) {
            $tmp = ['parents' => [], 'deprecated' => false];
            if($role_dom->attributes) {
                foreach ($role_keys as $role_key) {
                    $role_value = $role_dom->attributes->getNamedItem($role_key);
                    if ($role_value) {
                        $tmp[$role_key] = XmlUtils::phpize($role_value->textContent);
                    }
                }
            }
            //接下来处理父元素
            $parent_doms = $xpath->query('./x:parents//x:parent', $role_dom);
            /** @var \DOMNode $parent_dom */
            foreach($parent_doms as $parent_dom) {
                if($parent_dom->attributes) {
                    $tmp['parents'][] = $parent_dom->attributes->getNamedItem('refer')->textContent;
                }
            }
            $tmp['parents'] = array_unique(array_filter($tmp['parents']));
            $acl_config['roles'][] = $tmp;
        }
        //开始处理资源部分
        $resource_doms = $xpath->query('/x:manifest/x:acl/x:resources/x:resource');
        /** @var \DOMNode $resource_dom*/
        $resource_keys = ['id', 'title', 'description', 'deprecated', 'class', 'parent'];
        foreach ($resource_doms as $resource_dom) {
            $tmp = ['deprecated' => false];
            if($resource_dom->attributes) {
                foreach ($resource_keys as $resource_key) {
                    $resource_value = $resource_dom->attributes->getNamedItem($resource_key);
                    if ($resource_value) {
                        $tmp[$resource_key] = XmlUtils::phpize($resource_value->textContent);
                    }
                }
            }
            if(!empty($tmp)) $acl_config['resources'][] = $tmp;
        }
        //开始处理授权规则部分
        $grant_doms = $xpath->query('/x:manifest/x:acl/x:grants/x:grant');
        /** @var \DOMNode $grant_dom*/
        $grant_keys = ['roleId', 'title', 'resourceId', 'deprecated', 'assertion', 'allowed'];
        foreach ($grant_doms as $grant_dom) {
            $tmp = ['privileges' => [], 'deprecated' => false];
            if($grant_dom->attributes) {
                foreach ($grant_keys as $grant_key) {
                    $grant_value = $grant_dom->attributes->getNamedItem($grant_key);
                    if ($grant_value) $tmp[$grant_key] = XmlUtils::phpize($grant_value->textContent);
                }
            }
            //接下来是权限名

            $privilege_doms = $xpath->query('./x:privileges//x:privilege', $grant_dom);
            /** @var \DOMNode $privilege_dom */
            foreach($privilege_doms as $privilege_dom) {
                $tmp['privileges'][] = $privilege_dom->textContent;
            }
            $acl_config['grants'][] = $tmp;
        }
        $element->setElement('acl_config', $acl_config);
        return $element;
    }

    public function onInstall(PluginManager $manager, $force)
    {
        //首先是数据迁移
        $migration_manager = new MigrationManager($manager, $this);
        $migration_manager->install($force);
        //其次是前端资源
        $asset_manager = new AssetManager($manager);
        $asset_manager->install($this, $force);
    }

    public function onUninstall(PluginManager $manager, $force)
    {
        //首先是数据迁移
        $migration_manager = new MigrationManager($manager, $this);
        $migration_manager->uninstall($force);
        //其次是前端资源
        $asset_manager = new AssetManager($manager);
        $asset_manager->uninstall($this, $force);
    }

    public function onUpgrade(PluginManager $manager, $force)
    {
        //首先是数据迁移
        $migration_manager = new MigrationManager($manager, $this);
        $migration_manager->upgrade($force);
        //其次是前端资源
        $asset_manager = new AssetManager($manager);
        $asset_manager->upgrade($this, $force);
    }

    public function getAssetConfig()
    {
        $assets = $this->getElement('assets', []);
        if (empty($assets)) return [];
        $return = [
            'code' => $this->getCode(),
            'basePath' => $this->getRootPath(),
            'copyFiles' => [],
            'entries' => [],
            'styleEntries' => [],
        ];
        foreach ($assets as $field => $asset_items) {
            if ($field == 'asset') {
                foreach ($asset_items as $asset_item) {
                    if ($asset_item['type'] == 'javascript') {
                        $map_field = isset($asset_item['shared']) && $asset_item['shared'] ? 'shareEntries' : 'entries';
                    } else {
                        $map_field = 'styleEntries';
                    }
                    $return[$map_field][] = [
                        'name' => $asset_item['entry'],
                        'src' => $asset_item['src'],
                    ];
                }
            } else if ($field == 'copy_file') {
                $return['copyFiles'] = $asset_items;
            }
        }
        return $return;
    }
}