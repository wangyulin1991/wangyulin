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
use app\admin\model\Menu;

class MainController extends ShopkeeperBaseController
{

    /**
     *  欢迎页
     */
    public function index()
    {
        $user = Db::name('user a')
            ->join('shopkeeper b', 'a.id=b.user_id')
            ->field('a.id as user_id, a.user_nickname, b.alipay_no, a.qq, a.create_time, b.money')
            ->where(['a.id'=>cmf_get_current_user_id()])
            ->find();

        $tasks = Db::name('user a')
            ->join('shopkeeper b', 'a.id=b.user_id')
            ->join('shop c', 'b.id=c.shopkeeper_id')
            ->join('task d', 'c.id=d.shop_id')
            ->join('process e', 'd.process_id=e.id')
            ->field('c.name, c.type, d.*, e.name as task_type')
            ->where(['a.id'=>cmf_get_current_user_id(), 'd.status'=>['neq', 9]])
            ->group('d.task_no')
            ->select()->toArray();
        foreach ($tasks as $key => $val) {
            $tasks[$key]['com_count'] = Db::name('task t')->join('order o','o.task_id=t.id and o.status in (4,6)')->where(['t.task_no'=>$val['task_no']])->count();;
        }

        $task_count = Db::name('user a')
            ->join('shopkeeper b', 'a.id=b.user_id')
            ->join('shop c', 'b.id=c.shopkeeper_id')
            ->join('task d', 'c.id=d.shop_id')
            ->field('c.name, c.type, d.*')
            ->group('task_no')
            ->where(['a.id'=>cmf_get_current_user_id(), 'd.status'=>1])
            ->count();

        $order_count = Db::name('order')
            ->where(['businesses_id'=>cmf_get_current_user_id(), 'status'=>0])
            ->count();


        $records = Db::name('user a')
            ->join('account_statement b','a.id=b.user_id')
            ->field('b.*')
            ->where(['b.status'=>1, 'a.id'=>cmf_get_current_user_id()])
            ->order('b.create_time', 'desc')
            ->select();

        $this->assign('user', $user);
        $this->assign('records', $records);
        $this->assign('tasks', $tasks);
        $this->assign('task_count', $task_count);
        $this->assign('order_count', $order_count);
        return $this->fetch();
    }
}
