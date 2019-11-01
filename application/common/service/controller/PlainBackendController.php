<?php


namespace app\common\service\controller;


use app\common\service\routing\Router;
use think\Controller;
use think\exception\HttpException;
use think\Request;
use think\Route;
use think\Session;

abstract class PlainBackendController extends Controller
{
    /** @var array $store 商家登录信息 */
    protected $store;

    /** @var string $route 当前控制器名称 */
    protected $controller = '';

    /** @var string $route 当前方法名称 */
    protected $action = '';

    /** @var string $route 当前路由uri */
    protected $routeUri = '';

    /** @var string $route 当前路由：分组名称 */
    protected $group = '';

    /** @var array $allowAllAction 登录验证白名单 */
    protected $allowAllAction = [
        // 登录页面
        'login/index',
    ];

    /* @var array $notLayoutAction 无需全局layout */
    protected $notLayoutAction = [
        // 登录页面
        'login/manager',
    ];
    protected $menuName = 'admin_panel';
    /**
     * 模型对象
     * @var \think\Model
     */
    protected $model = null;

    /**
     * 快速搜索时执行查找的字段
     */
    protected $searchFields = 'id';

    /**
     * 是否是关联查询
     */
    protected $relationSearch = false;

    /**
     * 后台初始化
     */
    protected function _initialize()
    {
        parent::_initialize();
        // 商家登录信息
        $this->store = Session::get($this->getAuthSessionKey());
        // 当前路由信息
        $this->getRouteinfo();
        // 验证登录状态
        $this->checkLogin();
        // 验证当前页面权限
        //        $this->checkPrivilege();
        // 全局layout
        $this->layout();
    }

    /**
     * @return string 获取用户登录的session键名
     */
    protected abstract function getAuthSessionKey();
    protected abstract function getUrlPrefix();

    /**
     * 全局layout模板输出
     * @throws \think\exception\DbException
     * @throws \Exception
     */
    private function layout()
    {
        // 验证当前请求是否在白名单
        if (!in_array($this->routeUri, $this->notLayoutAction)) {
            // 输出到view
            $this->assign([
                'base_url' => base_url(),
                'store_url' => $this->getUrlPrefix(),
                'group' => $this->group,                      // 当前域名
                'menus' => $this->menus(),                     // 后台菜单
                'setting' => null,        // 当前商城设置
                'request' => $this->request,              // Request对象
                'version' => get_version(),                    // 系统版本号
            ]);
        }
    }

    /**
     * 解析当前路由参数 （分组名称、控制器名称、方法名）
     */
    protected function getRouteinfo()
    {
        // 控制器名称
        $this->controller = toUnderScore($this->request->controller());
        // 方法名称
        $this->action = $this->request->action();
        // 控制器分组 (用于定义所属模块)
        $groupstr = strstr($this->controller, '.', true);
        $this->group = $groupstr !== false ? $groupstr : $this->controller;
        // 当前uri
        $this->routeUri = $this->controller . '/' . $this->action;
    }

    /**
     * 后台菜单配置
     * @return mixed
     * @throws \think\exception\DbException
     */
    protected function menus()
    {
        static $menus = [];
        if (empty($menus)) {
            /** @var \app\common\service\menu\MenuService $service */
            $service = app('menu.manager');
            $menus = $service->getMenusByName($this->menuName, null, 3);
            //dump($this->menuName); dump($menus);exit;
        }
        //        dump($menus);die;
        return $menus;
    }

    /**
     * 验证登录状态
     * @return bool
     */
    protected function checkLogin()
    {
        // 验证当前请求是否在白名单
        /*if (in_array($this->routeUri, $this->allowAllAction)) {
            return true;
        }*/
        if($this->isAllowAllAction($this->request)) return true;
        // 验证登录状态
        if (
            empty($this->store)
            || (isset($this->store['is_login']) && (int) $this->store['is_login'] !== 1)
        ) {
            $this->redirect('backend.login');
            $this->handleNeedLogin($this->request);
            return false;
        }
        //这里绑定信息
        app(true)->offsetSet('current_account', $this->store['admin_user']);
        return true;
    }
    protected abstract function handleNeedLogin(Request $request);

    /**
     * 返回封装后的 API 数据到客户端
     * @param int $code
     * @param string $msg
     * @param string $url
     * @param array $data
     * @return array
     */
    protected function renderJson($code = 1, $msg = '', $url = '', $data = [])
    {
        return compact('code', 'msg', 'url', 'data');
    }

    /**
     * 返回操作成功json
     * @param string $msg
     * @param string $url
     * @param array $data
     * @return array
     */
    protected function renderSuccess($msg = 'success', $url = '', $data = [])
    {
        return $this->renderJson(1, $msg, $url, $data);
    }

    /**
     * 返回操作失败json
     * @param string $msg
     * @param string $url
     * @param array $data
     * @return array
     */
    protected function renderError($msg = 'error', $url = '', $data = [])
    {
        return $this->renderJson(0, $msg, $url, $data);
    }

    /**
     * 获取post数据 (数组)
     * @param $key
     * @return mixed
     */
    protected function postData($key = null)
    {
        return $this->request->post(is_null($key) ? '' : $key . '/a');
    }

