<?php
/**
 * Created by PhpStorm.
 * User: Admin
 * Date: 2019/1/30 0030
 * Time: 14:47
 */

namespace app\admin\controller;
use cmf\controller\AdminBaseController;
use lianlian\llpay;
use think\Db;

class CashRecordController extends AdminBaseController
{
    //充值记录

    public function recharge() {
        //$where = $this->getParams($this->request->param());
        $param = $this->request->param();
        $status = input('status');
        $keyword = input('keyword');
        $start_time = input('start_time');
        $end_time = input('end_time');
        $cash_explain = input('cash_explain');
        $where = 'b.type=1 and b.purpose = 1';
        if (is_numeric($status)) {
            $where .= ' and b.status = '.$status;
        }

        if ($keyword) {
            $where .= " and (a.user_login like '%$keyword%' or a.mobile like '%$keyword%' or a.qq like '%$keyword%')";
        }

        if (!empty($param['cash_explain'])) {
            $where .= ' and b.cash_explain = '.$cash_explain;
        }

        if ($start_time) {
            $where .= ' and b.create_time >= '.strtotime($start_time);
        }
        if ($end_time) {
            $where .= ' and b.create_time <= '.strtotime($end_time. ' 23:59:59');
        }
        $this->assign('status', $status);
        $this->assign('keyword', $keyword);
        $this->assign('start_time', $start_time);
        $this->assign('end_time', $end_time);
        $this->assign('cash_explain', $cash_explain);

        $list = Db::name('user a')
            ->join('account_statement b', 'a.id=b.user_id')
            ->where($where)
            ->field('a.user_login, a.qq, b.*')
            ->order('b.create_time', 'desc')
            ->paginate(15);
        $list->appends($param);
        $this->assign('page', $list->render());
        $this->assign('total',$list->count());
        $this->assign('lists', $list);
        return $this->fetch();
    }

    //提现记录
    public function withdraw() {

        $param = $this->request->param();
        $status = input('status');
        $keyword = input('keyword');
        $start_time = input('start_time');
        $end_time = input('end_time');
        $real_name = input('real_name');
        $where = 'b.type=2 and b.purpose = 6';
        if (is_numeric($status)) {
            $where .= ' and b.status = '.$status;
        }

        if ($keyword) {
            $where .= " and (a.user_login like '%$keyword%' or a.mobile like '%$keyword%' or a.qq like '%$keyword%')";
        }
        if ($real_name) {
            $where .= ' and c.real_name = \''.$param['real_name'].'\'';
        }

        if ($start_time) {
            $where .= ' and b.create_time = '.strtotime($start_time);
        }
        if ($end_time) {
            $where .= ' and b.create_time <= '.strtotime($end_time. ' 23:59:59');
        }
        $this->assign('status', $status);
        $this->assign('keyword', $keyword);
        $this->assign('start_time', $start_time);
        $this->assign('end_time', $end_time);
        $this->assign('real_name', $real_name);
        $list = Db::name('user a')
            ->join('account_statement b', 'a.id=b.user_id')
            ->join('brush_guest c','c.user_id = a.id','left')
            ->where($where)
            ->field('a.user_login, a.qq, b.*')
            ->order('b.create_time', 'desc')
            ->paginate(15);
        $list->appends($param);
        $this->assign('page', $list->render());
        $this->assign('total',$list->count());
        $this->assign('lists', $list);
        return $this->fetch();
    }

    //再次手动提现
    public function again_withdraw(){
        $id = $this->request->param('id',0,'intval');
        $where = 'a.id ='.$id.' and a.type=2 and purpose=6 and status!=1';
        $data = Db::name('account_statement a')
            ->join('brush_guest b','b.user_id = a.user_id','left')
            ->where($where)
            ->field('a.order_no,a.money,a.create_time,b.real_name,b.bank_number')
            ->find();
        $data['dt_order'] = date('YmdHis',$data['create_time']);
        $ret  = llpay::applypay($data['order_no'],$data['money'],$data['dt_order'],$data['real_name'],$data['bank_number']);
        if ($ret['status']=='ok') {
            Db::name('account_statement')->update(['id'=>$id,'status'=>1]);
            $this->success("提现成功！", url("CashRecord/withdraw"));
        } else {
            $this->error('提现失败！',$ret['msg']);
        }

    }

