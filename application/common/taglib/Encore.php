<?php
/**
 * Created by Moonpie Studio.
 * User: JohnZhang
 * Date: 2019/3/26
 * Time: 10:24
 */

namespace app\common\taglib;


use think\Template;
use think\template\TagLib;

class Encore extends TagLib
{
    protected $lookup;
    /** @var Template $tpl */
    protected $tpl;
    protected $tags = [
        'links' => ['attr' => 'id', 'close' => 0], //CSS部分
        'scripts' => ['attr' => 'id', 'close' => 0], //JS
        'asset' => ['attr' => 'id,type', 'close' => 0], //前端资源
    ];

    public function tagLinks($tag)
    {
        $prefix = '<?php echo ';
        $content = '"';
        $links = $this->getLookup()->getCssFiles($tag['id']);
        foreach ($links as $link) {
            $content .= sprintf('<link rel=\"stylesheet\" href=\"%s\" />', $link);
        }
        $content .= '"';
        $suffix = ';?>';
        return $prefix . $content . $suffix;
    }

    public function tagScripts($tag)
    {
        $prefix = '<?php echo ';
        $content = '"';
        $scripts= $this->getLookup()->getJavaScriptFiles($tag['id']);
        foreach ($scripts as $script) {
            $content .= sprintf('<script type=\"text/javascript\" src=\"%s\"></script>', $script);
        }
        $content .= '"';
        $suffix = ';?>';
        return $prefix . $content . $suffix;
    }
    public function tagAsset($tag)
    {
        $type = isset($tag['type']) ? $tag['type'] : 'style';
        $name = $tag['id'];
        $options = isset($tag['options']) ? (array) $tag['options'] : [];
        $sources = $this->getLookup()->getManifestFile($name);
        $prefix = '<?php echo ';
        $content = '"';
        switch ($type) {
            case 'js':
                foreach($sources as $source) {
                    $content .= sprintf('<script type=\"text/javascript\" src=\"%s\"></script>', $source);
                }
                break;
            case 'img':
                foreach ($sources as $source) {
                    $content .= sprintf('<img src=\"%s\" />', $source);
                }
                break;
            default:
                foreach ($sources as $source) {
                    $content .= sprintf('<link rel=\"stylesheet\" href=\"%s\" />', $source);
                }
                break;
        }
        $content .= '"';
        $suffix = ';?>';
        return $prefix . $content . $suffix;
    }

    protected function getLookup()
    {
        if (!isset($this->lookup)) {
            $this->lookup = new EncoreLookup($this->tpl->config('encore_entry_path'), $this->tpl->config('encore_manifest_path'),
                $this->tpl->config('encore_caching'));
        }
        return $this->lookup;
    }


}