    /**
     * 获取post数据 (数组)
     * @param $key
     * @return mixed
     */
    protected function getData($key = null)
    {
        return $this->request->get(is_null($key) ? '' : $key);
    }
    /**
     * 生成查询所需要的条件,排序方式
     * @param mixed $searchfields 快速查询的字段
     * @param boolean $relationSearch 是否关联查询
     * @return array
     */
    protected function buildparams($searchfields = null, $relationSearch = null)
    {
        $searchfields = is_null($searchfields) ? $this->searchFields : $searchfields;
        $relationSearch = is_null($relationSearch) ? $this->relationSearch : $relationSearch;
        $search = $this->request->get("search/a", []);
        $filter = $this->request->get("filter/a", []);
        $op = $this->request->get("op/a", []);
        $sort = $this->request->get("sort", "id");
        $order = $this->request->get("order", "DESC");
        $offset = $this->request->get("offset", 0);
        $limit = $this->request->get("limit", 0);
        $filter = array_filter($filter ? $filter : []);
        $where = [];
        $tableName = '';
        if ($relationSearch) {
            if (!empty($this->model)) {
                $name = \think\Loader::parseName(basename(str_replace('\\', '/', get_class($this->model))));
                $tableName = $name . '.';
            }
            $sortArr = explode(',', $sort);
            foreach ($sortArr as $index => &$item) {
                $item = stripos($item, ".") === false ? $tableName . trim($item) : $item;
            }
            unset($item);
            $sort = implode(',', $sortArr);
        }
        if ($search) {
            $searcharr = is_array($searchfields) ? $searchfields : explode(',', $searchfields);
            foreach ($searcharr as $k => &$v) {
                $v = stripos($v, ".") === false ? $tableName . $v : $v;
            }
            unset($v);
            $where[] = [implode("|", $searcharr), "LIKE", "%{$search}%"];
        }
        foreach ($filter as $k => $v) {
            $sym = isset($op[$k]) ? $op[$k] : '=';
            if (stripos($k, ".") === false) {
                $k = $tableName . $k;
            }
            $v = !is_array($v) ? trim($v) : $v;
            $sym = trim(strtoupper(isset($op[$k]) ? $op[$k] : $sym));
            switch ($sym) {
                case '=':
                case '!=':
                    $where[] = [$k, $sym, (string) $v];
                    break;
                case 'LIKE':
                case 'NOT LIKE':
                case 'LIKE %...%':
                case 'NOT LIKE %...%':
                    $where[] = [$k, trim(str_replace('%...%', '', $sym)), "%{$v}%"];
                    break;
                case '>':
                case '>=':
                case '<':
                case '<=':
                    $where[] = [$k, $sym, intval($v)];
                    break;
                case 'FINDIN':
                case 'FINDINSET':
                case 'FIND_IN_SET':
                    $where[] = "FIND_IN_SET('{$v}', " . ($relationSearch ? $k : '`' . str_replace('.', '`.`', $k) . '`') . ")";
                    break;
                case 'IN':
                case 'IN(...)':
                case 'NOT IN':
                case 'NOT IN(...)':
                    $where[] = [$k, str_replace('(...)', '', $sym), is_array($v) ? $v : explode(',', $v)];
                    break;
                case 'BETWEEN':
                case 'NOT BETWEEN':
                    $arr = array_slice(explode(',', $v), 0, 2);
                    if (stripos($v, ',') === false || !array_filter($arr))
                        continue;
                    //当出现一边为空时改变操作符
                    if ($arr[0] === '') {
                        $sym = $sym == 'BETWEEN' ? '<=' : '>';
                        $arr = intval($arr[1]);
                    } else if ($arr[1] === '') {
                        $sym = $sym == 'BETWEEN' ? '>=' : '<';
                        $arr = intval($arr[0]);
                    } else {
                        $arr = array_map(function ($item) {
                            return intval($item);
                        }, $arr);
                    }
                    $where[] = [$k, $sym, $arr];
                    break;
                case 'RANGE':
                case 'NOT RANGE':
                    $v = str_replace(' - ', ',', $v);
                    $arr = array_slice(explode(',', $v), 0, 2);
                    if (stripos($v, ',') === false || !array_filter($arr))
                        continue;
                    //当出现一边为空时改变操作符
                    if ($arr[0] === '') {
                        $sym = $sym == 'RANGE' ? '<=' : '>';
                        $arr = $arr[1];
                    } else if ($arr[1] === '') {
                        $sym = $sym == 'RANGE' ? '>=' : '<';
                        $arr = $arr[0];
                    }
                    $where[] = [$k, str_replace('RANGE', 'BETWEEN', $sym) . ' time', $arr];
                    break;
                case 'NULL':
                case 'IS NULL':
                case 'NOT NULL':
                case 'IS NOT NULL':
                    $where[] = [$k, strtolower(str_replace('IS ', '', $sym))];
                    break;
                default:
                    break;
            }
        }
        $where = function ($query) use ($where) {
            foreach ($where as $k => $v) {
                if (is_array($v)) {
                    call_user_func_array([$query, 'where'], $v);
                } else {
                    $query->where($v);
                }
            }
        };
        return [$where, $sort, $order, $offset, $limit];
    }
    /**
     * @return \app\common\service\security\AccountInterface
     */
    protected function getCurrentAccount()
    {
        return $this->store['admin_user'];
    }
    protected function accessDeny($permission = null, $resource = null, $message = 'Access Deny')
    {
        $has_permission = app('current_account')->hasPermission($permission, $resource);
        if (!$has_permission) {
            throw new HttpException(403, $message);
        }
    }
    protected function isAllowAllAction(Request $request)
    {
        /** @var Router $router **/
        $router = app('routing.router');
        foreach($this->allowAllAction as $limit) {
            $route_info = Route::name($limit);
            if($route_info) {
                $check = $router->isLinkMatchRequest($route_info[0][0], $request);
                if($check) return true;
            }
        }
        return false;
    }
}