<?php
/**
 * Created by billy.
 *
 * Name:
 * Author: billy
 * Date: 2019/2/15
 * Time: 22:40
 */

namespace app\admin\controller;

use cmf\controller\AdminBaseController;
use app\admin\model\UserModel;
use app\admin\model\BrushGuestModel;
use app\admin\model\OrderModel;
use think\Db;

class OrderController extends AdminBaseController
{
    /**
     * 订单列表
     * @return mixed
     * @throws \think\exception\DbException
     */
    public function index()
    {
        $status = $this->request->param('status',-1,'intval');
        $order_number = $this->request->param('order_number','','strip_tags');
        $task_no = $this->request->param('task_no','','strip_tags');
        $platform_no = $this->request->param('platform_no','','strip_tags');
        $buss_mobile = $this->request->param('mobile','','strip_tags');
        $cellphone = $this->request->param('cellphone','','strip_tags');
        $start_time = $this->request->param('start_time','');
        $end_time = $this->request->param('end_time','');
        $param = $this->request->param();
        $where = '';
        if ($start_time) {
            $start_time_init = strtotime($start_time);
            if ($where) {
                $where .= " and o.create_time>='{$start_time_init}'";
            } else {
                $where .= "o.create_time>='{$start_time_init}'";
            }
        }

        if ($end_time) {
            $end_time_init = strtotime($end_time);
            if ($where) {
                $where .= " and o.create_time<='{$end_time_init}'";
            } else {
                $where .= "o.create_time<='{$end_time_init}'";
            }
        }

        if ($status>-1) {
            if ($where) {
                $where .= " and o.status='{$status}'";
            } else {
                $where .= "o.status='{$status}'";
            }

        }

        if ($order_number) {
            if ($where) {
                $where .= " and o.order_number = '".$order_number."'";
            } else {
                $where .= "o.order_number = '".$order_number."'";
            }

        }

        if ($task_no) {
            if ($where) {
                $where .= " and t.task_no = '" . $task_no . "'";
            } else {
                $where .= "t.task_no = '" . $task_no . "'";
            }
        }

        if ($platform_no) {
            if ($where) {
                $where .= " and o.platform_no = '" . $platform_no . "'";
            } else {
                $where .= "o.platform_no = '" . $platform_no . "'";
            }
        }

        if ($buss_mobile) {
            if ($where) {
                $where .= " and u.mobile = '" . $buss_mobile . "'";
            } else {
                $where .= "u.mobile = '" . $buss_mobile . "'";
            }
        }

        if ($cellphone) {
            if ($where) {
                $where .= " and bg.cellphone = '" . $cellphone . "'";
            } else {
                $where .= "bg.cellphone = '" . $cellphone . "'";
            }
        }

        $this->assign('start_time', $start_time);
        $this->assign('end_time', $end_time);
        $this->assign('order_number', $order_number);
        $this->assign('task_no', $task_no);
        $this->assign('platform_no', $platform_no);
        $this->assign('buss_mobile', $buss_mobile);
        $this->assign('cellphone', $cellphone);
        $this->assign('status', $status+1);

        $orderModel = new OrderModel();
        $result = $orderModel->alias('o')
            ->join('order_step ot', 'ot.order_id=o.id and ot.step_type =11', 'LEFT')
            ->join('brush_guest bg', 'o.brush_guest_id=bg.user_id','LEFT')
            ->join('user u','o.businesses_id=u.id', 'LEFT')
            ->join('task t', 'o.task_id=t.id', 'LEFT')
            ->field('o.id, o.order_number, t.task_no, bg.taobao_ww,bg.real_name, bg.cellphone, u.mobile, o.status, o.create_time,ot.input_text')
            ->where($where)
            ->order("o.id DESC")
            ->paginate(100);
        foreach ($result as $key => $val) {
            if ($val['input_text']) {
                $result[$key]['input_text'] = substr(json_decode($val['input_text'], true)[0]['value'],0,20);
            }
        }
        $result->appends($param);
        $page = $result->render();
        $total = $result->count();

        $this->assign('order_status', order_status('',0));
        $this->assign('orders',$result);
        $this->assign('total',$total);
        $this->assign('page',$page);
        return $this->fetch();
    }

