<?php
/**
 * Created by PhpStorm.
 * User: Admin
 * Date: 2019/2/1 0001
 * Time: 12:57
 */

namespace app\shopkeeper\controller;


use cmf\controller\ShopkeeperBaseController;
use think\Db;

class PayController extends ShopkeeperBaseController
{

    //轮询查询订单状态
    public function get_status() {
        $vild_code = input('vild_code');
        $status = 0;
        if ($vild_code) {
            $status  = Db::name('account_statement')->where(['user_id'=>$this->user_id, 'vild_code'=>$vild_code])->order('id', 'desc')->value('status');
        }
        $this->success('获取成功', null, $status);
    }

    //轮询查询二维码付款订单状态
    public function get_qr_status() {
        $order_number = input('order_no');
        $alipay = new AlipayController();
        $this->success('获取成功', null, $alipay->get_status($order_number));
    }
}