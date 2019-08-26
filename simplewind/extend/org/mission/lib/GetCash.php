<?php
namespace org\mission\lib;

use lianlian\llpay;
use org\mission\core\Step;
use dingtalk\DingTalk;
use think\db;
class GetCash extends Step
{
    public function execute()
    {
        if (!isset($this->user['cellphone'])&&!$this->user['cellphone']) {
            $this->res = ['status'=>false,'msg'=>'请先完善手机号'];
            return;
        } else {
            Db::startTrans();
            $order = Db::name('order')->lock(true)->find($this->nowStep['order_id']);
            $flag = false;
            if ($order) {
                if($order['apply_cash']>0){
                    Db::rollback();
                    $this->res = ['status'=>false,'msg'=>'已经申请了货款'];
                    return;
                }
                $order = Db::name('order')
                    ->alias('a')
                    ->join('task b','a.task_id=b.id')
                    ->where(['a.id'=>$this->nowStep['order_id']])
                    ->field('a.order_number, a.create_time, b.product_price,b.deal_num')
                    ->find();
                if (!$order){
                    $this->res = ['status'=>false,'msg'=>'未能找到订单'];
                    return;
                }
                if ($order['product_price'] != $this->data['GetCash']) {
                    $this->res = ['status'=>false,'msg'=>'货款金额不一致'];
                    return;
                }
                try{
                    $no_order = $order['order_number'];
//                    $no_order = $no_order.'7';

                    $data = array(
                        'order_no'=>'RW'.$no_order,
                        'money'=>$order['product_price'],
                        'create_time'=>time(),
                        'pay_way' => 1,
                        'type' => 3,
                        'purpose' => 5,
                        'user_id' => $this->user['id'],
                        'order_id' => $this->nowStep['order_id'],
                        'status' => 0,
                    );
                    $data1 = array(
                        'real_name'=>$this->user['real_name'],
                        'bank_number'=>$this->user['bank_number']
                    );
                    $id = Db::name('account_statement')->insertGetId($data);
                    $dt_order = date('YmdHis',$data['create_time']);
                    $llstatus = 0;
                    if ($id) {
                        $msg = '用户【' . $this->user['real_name']. '】申请货款，银行卡【'.$this->user['bank_number'].'】,手机号【' . $this->user['cellphone'] . '】，订单号【' . $order['order_number']. '】，金额【'.$order['product_price'].'】';
                        DingTalk::push_msg_dingding($msg);
                        $data['order_no'] = $no_order;
                        $data['dt_order'] = $dt_order;
                        /* $this->redirect('Llpay/applypay', array($data['order_no'], $data['money'],$data['dt_order']));*/
                        $ret  = llpay::applypay($data['order_no'],$data['money'],$data['dt_order'],$data1['real_name'],$data1['bank_number']);
                        if ($ret['status']=='ok') {
                            $llstatus = 1;
                        } else {
                            $llstatus = 2;
                        }
                        if ($llstatus>0) {
                            Db::name('account_statement')->update(['id'=>$id,'status'=>$llstatus]);
                        }
                        /* LlpayController::applypay($data['order_no'],$data['money'],$data['create_time'],$data1['real_name'],$data1['bank_number']);*/
                        Db::name('order')->where(['id'=>$this->nowStep['order_id']])->update(['apply_cash'=>1]);
                        $this->setFrontStepNochange();
                        $this->res = ['status'=>true];
                        $flag = true;
                        Db::commit();
                    } else{
                        $this->res = ['status'=>false,'msg'=>'调起支付失败'];
                    }
                }catch (\Exception $exception){
                    Db::rollback();
                    $this->res = ['status'=>false,'msg'=>'运行错误'];
                }

                if ($flag) {
                    return;
                } else {
                    Db::rollback();
                    return;
                }
            } else {
                Db::rollback();
                $this->res = ['status'=>false,'msg'=>'订单错误'];
                return;
            }
        }
    }

    private function setFrontStepNochange()
    {
        Db::name('order_step')->where(['order_id'=>$this->nowStep['order_id'], 'id'=>['<',$this->nowStep['id']]])->update(['is_change'=>0]);
    }
}