    //预约列表
    public function appointment()
    {
        $status = $this->request->param('status',-1,'intval');
        $task_no = $this->request->param('task_no','','strip_tags');
        $buss_mobile = $this->request->param('mobile','','strip_tags');
        $cellphone = $this->request->param('cellphone','','strip_tags');
        $start_time = $this->request->param('start_time','');
        $end_time = $this->request->param('end_time','');
        $param = $this->request->param();
        $where = '';
        if ($start_time) {
            $start_time_init = strtotime($start_time);
            if ($where) {
                $where .= " and a.create_time>='{$start_time_init}'";
            } else {
                $where .= "a.create_time>='{$start_time_init}'";
            }
        }

        if ($end_time) {
            $end_time_init = strtotime($end_time);
            if ($where) {
                $where .= " and a.create_time<='{$end_time_init}'";
            } else {
                $where .= "a.create_time<='{$end_time_init}'";
            }
        }

        if ($status>-1) {
            if ($where) {
                $where .= " and a.status='{$status}'";
            } else {
                $where .= "a.status='{$status}'";
            }

        }

        if ($task_no) {
            if ($where) {
                $where .= " and t.task_no = '" . $task_no . "'";
            } else {
                $where .= "t.task_no = '" . $task_no . "'";
            }
        }

        if ($buss_mobile) {
            if ($where) {
                $where .= " and u.mobile = '" . $buss_mobile . "'";
            } else {
                $where .= "u.mobile = '" . $buss_mobile . "'";
            }
        }

        if ($cellphone) {
            if ($where) {
                $where .= " and bg.cellphone = '" . $cellphone . "'";
            } else {
                $where .= "bg.cellphone = '" . $cellphone . "'";
            }
        }

        $this->assign('start_time', $start_time);
        $this->assign('end_time', $end_time);
        $this->assign('task_no', $task_no);
        $this->assign('buss_mobile', $buss_mobile);
        $this->assign('cellphone', $cellphone);
        $this->assign('status', $status);

        $result = Db::name('appointment')->alias('a')
            ->join('brush_guest bg', 'a.brush_guest_id=bg.user_id','LEFT')
            ->join('user u','a.shopkeeper_id=u.id', 'LEFT')
            ->join('task t', 'a.task_id=t.id', 'LEFT')
            ->field('a.id,a.task_id,t.task_no, bg.taobao_ww,bg.real_name, bg.cellphone, u.mobile, a.status, a.create_time,a.order_id')
            ->where($where)
            ->order("a.id DESC")
            ->paginate(20);
        $result->appends($param);
        $page = $result->render();
        $total = $result->count();

        $this->assign('order_status', order_status('',0));
        $this->assign('orders',$result);
        $this->assign('total',$total);
        $this->assign('page',$page);
        return $this->fetch();
    }

    //拒绝预约订单
    public function refuse_appointment(){
        $refuseId = input('id',0);
        $task_id = input('task_id',0);
        if($this->request->isPost()){
            $id = input('id');
            $task_id = input('task_id');
            $refuse_remark = input('refuse_remark');
            if (empty($refuse_remark)) {
                return json(['msg'=>'拒绝原因必填','code'=>1002]);
                exit;
            }
            $redis = new \TPRedis(array('host' => \think\Config::get('database.REDIS_HOST'), 'auth' => \think\Config::get('database.REDIS_AUTH'), 'prefix' => \think\Config::get('database.REDIS_PREFIX')));

            $rs = Db::name('appointment')->where('id',$id)->update(['status'=>0,'refuse_remark'=>$refuse_remark]);
            if($rs){
                $info = Db::name('task')->where('id',$task_id)->find();
                if($info['status']==1){
                    $cache_name = 'task_num_'.$task_id;
                    $redis->lPush($cache_name,1);
                    Db::name('task')->where(['id'=>$task_id])->setInc('remain_task_num');
                }else{
                    Db::name('shopkeeper')->where('id',$info['shopkeeper_id'])->setInc('money',$info['product_price']+$info['commission']);
                    $data = array(
                        'order_no' => $info['task_no'],
                        'money' => $info['product_price']+$info['commission'],
                        'shop_id'=> $info['shop_id'],
                        'balance' => Db::name('shopkeeper')->where(['id'=>$info['shopkeeper_id']])->value('money'),
                        'type' => 1,
                        'pay_way' => 0,
                        'purpose' => 3,
                        'user_id' => Db::name('shopkeeper')->where(['id'=>$info['shopkeeper_id']])->value('user_id'),
                        'status' => 1,
                        'create_time' => time(),
                    );
                    Db::name('account_statement')->insert($data);
                }
                return json(['msg'=>"拒绝成功",'code'=>200]);
            }else{
                return json(['msg'=>'操作失败','code'=>400]);
            }

        }else{
            $this->assign('refuseId',$refuseId);
            $this->assign('task_id',$task_id);
            return $this->fetch();
        }

    }

