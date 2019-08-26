<?php
/**
 * Created by billy.
 *
 * Name:
 * Author: billy
 * Date: 2019/2/16
 * Time: 1:23
 */
namespace api\user\controller;

use lianlian\llpay;
use api\task\model\OrderModel;
use api\user\model\BrushGuestWithdrawModel;
use app\admin\model\BrushGuestModel;
use cmf\controller\RestUserBaseController;
use dingtalk\DingTalk;
use think\db;

class CashController extends RestUserBaseController
{
    private $status = ['待审核','已通过','已拒绝'];
    /**
     * 当前余额
     * @throws \think\db\exception\DataNotFoundException
     * @throws \think\db\exception\ModelNotFoundException
     * @throws \think\exception\DbException
     */
    public function getBalance()
    {
        $brushGuestModel = new BrushGuestModel();
        $brushGuest = $brushGuestModel->where(['user_id'=>$this->userId])->find();

        if ($brushGuest) {
            $this->success('获取成功',['balance'=>$brushGuest['balance']]);
        } else {
            $this->error('未找到数据');
        }
    }

    /**
     * 申请提现
     * @throws \think\db\exception\DataNotFoundException
     * @throws \think\db\exception\ModelNotFoundException
     * @throws \think\exception\DbException
     * @throws \think\exception\PDOException
     */
    public function makeCash()
    {
        $cash = $this->request->param('cash','0','int');
        if ($cash<1) {
            $this->error('提现金额必须大于0');
            exit;
        }
        if ($cash<40) {
            $this->error('提现金额必须大于40');
            exit;
        }
        if (date('d')!=15) {
            $this->error('每个月的15号才能提现');
            exit;
        }
        $brushGuestModel = new BrushGuestModel();
        $brushGuestModel->startTrans();
        $brushGuest = $brushGuestModel->where(['user_id'=>$this->userId])->lock(true)->find();
        if ($brushGuest) {
            if ($brushGuest['balance'] < $cash) {
                $brushGuestModel->rollback();
                $this->error('余额不足');
                exit;
            }
            $balance = $brushGuest['balance'] - $cash;
            $flag = false;
            try {
                //写入提现记录
                $orderModel = new OrderModel();
                $cash_no = $orderModel->createOrderNumber();
                $number = 'CASH'.$cash_no;
                $time = time();
                $params = [
                    'number'=>$number,
                    'amount'=>$cash,
                    'balance' => $balance,
                    'withdraw_time' => $time,
                    'create_time' => $time,
                    'user_id' => $this->userId,
                ];
                $brushGuestWithdraw = new BrushGuestWithdrawModel();
                $id = $brushGuestWithdraw->insertGetId($params);
                if ($id>0){
                    $data = ['balance' => $balance];
                    $brushGuestModel->save($data, ['user_id'=>$this->userId]);
                    $data1 = array(
                        'order_no'=>$cash_no,
                        'real_name'=>$brushGuest['real_name'],
                        'bank_number'=>$brushGuest['bank_number'],
                    );
                    $msg = '用户【' . $this->user['real_name']. '】申请提现，手机号【' . $this->user['cellphone'] . '】，单号【' . $number. '】，提现金额【'.$cash.'】，提现后余额【'.$balance.'】';
                    DingTalk::push_msg_dingding($msg);
                    $data2 = array(
                        'order_no'=>'RW'.$data1['order_no'],
                        'money'=>$cash,
                        'create_time'=>time(),
                        'pay_way' => 1,
                        'type' => 2,
                        'purpose' => 6,
                        'user_id' => $this->userId,
                        'status' => 0,
                    );
                    $aid = Db::name('account_statement')->insertGetId($data2);
                    $dt_order = date('YmdHis',$data2['create_time']);
                    if ($aid) {
                        $data1['dt_order'] = $dt_order;
                        $ret = llpay::applypay($data1['order_no'],$data2['money'],$data1['dt_order'],$data1['real_name'],$data1['bank_number']);
                        if ($ret['status']=='ok') {
                            $llstatus = 1;
                            $brushGuestWithdraw->save(['status'=>1],['id'=>$id]);
                        } else {
                            $llstatus = 2;
                            $brushGuestWithdraw->save(['status'=>2],['id'=>$id]);
                        }
                        if ($llstatus>0) {
                            Db::name('account_statement')->update(['id'=>$aid,'status'=>$llstatus]);
                        }
                        $flag = true;
                        Db::commit();
                    }
                } else {
                    $flag = false;
                    $brushGuestModel->rollback();
                }
            } catch (\Exception $e){
                $brushGuestModel->rollback();
            }
            if ($flag) {
                $this->success('提现申请成功');
            } else {
                $brushGuestModel->rollback();
                $this->error('提现申请失败');
            }

        } else {
            $brushGuestModel->rollback();
            $this->error('未找到数据');
        }
    }

    /**
     * 提现记录
     */
    public function cashList()
    {
        $page = $this->request->param('page', 1,'intval');
        $brushGuestWithdraw = new BrushGuestWithdrawModel();
        $result = $brushGuestWithdraw->where(['user_id'=>$this->userId])->order('id DESC')->page($page,20)->select()->toArray();
        if ($result) {
            foreach ($result as $key => $row){
                $result[$key]['status_text'] = $this->status[$row['status']];
                $result[$key]['create_time_format'] = date('Y年m月d日H时i分', $row['create_time']);
                $result[$key]['withdraw_time_format'] = date('Y年m月d日H时i分', $row['withdraw_time']);
                $result[$key]['transactor_time_format'] = date('Y年m月d日H时i分', $row['transactor_time']);
            }
            $this->success('获取成功', $result);
        } else {
            $this->error('没有更多记录');
        }
    }
}