    //打款记录
    public function cashList()
    {
        $param = $this->request->param();
        $status = input('status');
        $start_time = input('start_time');
        $end_time = input('end_time');
        $mobile = input('mobile');
        $real_name = input('real_name');
        $where = 'a.type=3';
        if (is_numeric($status)) {
            $where .= ' and a.status = '.$status;
        }
        if ($mobile) {
            $where .= ' and u.mobile = '.$mobile;
        }
        if ($real_name) {
            $where .= " and b.real_name like '%".$real_name."%'";
        }
        if ($start_time) {
            $where .= ' and a.create_time >= '.strtotime($start_time);
        }
        if ($end_time) {
            $where .= ' and a.create_time <= '.strtotime($end_time. ' 23:59:59');
        }
        $this->assign('status', $status);
        $this->assign('start_time', $start_time);
        $this->assign('end_time', $end_time);
        $list = Db::name('account_statement a')
            ->join('user u','a.user_id = u.id','left')
            ->join('brush_guest b','b.user_id = u.id','left')
            ->join('order o','a.order_id = o.id','left')
            ->where($where)
            ->field('a.*, b.real_name, u.mobile,u.user_nickname, o.order_number')
            ->order('a.id', 'desc')
            ->paginate(15);
        $list->appends($param);
        $this->assign('page', $list->render());
        $this->assign('total',$list->count());
        $this->assign('lists', $list);
        return $this->fetch();
    }

    //再次打款
    public function again_cash(){
        $id = $this->request->param('id',0,'intval');
        $where = 'a.id ='.$id.' and a.type=3 and purpose=5 and status!=1';
        $data = Db::name('account_statement a')
            ->join('brush_guest b','b.user_id = a.user_id','left')
            ->where($where)
            ->field('a.order_no,a.money,a.create_time,b.real_name,b.bank_number')
            ->find();

        $data['dt_order'] = date('YmdHis',$data['create_time']);
        $ret  = llpay::applypay($data['order_no'],$data['money'],$data['dt_order'],$data['real_name'],$data['bank_number']);
        if ($ret['status']=='ok') {
            Db::name('account_statement')->update(['id'=>$id,'status'=>1]);
            $this->success("打款成功！", url("CashRecord/cashList"));
        } else {
            $this->error('打款失败！',$ret['msg']);
        }

    }

    public function getResult(){
        $id = $this->request->param('id',0,'intval');
        $where = 'a.type=3 and a.id='.$id;
        $data =array();
        if ($id > 0) {
            $res = Db::name('account_statement a')
                ->join('user u','a.user_id = u.id','left')
                ->join('brush_guest b','b.user_id = u.id','left')
                ->where($where)->field('a.*, u.mobile, b.real_name, b.bank_number')->select();
            //print_r($res);
            $create_time = $res[0]['create_time'];
            $time = $create_time+2*60*60;//2小时后的时间戳
//            echo '2小时后'.date("Y-m-d H:i:s",$time);echo "<br/>";
//            die;
            if ($res) {
                //2小时之内调用连连查询
                if($time > time()){
                    $ll = llpay::findResult(str_replace('RW','',$res[0]['order_no']));
                    //var_dump($ll);die;
                    $data =[
                        'order_no'=>$res[0]['order_no'],
                        'money'=>$res[0]['money'],
                        'cellphone'=>$res[0]['mobile'],
                        'real_name'=>$res[0]['real_name'],
                        'bank_number'=>$res[0]['bank_number'],
                        'memo'=>$ll['msg']['memo'],
                        'settle_date'=>$ll['msg']['settle_date']
                    ];
                    $data2 = array(
                        'memo'=>$ll['msg']['memo'],
                        'settle_date'=>$ll['msg']['settle_date']
                    );
                if($ll['status'] == "ok"){
                        switch ($ll['msg']['result_pay']){
                            case "SUCCESS":
                                $data['result']="付款成功！";
                                $data2['result']=$data['result'];
                                $JsonData = json_encode($data2);
                                Db::name('account_statement')->where('id', $id )->update(['status' => 1,'notes_money' => $JsonData]);
                                break;
                            case "PROCESSING":
                                $data['result']="付款处理中！";
                                $data2['result']=$data['result'];
                                $JsonData = json_encode($data2);
                                Db::name('account_statement')->where('id', $id )->update(['status' => 1,'notes_money' => $JsonData]);
                                break;
                            case "FAILURE":
                                $data['result']="付款失败！";
                                $data2['result']=$data['result'];
                                $JsonData = json_encode($data2);
                                Db::name('account_statement')->where('id', $id )->update(['status' => 0,'notes_money' => $JsonData]);
                                break;
                            case "CLOSED":
                                $data['result']="付款关闭！";
                                $data2['result']=$data['result'];
                                $JsonData = json_encode($data2);
                                Db::name('account_statement')->where('id', $id )->update(['status' => 0,'notes_money' => $JsonData]);
                                break;
                            default:
                                $data['result']="连连订单创建失败！";
                                $data2['result']=$data['result'];
                                $JsonData = json_encode($data2);
                                Db::name('account_statement')->where('id', $id )->update(['status' => 0,'notes_money' => $JsonData]);
                        }
                    }else{
                        $data['result']=$ll['msg']['ret_msg'];
                        $data2['result']=$data['result'];
                        $JsonData = json_encode($data2);
                        Db::name('account_statement')->where('id', $id )->update(['status' => 0,'notes_money' => $JsonData]);
                    }
                }else{
                    $notes_money = json_decode($res[0]['notes_money'],true);
                    $data =[
                        'order_no'=>$res[0]['order_no'],
                        'money'=>$res[0]['money'],
                        'cellphone'=>$res[0]['mobile'],
                        'real_name'=>$res[0]['real_name'],
                        'bank_number'=>$res[0]['bank_number'],
                        'memo'=>$notes_money['memo'],
                        'settle_date'=>$notes_money['settle_date'],
                        'result'=>$notes_money['result'],
                    ];
                }
            }
        }
        $this->assign('data', $data);
        return $this->fetch();
    }