    //提前结束订单
    public function end(){
        $order_id = $this->request->param('order_id');
        if($order_id){
            $rs = Db::name('order')->where('id',$order_id)->update(['status'=>7]);
            $info = Db::name('task')->alias('a')
                ->join('order b','a.id = b.task_id')
                ->field('a.id as task_id,a.remain_task_num')
                ->where('b.id',$order_id)->find();
            $redis = new \TPRedis(array('host' => \think\Config::get('database.REDIS_HOST'), 'auth' => \think\Config::get('database.REDIS_AUTH'), 'prefix' => \think\Config::get('database.REDIS_PREFIX')));

            if($rs){
                $cache_name = 'task_num_'.$info['task_id'];
                $redis->lPush($cache_name,1);
                $res = Db::name('task')->where(['id'=>$info['task_id']])->update(['remain_task_num'=>$info['remain_task_num']+1]);

                return json(['msg'=>'已结束订单！','code'=>200]);
            }else{
                return json(['msg'=>'操作失败！','code'=>400]);
            }
        }else{
            return json(['msg'=>'订单错误！','code'=>400]);
        }

    }

    //异常的列表 弃用
    public function problem(){
        $status = $this->request->param('status',-1,'intval');
        $order_number = $this->request->param('order_number','','strip_tags');
        $task_no = $this->request->param('task_no','','strip_tags');
        $platform_no = $this->request->param('platform_no','','strip_tags');
        $buss_mobile = $this->request->param('buss_mobile','','strip_tags');
        $cellphone = $this->request->param('cellphone','','strip_tags');
        $start_time = $this->request->param('start_time','');
        $end_time = $this->request->param('end_time','');
        $where = '';
        if ($start_time) {
            $start_time_init = strtotime($start_time);
            if ($where) {
                $where .= " and o.create_time>='{$start_time_init}'";
            } else {
                $where .= "o.create_time>='{$start_time_init}'";
            }
        }

        if ($end_time) {
            $end_time_init = strtotime($end_time);
            if ($where) {
                $where .= " and o.create_time<='{$end_time_init}'";
            } else {
                $where .= "o.create_time<='{$end_time_init}'";
            }
        }

        if ($status>-1) {
            if ($where) {
                $where .= " and o.status='{$status}'";
            } else {
                $where .= "o.status='{$status}'";
            }

        } else {
            if ($where) {
                $where .= " and o.status = (2,5)";
            } else {
                $where .= "o.status in (2,5)";
            }
        }

        if ($order_number) {
            if ($where) {
                $where .= " and o.order_number = '".$order_number."'";
            } else {
                $where .= "o.order_number = '".$order_number."'";
            }

        }

        if ($task_no) {
            if ($where) {
                $where .= " and t.task_no = '" . $task_no . "'";
            } else {
                $where .= "t.task_no = '" . $task_no . "'";
            }
        }

        if ($platform_no) {
            if ($where) {
                $where .= " and o.platform_no = '" . $platform_no . "'";
            } else {
                $where .= "o.platform_no = '" . $platform_no . "'";
            }
        }

        if ($buss_mobile) {
            if ($where) {
                $where .= " and u.mobile = '" . $buss_mobile . "'";
            } else {
                $where .= "u.mobile = '" . $buss_mobile . "'";
            }
        }

        if ($cellphone) {
            if ($where) {
                $where .= " and bg.cellphone = '" . $cellphone . "'";
            } else {
                $where .= "bg.cellphone = '" . $cellphone . "'";
            }
        }

        $this->assign('start_time', $start_time);
        $this->assign('end_time', $end_time);
        $this->assign('order_number', $order_number);
        $this->assign('task_no', $task_no);
        $this->assign('platform_no', $platform_no);
        $this->assign('buss_mobile', $buss_mobile);
        $this->assign('cellphone', $cellphone);
        $this->assign('status', $status);

        $orderModel = new OrderModel();
        $result = $orderModel->alias('o')
            ->join('brush_guest bg', 'o.brush_guest_id=bg.user_id','LEFT')
            ->join('user u','o.businesses_id=u.id', 'LEFT')
            ->join('task t', 'o.task_id=t.id', 'LEFT')
            ->field('o.id, o.order_number, t.task_no, bg.taobao_ww, bg.cellphone, u.mobile, o.status, o.create_time')
            ->where($where)
            ->order("o.id DESC")
            ->paginate(20);
        $page = $result->render();
        $total = $result->count();

        $this->assign('order_status', [2=>'未通过',5=>'已过期']);
        $this->assign('orders',$result);
        $this->assign('total',$total);
        $this->assign('page',$page);
        return $this->fetch();
    }

