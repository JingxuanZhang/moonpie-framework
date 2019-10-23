<?php
/*
 *  Copyright (c) 2018-2019.
 *  This file is part of the moonpie production
 *  (c) johnzhang <875010341@qq.com>
 *  This source file is subject to the MIT license that is bundled
 *  with this source code in the file LICENSE.
*/

namespace app\common\service\filesystem\plugin;

use League\Flysystem\FileExistsException;
use League\Flysystem\Plugin\AbstractPlugin;
use think\File;

class ThinkUploadFilePlugin extends AbstractPlugin
{
    public function getMethod()
    {
        return 'invokeThinkUpload';
    }
    public function handle($method, $arguments)
    {
        $method = 'handle' . ucfirst($method);
        return call_user_func_array([$this, $method], $arguments);
    }
    public function handleGetThinkFileSavePath(File $file, $rule = null)
    {
        $savename = $file->getSaveName();
        if (is_null($savename)) $savename = true;
        // 自动生成文件名
        if (true === $savename) {
            if ($rule instanceof \Closure) {
                $savename = call_user_func_array($rule, [$this]);
            } else {
                switch ($rule) {
                    case 'date':
                        $savename = date('Ymd') . DS . md5(microtime(true));
                        break;
                    default:
                        if (in_array($rule, hash_algos())) {
                            $hash     = $file->hash($rule);
                            $savename = substr($hash, 0, 2) . DS . substr($hash, 2);
                        } elseif (is_callable($rule)) {
                            $savename = call_user_func($rule);
                        } else {
                            $savename = date('Ymd') . DS . md5(microtime(true));
                        }
                }
            }
        } elseif ('' === $savename || false === $savename) {
            $savename = $file->getInfo('name');
        }

        if (!strpos($savename, '.')) {
            $savename .= '.' . pathinfo($file->getInfo('name'), PATHINFO_EXTENSION);
        }

        return $savename;
    }
    public function handleUploadThinkFile(File $file, $saveName, $override = true)
    {
        $info = $file->getInfo();
        // 文件上传失败，捕获错误代码
        if (!empty($info['error'])) {
            return $this->getError($info['error']);
        }

        // 检测合法性
        if (!$file->isValid()) {
            return $this->getError('upload illegal files');
        }

        // 验证上传
        if (!$file->check()) {
            return $file->getError();
        }


        $fh = fopen($file->getRealPath(), 'r');
        if (!is_resource($fh)) return $this->getError('open upload file failed.');
        if ($override) {
            $ok = $this->filesystem->putStream($saveName, $fh);
            fclose($fh);
            if (!$ok) return $this->getError('cannot put contents to target file');
        } else {
            //不允许复写
            try {
                $ok = $this->filesystem->writeStream($saveName, $fh);
                if (!$ok) return $this->getError('write target file content failed');
            } catch (FileExistsException $e) {
                return $this->getError('target file has been exists.');
            } finally {
                fclose($fh);
            }
        }

        $metadata = $this->filesystem->getMetadata($saveName);
        if ($metadata) {
            return $metadata;
        }
        return $this->getError('cannot get upload file metadata');
    }
    protected function getError($error)
    {
        if (is_array($error)) {
            list($msg, $vars) = $error;
        } else {
            $msg  = $error;
            $vars = [];
        }

        return Lang::has($msg) ? Lang::get($msg, $vars) : $msg;
    }
}
