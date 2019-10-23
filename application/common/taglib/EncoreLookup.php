<?php
/**
 * Created by Moonpie Studio.
 * User: JohnZhang
 * Date: 2019/3/26
 * Time: 10:53
 */

namespace app\common\taglib;

use think\Cache;
use think\cache\Driver;
use think\Config;

/**
 * 实现Encore定位查找相关功能
 * Class EncoreLookup
 * @package app\common\taglib
 */
class EncoreLookup
{
    protected $entryPointPath;
    protected $manifestPath;
    /**
     * @var Driver|null
     */
    protected $cache;
    protected $cacheKey;
    private $entriesData;
    private $manifestData;
    private $returnedFiles = [];

    public function __construct($entryPointPath, $manifestPath, $cachingConfig)
    {
        $this->manifestPath = $manifestPath;
        $this->entryPointPath = $entryPointPath;
        $this->initCache($cachingConfig);
    }

    protected function initCache($cachingConfig)
    {
        if (isset($cachingConfig['enable']) && $cachingConfig['enable']) {
            $option = isset($cachingConfig['options']) ? $cachingConfig['options'] : Config::get('cache');
            $this->cache = Cache::connect($option, 'encore_lookup');
            $this->cacheKey = isset($cachingConfig['cache_key']) ? $cachingConfig['cache_key'] : 'cache:encore-lookup';
        } else {
            $this->cache = $this->cacheKey = null;
        }
    }


    public function getJavaScriptFiles($entryName)
    {
        return $this->getEntryFiles($entryName, 'js');
    }

    public function getCssFiles($entryName)
    {
        return $this->getEntryFiles($entryName, 'css');
    }

    /**
     * Resets the state of this service.
     */
    public function reset()
    {
        $this->returnedFiles = [];
    }

    private function getEntryFiles($entryName, $key)
    {
        $this->validateEntryName($entryName);
        $entriesData = $this->getEntriesData();
        $entryData = $entriesData['entrypoints'][$entryName];
        if (!isset($entryData[$key])) {
            // If we don't find the file type then just send back nothing.
            return [];
        }
        // make sure to not return the same file multiple times
        $entryFiles = $entryData[$key];
        $newFiles = array_values(array_diff($entryFiles, $this->returnedFiles));
        $this->returnedFiles = array_merge($this->returnedFiles, $newFiles);
        return $newFiles;
    }

    private function validateEntryName($entryName)
    {
        $entriesData = $this->getEntriesData();
        if (!isset($entriesData['entrypoints'][$entryName])) {
            $withoutExtension = substr($entryName, 0, strrpos($entryName, '.'));
            if (isset($entriesData['entrypoints'][$withoutExtension])) {
                throw new \InvalidArgumentException(sprintf('Could not find the entry "%s". Try "%s" instead (without the extension).', $entryName, $withoutExtension));
            }
            throw new \InvalidArgumentException(sprintf('Could not find the entry "%s" in "%s". Found: %s.', $entryName, $this->entryPointPath, implode(', ', array_keys($entriesData))));
        }
    }

    private function getEntriesData()
    {
        if (null !== $this->entriesData) {
            return $this->entriesData;
        }
        $cacheKey = $this->cacheKey . ':entrypoint';
        if ($this->cache) {
            if ($this->cache->has($cacheKey)) {
                return $this->entriesData = $this->cache->get($cacheKey, []);
            }
        }
        if (!file_exists($this->entryPointPath)) {
            throw new \InvalidArgumentException(sprintf('Could not find the entrypoints file from Webpack: the file "%s" does not exist.', $this->entryPointPath));
        }
        $this->entriesData = json_decode(file_get_contents($this->entryPointPath), true);
        if (null === $this->entriesData) {
            throw new \InvalidArgumentException(sprintf('There was a problem JSON decoding the "%s" file', $this->entryPointPath));
        }
        if (!isset($this->entriesData['entrypoints'])) {
            throw new \InvalidArgumentException(sprintf('Could not find an "entrypoints" key in the "%s" file', $this->entryPointPath));
        }
        if ($this->cache) {
            $this->cache->set($cacheKey, $this->entriesData);
        }
        return $this->entriesData;
    }
    public function getManifestFile($manifest)
    {
        $this->validateManifestName($manifest);
        $manifestsData = $this->getManifestData();

        if(!isset($manifestsData[$manifest])){
            return '';
        }
        // make sure to not return the same file multiple times
        $manifestFile = (array) $manifestsData[$manifest];
        $newFiles = array_values(array_diff($manifestFile, $this->returnedFiles));
        $this->returnedFiles = array_merge($this->returnedFiles, $newFiles);
        return $newFiles;
    }

    private function validateManifestName($manifestName)
    {
        $manifestsData = $this->getManifestData();
        if (!isset($manifestsData[$manifestName])) {
            $withoutExtension = substr($manifestName, 0, strrpos($manifestName, '.'));
            if (isset($manifestsData[$withoutExtension])) {
                throw new \InvalidArgumentException(sprintf('Could not find the manifest "%s". Try "%s" instead (without the extension).', $manifestName, $withoutExtension));
            }
            throw new \InvalidArgumentException(sprintf('Could not find the manifest "%s" in "%s". Found: %s.', $manifestName, $this->entryPointPath, implode(', ', array_keys($manifestsData))));
        }
    }

    private function getManifestData()
    {
        if (null !== $this->manifestData) {
            return $this->manifestData;
        }
        $cacheKey = $this->cacheKey . ':manifest';
        if ($this->cache) {
            if ($this->cache->has($cacheKey)) {
                return $this->manifestData = $this->cache->get($cacheKey, []);
            }
        }
        if (!file_exists($this->manifestPath)) {
            throw new \InvalidArgumentException(sprintf('Could not find the manifest file from Webpack: the file "%s" does not exist.', $this->manifestPath));
        }
        $this->manifestData = json_decode(file_get_contents($this->manifestPath), true);
        if (null === $this->manifestData) {
            throw new \InvalidArgumentException(sprintf('There was a problem JSON decoding the "%s" file', $this->manifestPath));
        }
        if ($this->cache) {
            $this->cache->set($cacheKey, $this->manifestData);
        }
        return $this->manifestData;
    }
}