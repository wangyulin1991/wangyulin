<?php
/**
 * Created by PhpStorm.
 * User: Admin
 * Date: 2019/1/26 0026
 * Time: 16:17
 */

namespace app\shopkeeper\controller;


use cmf\controller\ShopkeeperBaseController;
use think\Db;
use app\shopkeeper\model\PlatformModel;

class ShopController extends ShopkeeperBaseController
{
    public function index() {

        /**搜索条件**/
        $user_id = cmf_get_current_user_id();
        $where = 'a.user_type = 2 and a.id='.$user_id;
        $shop_type = input('shop_type');
        $shop_name = input('shop_name');
        $start_time = input('start_time');
        $end_time = input('end_time');
        if ($start_time) {
            $where .= ' and c.create_time > '.strtotime($start_time);
            $this->assign('start_time', $start_time);
        }
        if ($end_time) {
            $where .= ' and c.create_time < '.strtotime($end_time);
            $this->assign('end_time', $end_time);
        }
        if (!empty($shop_name)) {
            $where .= " and c.name like '%$shop_name%'";
            $this->assign('shop_name', $shop_name);
        }
        if (!empty($shop_type)) {
            $where .= " and c.type = '$shop_type'";
        }
        $this->assign('shop_type', $shop_type);


        $shops = Db::name('user a')
            ->join('shopkeeper b', 'a.id=b.user_id')
            ->join('shop c', 'b.id=c.shopkeeper_id')
            ->join('platform d', 'd.id=c.type')
            ->field('c.*, a.mobile,b.section_id,a.qq, d.platform_name')
            ->where($where)
            ->order("c.create_time", 'desc')
            ->paginate(50);

        $list = array();
        foreach ($shops as $key => $val) {
            $list[$key] = $val;
            $count = Db::name('task')->where(['shop_id'=>$val['id'], 'shopkeeper_id'=>$this->shopkeeper_id])->count();
            $money = Db::name('account_statement')->where(['shop_id'=>$val['id'], 'user_id'=>$this->user_id])->sum('money');
            $list[$key]['count'] = intval($count,0);
            $list[$key]['money'] = intval($money,0);
        }
        // 获取分页显示
        $page = $shops->render();
        $this->assign("page", $page);
        $this->assign("shops", $list);
        return $this->fetch();
    }

    //添加店铺
    public function add() {

        if ($this->request->isAjax()) {
            $param = $this->request->param();
            $res = $this->validate($this->request->param(), 'Shop');
            if ($res !== true) {
                $this->error($res);
            } else {
                /*$count = Db::name('shop')->where(['status'=>1, 'shopkeeper_id'=>$this->shopkeeper_id])->count();
                if ($count >= 3) {
                    $this->error('拥有店铺数量已达上限');
                }*/
                $param['create_time'] = time();
                $param['shopkeeper_id'] = $this->shopkeeper_id;
                $res = Db::name('shop')->insertGetId($param);
                if ($res) {
                    $this->success('添加店铺成功!', url('shop/index'));
                } else {
                    $this->error('添加店铺失败!');
                }
            }
            $this->error('添加店铺失败!');
        }
        $platformModel = new PlatformModel();
        $result = $platformModel->where(['status'=>1])->select()->toArray();
        $this->assign('platforms', $result);
        return $this->fetch();
    }

    //查看店铺
    public function view() {
        $id = input('id');
        $shop = Db::name('user a')->join('shopkeeper b','a.id=b.user_id')
            ->join('shop c', 'b.id=c.shopkeeper_id')
            ->field('a.user_login, c.*')
            ->where(['c.id'=>$id])
            ->find();
        $shopkeepers = Db::name('user')->field('id, user_login')->where(['user_status'=>1, 'user_type'=>2])->select();
        $this->assign('shop', $shop);
        $this->assign('lists', json_encode($shopkeepers));
        return $this->fetch();
    }

    //编辑店铺
    public function edit() {
        $id = input('id');
        if ($this->request->isPost()) {
            $param = $this->request->param();
            $res = $this->validate($this->request->param(), 'Shop');
            $param['shopkeeper_id'] = $this->shopkeeper_id;
            if ($res !== true) {
                $this->error($res);
            } else {
                $res = Db::name('shop')->update($param);
                if ($res) {
                    $this->success('更新店铺成功!', url('shop/edit', array('id'=>$id)));
                } else {
                    $this->error('更新店铺失败!');
                }
            }
        }
        $shop = Db::name('user a')->join('shopkeeper b','a.id=b.user_id')
            ->join('shop c', 'b.id=c.shopkeeper_id')
            ->field('a.user_login, c.*')
            ->where(['c.id'=>$id])
            ->find();
        $shopkeepers = Db::name('user')->field('id, user_login')->where(['user_status'=>1, 'user_type'=>2])->select();

        $platformModel = new PlatformModel();
        $result = $platformModel->where(['status'=>1])->select()->toArray();
        $this->assign('platforms', $result);

        $this->assign('shop', $shop);
        $this->assign('lists', json_encode($shopkeepers));
        return $this->fetch();
    }
}