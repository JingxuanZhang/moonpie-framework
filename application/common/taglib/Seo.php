<?php
/**
 * Created by Moonpie Studio.
 * User: JohnZhang
 * Date: 2019/5/8
 * Time: 15:16
 */

namespace app\common\taglib;


use think\Template;
use think\template\TagLib;

class Seo extends TagLib
{
    /** @var Template $tpl */
    protected $tpl;
    protected $tags = [
        'baidu' => ['attr' => 'id'],
    ];

    public function tagBaidu($tag, $content)
    {
        $id = $tag['id'];
        $field = $this->autoBuildVar($id);
        $parse_str = '<?php ';
        $parse_str .= ' $_result = ' . $field . '; ?>';
        if (empty($id)) {
            $str = <<<EOT
<script>
var _hmt = _hmt || [];
{$content}
</script>
EOT;
        } else {
            $str = <<<EOT
<script>
var _hmt = _hmt || [];
{$content}
(function() {
  var hm = document.createElement("script");
  hm.src = "https://hm.baidu.com/hm.js?{\$_result}";
  var s = document.getElementsByTagName("script")[0]; 
  s.parentNode.insertBefore(hm, s);
})();
</script>
EOT;
        }
        return $parse_str . $str;
    }
}