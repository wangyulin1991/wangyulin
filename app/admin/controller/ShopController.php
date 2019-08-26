<?php
/**
 * Created by PhpStorm.
 * User: Admin
 * Date: 2019/1/26 0026
 * Time: 16:17
 */

namespace app\admin\controller;


use cmf\controller\AdminBaseController;
use think\Db;

class ShopController extends AdminBaseController
{
    //店铺列表
    public function index() {

        /**搜索条件**/
        $param = $this->request->param();
        $where = 'a.user_type = 2';
        $startTime = empty($param['start_time']) ? 0 : strtotime($param['start_time']);
        $endTime   = empty($param['end_time']) ? 0 : strtotime($param['end_time']);
        if (!empty($startTime)) {
            $where .= ' and c.ctime > '.$startTime;
            $this->assign('start_time', $param['start_time']);
        }
        if (!empty($endTime)) {
            $where .= ' and c.ctime < '.$endTime;
            $this->assign('end_time', $param['end_time']);
        }
        if (!empty($param['mobile'])) {
            $where .= ' and a.mobile = '.$param['mobile'];
            $this->assign('mobile', $param['mobile']);
        }
        if (!empty($param['qq'])) {
            $where .= ' and b.qq = '.$param['qq'];
            $this->assign('qq', $param['qq']);
        }

        if (!empty($param['section_id'])) {
            $where .= ' and b.section_id = '.$param['section_id'];
        }
        $this->assign('section_id', input('section_id'));

        $shops = Db::name('user a')
            ->join('shopkeeper b', 'a.id=b.user_id')
            ->join('shop c', 'b.id=c.shopkeeper_id')
            ->join('task d', 'c.id=d.shop_id', 'left')
            ->join('account_statement e', 'd.id=e.shop_id', 'left')
            ->join('section f', 'f.id=b.section_id')
            ->field('a.mobile, a.qq, b.alipay_no, c.*, count(d.id) as task_count, sum(e.money) as money,f.name as section_name')
            ->where($where)
            ->group('c.id')
            ->order("c.id DESC")
            ->paginate(15);
        // 获取分页显示
        $page = $shops->render();
        $this->assign("page", $page);
        $this->assign("shops", $shops);
        $this->assign('sections', Db::name('section')->select());
        return $this->fetch();
    }

    //添加店铺
    public function add() {

        if ($this->request->isPost()) {
            $param = $this->request->param();
            $res = $this->validate($this->request->param(), 'Shop');
            if ($res !== true) {
                $this->error($res);
            } else {
                $param['ctime'] = time();
                $res = Db::name('shop')->insertGetId($param);
                if ($res) {
                    $this->success('添加店铺成功!');
                } else {
                    $this->error('添加店铺失败!');
                }
            }
        }
        $shopkeepers = Db::name('user')->field('id, user_login')->where(['user_status'=>1, 'user_type'=>2])->select();
        $this->assign('lists', json_encode($shopkeepers));
        return $this->fetch();
    }

    //编辑店铺
    public function edit() {
        $id = input('id');
        if ($this->request->isPost()) {
            $param = $this->request->param();
            $res = $this->validate($this->request->param(), 'Shop');
            if ($res !== true) {
                $this->error($res);
            } else {
                $res = Db::name('shop')->update($param);
                if ($res) {
                    $this->success('更新店铺成功!');
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
        $this->assign('shop', $shop);
        $this->assign('lists', json_encode($shopkeepers));
        return $this->fetch();
    }


    /**
     * 冻结店铺
     */
    public function freeze() {
        $id = input('param.id', 0, 'intval');
        if (!$id) {
            $this->error('数据传输错误!');
        }
        Db::name('shop')->where(['id'=>$id])->setField('status', 0);
        $this->success('冻结成功');
        return $this->fetch();
    }

    /**
     * 激活店铺
     */
    public function active() {
        $id = input('param.id', 0, 'intval');
        if ($id) {
            Db::name('shop')->where(['id'=>$id])->setField('status', 1);
            $this->success("启用成功！", '');
        } else {
            $this->error('数据传入失败！');
        }
    }
}