<?php
// +----------------------------------------------------------------------
// | ThinkCMF [ WE CAN DO IT MORE SIMPLE ]
// +----------------------------------------------------------------------
// | Copyright (c) 2013-2018 http://www.thinkcmf.com All rights reserved.
// +----------------------------------------------------------------------
// | Licensed ( http://www.apache.org/licenses/LICENSE-2.0 )
// +----------------------------------------------------------------------
// | Author: 小夏 < 449134904@qq.com>
// +----------------------------------------------------------------------
namespace app\shopkeeper\controller;

use cmf\controller\ShopkeeperBaseController;
use think\Db;

class IndexController extends ShopkeeperBaseController
{


    /**
     * 后台首页
     */
    public function index()
    {
        /*$content = hook_one('admin_index_index_view');

        if (!empty($content)) {
            return $content;
        }

        $adminMenuModel = new AdminMenuModel();
        $menus          = cache('admin_menus_' . session('SHOPKEEPER_ADMIN_ID'), '', null, 'admin_menus');

        if (empty($menus)) {
            $menus = $adminMenuModel->menuTree();
            cache('admin_menus_' . session('SHOPKEEPER_ADMIN_ID'), $menus, null, 'admin_menus');
        }

        $this->assign("menus", $menus);

        $result = Db::name('AdminMenu')->order(["app" => "ASC", "controller" => "ASC", "action" => "ASC"])->select();
        $menusTmp = array();
        foreach ($result as $item){
            //去掉/ _ 全部小写。作为索引。
            $indexTmp = $item['app'].$item['controller'].$item['action'];
            $indexTmp = preg_replace("/[\\/|_]/","",$indexTmp);
            $indexTmp = strtolower($indexTmp);
            $menusTmp[$indexTmp] = $item;
        }
        $this->assign("menus_js_var",json_encode($menusTmp));

        //$admin = Db::name("user")->where('id', cmf_get_current_admin_id())->find();
        //$this->assign('admin', $admin);*/

        $is_rec = Db::name('user')->alias('a')
            ->join('shopkeeper b','a.id=b.user_id')
            ->where(['a.id'=>$this->user_id])->field('b.is_rec')->find();
        $this->assign('is_rec', $is_rec);
        return $this->fetch();
    }
}