    //查看 提现
    public function drawResult(){
        $id = $this->request->param('id',0,'intval');
        $where = 'a.type=2 and a.purpose = 6 and a.id='.$id;
        $data =array();
        if ($id > 0) {
            $res = Db::name('account_statement a')
                ->join('user u','a.user_id = u.id','left')
                ->join('brush_guest b','b.user_id = u.id','left')
                ->where($where)->field('a.*, u.mobile, b.real_name, b.bank_number')->select();
            //print_r($res);die;
            $create_time = $res[0]['create_time'];
            $time = $create_time+2*60*60;//2小时后的时间戳
            if ($res) {
                if ($time > time()) {
                    $ll = llpay::findResult(str_replace('RW', '', $res[0]['order_no']));
                    //var_dump($ll);die;
                    $data = [
                        'order_no' => $res[0]['order_no'],
                        'money' => $res[0]['money'],
                        'cellphone' => $res[0]['mobile'],
                        'real_name' => $res[0]['real_name'],
                        'bank_number' => $res[0]['bank_number'],
                        'settle_date' => $ll['msg']['settle_date'],
                        'memo' => $ll['msg']['memo'],
                    ];
                    $data2 = array(
                        'memo'=>$ll['msg']['memo'],
                        'settle_date'=>$ll['msg']['settle_date']
                    );
                    if ($ll['status'] == "ok") {
                        switch ($ll['msg']['result_pay']) {
                            case "SUCCESS":
                                $data['result'] = "提现成功！";
                                $data2['result']=$data['result'];
                                $JsonData = json_encode($data2);
                                Db::name('account_statement')->where('id', $id )->update(['status' => 1,'notes_money' => $JsonData]);
                                break;
                            case "PROCESSING":
                                $data['result']="付款处理中！";
                                $data2['result']=$data['result'];
                                $JsonData = json_encode($data2);
                                Db::name('account_statement')->where('id', $id )->update(['status' => 0,'notes_money' => $JsonData]);
                                break;
                            case "FAILURE":
                                $data['result']="付款失败！";
                                $data2['result']=$data['result'];
                                $JsonData = json_encode($data2);
                                Db::name('account_statement')->where('id', $id )->update(['status' => 0,'notes_money' => $JsonData]);
                                break;
                            case "CLOSED":
                                $data['result']="付款关闭！";
                                $data2['result']=$data['result'];
                                $JsonData = json_encode($data2);
                                Db::name('account_statement')->where('id', $id )->update(['status' => 0,'notes_money' => $JsonData]);
                                break;
                            default:
                                $data['result'] = "连连订单创建失败！";
                                $data2['result']=$data['result'];
                                $JsonData = json_encode($data2);
                                Db::name('account_statement')->where('id', $id )->update(['status' => 0,'notes_money' => $JsonData]);
                        }
                    } else {
                        $data['result'] = $ll['msg']['ret_msg'];
                        $data2['result']=$data['result'];
                        $JsonData = json_encode($data2);
                        Db::name('account_statement')->where('id', $id )->update(['status' => 0,'notes_money' => $JsonData]);
                    }
                }else{
                    $notes_money = json_decode($res[0]['notes_money'],true);
                    $data =[
                        'order_no'=>$res[0]['order_no'],
                        'money'=>$res[0]['money'],
                        'cellphone'=>$res[0]['mobile'],
                        'real_name'=>$res[0]['real_name'],
                        'bank_number'=>$res[0]['bank_number'],
                        'memo'=>$notes_money['memo'],
                        'settle_date'=>$notes_money['settle_date'],
                        'result'=>$notes_money['result'],
                    ];
                }
            }
        }
        $this->assign('data', $data);
        return $this->fetch();
    }
}