    //申诉列表
    public function appeal(){
        $order_number = $this->request->param('order_number','','strip_tags');
        $task_no = $this->request->param('task_no','','strip_tags');
        $platform_no = $this->request->param('platform_no','','strip_tags');
        $buss_mobile = $this->request->param('buss_mobile','','strip_tags');
        $cellphone = $this->request->param('cellphone','','strip_tags');
        $start_time = $this->request->param('start_time','');
        $end_time = $this->request->param('end_time','');
        $where = 'o.status = 6';
        if ($start_time) {
            $start_time_init = strtotime($start_time);
            if ($where) {
                $where .= " and o.create_time>='{$start_time_init}'";
            } else {
                $where .= "o.create_time>='{$start_time_init}'";
            }
        }

        if ($end_time) {
            $end_time_init = strtotime($end_time);
            if ($where) {
                $where .= " and o.create_time<='{$end_time_init}'";
            } else {
                $where .= "o.create_time<='{$end_time_init}'";
            }
        }

        if ($order_number) {
            if ($where) {
                $where .= " and o.order_number = '".$order_number."'";
            } else {
                $where .= "o.order_number = '".$order_number."'";
            }

        }

        if ($task_no) {
            if ($where) {
                $where .= " and t.task_no = '" . $task_no . "'";
            } else {
                $where .= "t.task_no = '" . $task_no . "'";
            }
        }

        if ($platform_no) {
            if ($where) {
                $where .= " and o.platform_no = '" . $platform_no . "'";
            } else {
                $where .= "o.platform_no = '" . $platform_no . "'";
            }
        }

        if ($buss_mobile) {
            if ($where) {
                $where .= " and u.mobile = '" . $buss_mobile . "'";
            } else {
                $where .= "u.mobile = '" . $buss_mobile . "'";
            }
        }

        if ($cellphone) {
            if ($where) {
                $where .= " and bg.cellphone = '" . $cellphone . "'";
            } else {
                $where .= "bg.cellphone = '" . $cellphone . "'";
            }
        }

        $this->assign('start_time', $start_time);
        $this->assign('end_time', $end_time);
        $this->assign('order_number', $order_number);
        $this->assign('task_no', $task_no);
        $this->assign('platform_no', $platform_no);
        $this->assign('buss_mobile', $buss_mobile);
        $this->assign('cellphone', $cellphone);

        $orderModel = new OrderModel();
        $result = $orderModel->alias('o')
            ->join('brush_guest bg', 'o.brush_guest_id=bg.user_id','LEFT')
            ->join('user u','o.businesses_id=u.id', 'LEFT')
            ->join('task t', 'o.task_id=t.id', 'LEFT')
            ->field('o.id, o.appeal_reason, o.order_number, t.task_no, bg.taobao_ww, bg.cellphone, u.mobile, o.status, o.create_time')
            ->where($where)
            ->order("o.id DESC")
            ->paginate(20);
        $page = $result->render();
        $total = $result->count();

        $this->assign('orders',$result);
        $this->assign('total',$total);
        $this->assign('page',$page);
        return $this->fetch();
    }

    //处理申诉订单
    public function appeal_result() {
        $order_id = input('order_id',0);
        if ($this->request->isPost()) {
            $appeal_result = input('appeal_result');
            if (empty($appeal_result)) $this->error('处理结果不能为空');
            $rs = Db::name('order')->where(['id'=>$order_id])->update(['appeal_result'=>$appeal_result, 'status'=>4]);
            if ($rs) {
                orderDoMark();
                $orderModel = new OrderModel();
                $order = $orderModel->alias('a')->join('user b','a.businesses_id = b.id')->field('a.order_number, b.mobile')->where('id='.$order_id)->find();
                if ($order) {
                    orderDoMark($order['mobile'],[$order['order_number']]);
                }
                $this->success('处理成功',url('order/appeal'));
            } else {
                $this->error('处理失败',url('order/appeal'));
            }
        }
        return $this->fetch();
    }

    //查询步骤进度
    public function step_details() {
        $order_id = input('order_id',0);
        $list = Db::name('order_step a')
            ->join('process_step b', 'a.step_id=b.id')
            ->field('a.id,a.step_name,a.completed_time, a.step_id, a.input_text, b.step_instruction')
            ->where(['order_id'=>$order_id])
            ->select();
        $data = array();
        foreach ($list as $key => $val) {
            $data[$key] = $val;
            $data[$key]['step_instruction'] = htmlspecialchars_decode($val['step_instruction']);
            if ($val['input_text']) {
                $data[$key]['input_text'] = json_decode($val['input_text'], true);
            }
        }
        $this->assign('steps', $data);
        return $this->fetch();
    }

    //步骤进度编辑
    public function edit_details() {
        $param = $this->request->param();
        $arr = array();
        foreach ($param as $k=>$v){
            $res = Db::name('order_step')->where('id',$v['id'])->field('input_text')->find();
                foreach ($res as $k1 => $v1) {
                    $arr = json_decode($v1,true);
                        foreach ($v as $k3=>$v3) {
                            for ($i=0; $i<count($arr);$i++) {
                                if ($arr[$i]['param_name'] == $k3) {
                                    $arr[$i]['value'] = $v3;
                                }
                            }
                        }
                    $input_text= json_encode($arr,JSON_UNESCAPED_UNICODE);
                    Db::name('order_step')->where('id',$v['id'])->update(['input_text'=>$input_text]);

                }

        }

         $this->success('修改成功！');

    }
}