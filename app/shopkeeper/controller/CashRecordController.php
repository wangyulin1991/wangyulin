<?php
/**
 * Created by PhpStorm.
 * User: Admin
 * Date: 2019/2/1 0001
 * Time: 15:31
 */

namespace app\shopkeeper\controller;


use cmf\controller\ShopkeeperBaseController;
use think\Db;

class CashRecordController extends ShopkeeperBaseController
{
    //资金账目
    public function index() {
        $type = input('type');
        $start_time = input('start_time');
        $end_time = input('end_time');
        $where = 'a.user_id = '.$this->user_id;
        if ($type) {
            $where .= " and b.type = '$type'";
        }
        if ($start_time) {
            $where .= ' and b.create_time > '.strtotime($start_time);
            $this->assign('start_time', $start_time);
        }
        if ($end_time) {
            $where .= ' and b.create_time < '.strtotime($end_time);
            $this->assign('end_time', $end_time);
        }
        $this->assign('type', $type);

        $records = Db::name('account_statement a')
            ->join('shop b','b.id=a.shop_id', 'left')
            ->field('b.name, a.*')
            ->where($where)
            ->order('a.create_time', 'desc')
            ->paginate(30);
        $in_money = 0;
        $out_money = 0;
        foreach ($records as $key => $val) {
            if ($val['status'] == 1) {
                if ($val['type'] == 1) {
                    $in_money += $val['money'];
                } else {
                    $out_money += $val['money'];
                }
            }
        }
        $this->assign('records', $records);
        $this->assign('total', $records->total());
        $this->assign('page', $records->render());
        $this->assign('in_money', $in_money);
        $this->assign('out_money', $out_money);
        return $this->fetch();
    }

    //充值记录
    public function recharge() {
        //$where = $this->getParams($this->request->param());
        $status = input('status');
        $start_time = input('start_time');
        $end_time = input('end_time');
        $where = 'type=1 and purpose=1 and user_id='.$this->user_id;
        if (is_int($status)) {
            $where .= ' and status = '.$status;
        }

        if ($start_time) {
            $where .= ' and create_time >= '.strtotime($start_time);
        }

        if ($end_time) {
            $where .= ' and create_time <= '.strtotime($end_time.' 23:59:59');
        }

        $this->assign('status', $status);
        $this->assign('start_time', $start_time);
        $this->assign('end_time', $end_time);

        $list = Db::name('account_statement')
            ->field('*')
            ->where($where)
            ->order('create_time', 'desc')
            ->paginate(30);
        $money = 0;
        foreach ($list as $key => $val) {
            if($status == 1) {
                $money+= $val['money'];
            }
        }
        $this->assign('lists', $list);
        $this->assign('total', $list->total());
        $this->assign('page', $list->render());
        $this->assign('money', $money);
        return $this->fetch();
    }
}