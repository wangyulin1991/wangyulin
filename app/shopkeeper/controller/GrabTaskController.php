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

use api\task\model\OrderModel;
use api\task\model\OrderStepModel;
use api\task\model\ProcessModel;
use api\task\model\ProcessStepModel;
use api\task\model\ShopModel;
use api\task\model\TaskModel;
use cmf\controller\ShopkeeperBaseController;
use think\Db;
use app\admin\model\Menu;

class GrabTaskController extends ShopkeeperBaseController
{
    private $order_status=['进行中','待审核','未通过','已打款','已完成','已过期','申诉中','已拒绝'];
    //订单查询
    public function index()
    {
        $where = 'a.businesses_id='.$this->user_id;
        $shop_name = input('shop_name');
        $start_time = input('start_time');
        $end_time = input('end_time');
        $order_no = input('order_no');
        $desc = input('desc');
        $keyword = input('keyword');
        $status = input('status');
        if ($start_time) {
            $where .= ' and a.create_time > '.strtotime($start_time);
            $this->assign('start_time', $start_time);
        }
        if ($end_time) {
            $where .= ' and a.create_time < '.strtotime($end_time);
            $this->assign('end_time', $end_time);
        }
        if (!empty($shop_name)) {
            $where .= " and d.name like '%$shop_name%'";
            $this->assign('shop_name', $shop_name);
        }
        if (!empty($order_no)) {
            $where .= " and a.order_number like '%$order_no%'";
            $this->assign('order_no', $order_no);
        }
        if (!empty($desc)) {
            $where .= " and d.name like '%$shop_name%'";
            $this->assign('desc', $desc);
        }
        if (!empty($keyword)) {
            $where .= " and c.keyword like '%$keyword%'";
            $this->assign('keyword', $keyword);
        }
        if (is_numeric($status)&& $status>-1) {
            $where .= " and a.status=".$status;
        }
        $this->assign('shop_name', $shop_name);
        $this->assign('status', $status);
        $orderModel = new OrderModel();
        $orders = $orderModel->alias('a')
            ->join('order_step ot', 'ot.order_id=a.id and ot.step_type =11', 'LEFT')
            ->join('user b', 'b.id=a.brush_guest_id')
            ->join('brush_guest bg', 'bg.user_id=a.brush_guest_id')
            ->join('task c', 'a.task_id=c.id')
            ->join('shop d', 'c.shop_id=d.id')
            ->field('d.name as shop_name, c.product_img, c.keyword, a.order_number, b.user_login, a.amount,a.create_time,bg.taobao_ww,bg.query_img,a.status,a.id,a.appeal_result,ot.input_text')
            ->where($where)
            ->order("a.id DESC")
            ->paginate(50);
        foreach ($orders as $k => $val) {
            if ($val['input_text']) {
                $orders[$k]['input_text'] = json_decode($val['input_text'], true)[0]['value'];
            }
        }
        $money = 0;
        foreach ($orders as $k => $v) {
            $money+=$v['amount'];
        }

        $shop = Db::name('shopkeeper')->alias('a')
            ->join('shop b','a.id=b.shopkeeper_id')
            ->where('a.user_id ='.$this->user_id)
            ->field('b.name')->select()->toArray();

        $this->assign('orders', $orders);
        $this->assign('page', $orders->render());
        $this->assign('total', $orders->total());
        $this->assign('shop', $shop);
        $this->assign('money', $money);
        return $this->fetch();
    }

    //异常订单 弃用
    public function ex_index()
    {
        $where = 'a.status = 3 and a.businesses_id='.$this->user_id;
        $shop_name = input('shop_name');
        $start_time = input('start_time');
        $end_time = input('end_time');
        $order_no = input('order_no');
        $desc = input('desc');
        $keyword = input('keyword');
        if ($start_time) {
            $where .= ' and a.create_time > '.strtotime($start_time);
            $this->assign('start_time', $start_time);
        }
        if ($end_time) {
            $where .= ' and a.create_time < '.strtotime($end_time);
            $this->assign('end_time', $end_time);
        }
        if (!empty($shop_name)) {
            $where .= " and d.name like '%$shop_name%'";
            $this->assign('shop_name', $shop_name);
        }
        if (!empty($order_no)) {
            $where .= " and a.order_number like '%$order_no%'";
            $this->assign('order_no', $order_no);
        }
        if (!empty($desc)) {
            $where .= " and c.reject_reason like '%$desc%'";
            $this->assign('desc', $desc);
        }
        if (!empty($keyword)) {
            $where .= " and c.keyword like '%$keyword%'";
            $this->assign('keyword', $keyword);
        }

        $orders = Db::name('order a')
            ->join('user b', 'b.id=a.brush_guest_id')
            ->join('task c', 'a.task_id=c.id')
            ->join('shop d', 'c.shop_id=d.id')
            ->field('d.name as shop_name, c.product_img, c.keyword, a.order_number, b.user_login, a.amount,a.create_time, a.status,a.id')
            ->where($where)
            ->order("a.id DESC")
            ->paginate(50);
        $money = 0;
        foreach ($orders as $k => $v) {
            $money+=$v['amount'];
        }
        $this->assign('orders', $orders);
        $this->assign('page', $orders->render());
        $this->assign('total', $orders->total());
        $this->assign('money', $money);
        return $this->fetch();
    }

    //申诉的订单
    public function appeal_index()
    {
        $where = 'a.status = 6 and a.businesses_id='.$this->user_id;
        $shop_name = input('shop_name');
        $start_time = input('start_time');
        $end_time = input('end_time');
        $order_no = input('order_no');
        $appeal_reason = input('appeal_reason');
        $keyword = input('keyword');
        if ($start_time) {
            $where .= ' and a.create_time > '.strtotime($start_time);
            $this->assign('start_time', $start_time);
        }
        if ($end_time) {
            $where .= ' and a.create_time < '.strtotime($end_time);
            $this->assign('end_time', $end_time);
        }
        if (!empty($shop_name)) {
            $where .= " and d.name like '%$shop_name%'";
            $this->assign('shop_name', $shop_name);
        }
        if (!empty($order_no)) {
            $where .= " and a.order_number like '%$order_no%'";
            $this->assign('order_no', $order_no);
        }
        if (!empty($appeal_reason)) {
            $where .= " and a.appeal_reason like '%$appeal_reason%'";
            $this->assign('appeal_reason', $appeal_reason);
        }
        if (!empty($keyword)) {
            $where .= " and c.keyword like '%$keyword%'";
            $this->assign('keyword', $keyword);
        }

        $orderModel = new OrderModel();
        $orders = $orderModel->alias('a')
            ->join('order_step ot', 'ot.order_id=a.id and ot.step_type =11', 'LEFT')
            ->join('user b', 'b.id=a.brush_guest_id')
            ->join('task c', 'a.task_id=c.id')
            ->join('shop d', 'c.shop_id=d.id')
            ->field('d.name as shop_name, c.product_img, c.keyword, a.order_number, a.appeal_reason, b.user_login, a.amount,a.create_time, a.status,a.id,ot.input_text')
            ->where($where)
            ->order("a.id DESC")
            ->paginate(50);
        foreach ($orders as $k => $val) {
            if ($val['input_text']) {
                $orders[$k]['input_text'] = json_decode($val['input_text'], true)[0]['value'];
            }
        }
        $money = 0;
        foreach ($orders as $k => $v) {
            $money+=$v['amount'];
        }
        $this->assign('orders', $orders);
        $this->assign('page', $orders->render());
        $this->assign('total', $orders->total());
        $this->assign('money', $money);
        return $this->fetch();
    }

    //进行中的订单
    public function ing_index()
    {
        $where = 'a.status = 0 and a.businesses_id='.$this->user_id;
        $shop_name = input('shop_name');
        $start_time = input('start_time');
        $end_time = input('end_time');
        $order_no = input('order_no');
        $desc = input('desc');
        $keyword = input('keyword');
        if ($start_time) {
            $where .= ' and a.create_time > '.strtotime($start_time);
            $this->assign('start_time', $start_time);
        }
        if ($end_time) {
            $where .= ' and a.create_time < '.strtotime($end_time);
            $this->assign('end_time', $end_time);
        }
        if (!empty($shop_name)) {
            $where .= " and d.name like '%$shop_name%'";
            $this->assign('shop_name', $shop_name);
        }
        if (!empty($order_no)) {
            $where .= " and a.order_number like '%$order_no%'";
            $this->assign('order_no', $order_no);
        }
        if (!empty($desc)) {
            $where .= " and c.reject_reason like '%$desc%'";
            $this->assign('desc', $desc);
        }
        if (!empty($keyword)) {
            $where .= " and c.keyword like '%$keyword%'";
            $this->assign('keyword', $keyword);
        }

        $orderModel = new OrderModel();
        $orders = $orderModel->alias('a')
            ->join('order_step ot', 'ot.order_id=a.id and ot.step_type =11', 'LEFT')
            ->join('user b', 'b.id=a.brush_guest_id')
            ->join('brush_guest bg', 'bg.user_id=a.brush_guest_id')
            ->join('task c', 'a.task_id=c.id')
            ->join('shop d', 'c.shop_id=d.id')
            ->field('d.name as shop_name, c.product_img, c.commission, c.product_price, c.keyword, a.order_number, b.user_login,bg.taobao_ww,bg.query_img, a.amount,a.create_time, a.status,a.id,ot.input_text')
            ->where($where)
            ->order("a.id DESC")
            ->paginate(50);
        foreach ($orders as $k => $val) {
            if ($val['input_text']) {
                $orders[$k]['input_text'] = json_decode($val['input_text'], true)[0]['value'];
            }
        }
        $money = 0;
        foreach ($orders as $k => $v) {
            $money+=$v['amount'];
        }
        $this->assign('orders', $orders);
        $this->assign('page', $orders->render());
        $this->assign('total', $orders->total());
        $this->assign('money', $money);
        return $this->fetch();
    }

    //预约订单列表
    public function appointment()
    {
        $where = 'c.shopkeeper_id='.$this->shopkeeper_id;
        $shop_name = input('shop_name');
        $start_time = input('start_time');
        $end_time = input('end_time');
        $status = input('status');
        if ($start_time) {
            $where .= ' and a.create_time > '.strtotime($start_time);
            $this->assign('start_time', $start_time);
        }
        if ($end_time) {
            $where .= ' and a.create_time < '.strtotime($end_time);
            $this->assign('end_time', $end_time);
        }
        if (!empty($shop_name)) {
            $where .= " and d.name like '%$shop_name%'";
            $this->assign('shop_name', $shop_name);
        }
        if (is_numeric($status)&& $status>-1) {
            $where .= " and a.status=".$status;
        }
        $this->assign('status', $status);
        $orders = Db::name('appointment a')
            ->join('user b', 'b.id=a.brush_guest_id')
            ->join('brush_guest bg', 'bg.user_id=b.id')
            ->join('task c', 'a.task_id=c.id')
            ->join('shop d', 'c.shop_id=d.id')
            ->field('d.name as shop_name, c.task_name,c.product_img, c.commission, c.product_price, c.keyword, b.user_login,bg.taobao_ww,bg.query_img,a.create_time, a.status,a.id')
            ->where($where)
            ->order("a.id DESC")
            ->paginate(50);
        $this->assign('orders', $orders);
        $this->assign('page', $orders->render());
        $this->assign('total', $orders->total());
        return $this->fetch();
    }

    /**
     * 审核订单
     */
    public function audit() {
        $id = input('id',0);
        $flag = false;
        if (request()->isPost()) {
            $status = input('status');
            $remark = input('remark');
            if (empty($status)) $this->error('请选择处理结果');
            if ($id && ($status || ($status == 3 && $remark))) {
                Db::startTrans();
                try{
                    if ($status == 2) {
                        $rs = Db::name('order')->where(['id'=>$id])->update(['status'=>$status]);
                        if ($rs) {
                            $info = Db::name('order a')
                                ->join('user b', 'a.brush_guest_id=b.id')
                                ->join('brush_guest c', 'c.user_id=b.id')
                                ->join('task d', 'd.id=a.task_id')
                                ->field('a.amount,c.id, d.process_id, c.p_user_id,c.product_price,c.deal_num')
                                ->where(['a.id'=>$id])
                                ->find();
                            $config = Db::name('process_config')->where(['id'=>$info['process_id']])->find();
                            $commission = $this->cal_commission($info['process_id'],$info['product_price'],$info['deal_num']);
                            $rs = Db::name('brush_guest')->where(['id'=>$info['id']])->setInc('balance', $commission);
                            if ($info['p_user_id']) {
                                $rs = Db::name('brush_guest')->where(['id'=>$info['p_user_id']])->setInc('balance', $config['parent_commission']);
                                $gf_id = Db::name('brush_guest')->where(['id'=>$info['p_user_id']])->value('p_user_id');
                                if ($gf_id) {
                                    $rs = Db::name('brush_guest')->where(['id'=>$gf_id])->setInc('balance', $config['ancestry_commission']);
                                }
                            }

                            if ($rs) {
                                $flag = true;
                                Db::commit();
                            }
                        }
                    } else {
                        $rs = Db::name('order')->where(['id'=>$id])->update(['status'=>$status, 'remark'=>$remark]);
                        if ($rs) {
                            $flag = true;
                            Db::commit();
                        }
                    }
                } catch (\Exception $e) {
                    Db::rollback();
                }
            }
            if ($flag) {
                $this->success('处理成功',url('grabTask/index'));
            } else {
                Db::rollback();
                $this->error('处理失败');
            }
        }
        $this->assign('order_id', $id);
        return $this->fetch();
    }

    public function appeal() {
        $id = input('id',0);
        $appeal_reason = input('appeal_reason');
        if ($id) {
            $rs = Db::name('order')->where(['id'=>$id])->update(['status'=>6, 'is_appeal'=>1, 'appeal_reason'=>$appeal_reason]);
            if ($rs) {
                $this->success('已提交申诉');
            }
        }
        $this->error('提交申诉失败');
    }

    /**
     * 审核预约订单界面
     */
    public function refuseDetail() {
        $refuseId = input('id',0);
        $this->assign('refuseId', $refuseId);
        return $this->fetch();
    }

    //拒绝预约订单之前
    public function beforeRefuse() {
        $refuseId = input('refuseId',0);
        $res = Db::name('appointment')->where('id',$refuseId)->value('order_id');
        if($res >0){
            return json(['msg'=>'已生成任务订单，不能拒绝','code'=>400]);
        }
    }
    /**
     * 预约订单处理
     */
    public function refuse_appointment() {
        $refuseId = input('id',0);
        $refuse_remark = input('refuse_remark');
        if (is_null($refuse_remark)) {
            return json(['msg'=>'拒绝原因必选','code'=>1002]);
            exit;
        }
        //return $refuse_remark;
        $flag = false;
        if($refuseId){
            $info = Db::name('task a')
                ->join('appointment b', 'a.id=b.task_id')
                ->field('a.id as task_id, a.sub_task_num, a.remain_task_num, b.*')
                ->where(['b.id'=>$refuseId])
                ->find();

            if ($info) {
                Db::startTrans();
                $redis = new \TPRedis(array('host' => \think\Config::get('database.REDIS_HOST'), 'auth' => \think\Config::get('database.REDIS_AUTH'), 'prefix' => \think\Config::get('database.REDIS_PREFIX')));
                try{
                    //拒绝
                    if($refuse_remark == 0){
                        //更改为黑名单
                        Db::name('brush_guest')->where('user_id',$info['brush_guest_id'])->update(['is_black' => 2]);
                        $refuse_remark = '黑号';
                    }
                    if($refuse_remark == 1){
                        $refuse_remark = '类目不符';
                    }
                    if($refuse_remark == 2){
                        $refuse_remark = '其他原因';
                    }
                    $rs = Db::name('appointment')->where(['id'=>$refuseId,'task_id'=>$info['task_id'], 'status'=> 1])->update(['status' => 0,'refuse_remark'=>$refuse_remark,'reject_time'=>time()]);
                    if ($rs) {
                        $cache_name = 'task_num_'.$info['task_id'];
                        $redis->lPush($cache_name,1);
                        $res = Db::name('task')->where(['id'=>$info['task_id']])->update(['remain_task_num'=>$info['remain_task_num']+1]);
                        if ($res) {
                            $flag = true;
                            Db::commit();
                        }
                    }
                } catch (\Exception $e) {
                    Db::rollback();
                }
            } else {
                $this->error('任务即将开始或已开始,不能再操作', url('grabTask/appointment'));
            }

            if ($flag) {
                return json(['msg'=>'处理成功！','code'=>200]);
            } else {
                Db::rollback();
                return json(['msg'=>'处理失败','code'=>400]);
            }

        }else{
            return json(['msg'=>'未找到任务','code'=>400]);
        }

    }

    /**
     * 审核预约订单
     */
    public function audit_appointment() {
        $id = input('id',0);
        $status = input('status');
        if (empty($status)) $this->error('请选择处理结果');
        if (request()->isGet()) {
            $flag = false;
            if ($id) {
                $info = Db::name('task a')
                    ->join('appointment b', 'a.id=b.task_id')
                    ->field('a.id as task_id, a.sub_task_num, a.remain_task_num, b.*')
                    ->where(['a.start_time'=>['gt', time()+1800], 'b.id'=>$id])
                    ->find();

                /*$count = Db::name('appointment')->where(['status'=>1, 'task_id'=>$info['task_id']])->count();
                if ($status == 1 && $count >= $info['sub_task_num']) {
                    $rs = Db::name('task')->where(['id'=>$info['task_id']])->update(['is_appointment'=>0]);
                    $this->error('已达到最大预约人数', url('grabTask/appointment'));
                }*/
                if ($info) {
                    Db::startTrans();
                    $redis = new \TPRedis(array('host' => \think\Config::get('database.REDIS_HOST'), 'auth' => \think\Config::get('database.REDIS_AUTH'), 'prefix' => \think\Config::get('database.REDIS_PREFIX')));
                    try{
                        //拒绝
                        $rs = Db::name('appointment')->where(['task_id'=>$info['task_id'], 'status'=> 1])->update(['status' => 0, 'reject_time'=>time()]);
                        if ($rs) {
                            $cache_name = 'task_num_'.$info['task_id'];
                            $redis->lPush($cache_name,1);
                            $rs = Db::name('task')->where(['id'=>$info['task_id']])->update(['remain_task_num'=>$info['remain_task_num']+1]);
                            if ($rs) {
                                $flag = true;
                                Db::commit();
                            }
                        }
                    } catch (\Exception $e) {
                        Db::rollback();
                    }
                } else {
                    $this->error('任务即将开始或已开始,不能再操作', url('grabTask/appointment'));
                }
            }
            if ($flag) {
                $this->success('处理成功',url('grabTask/appointment'));
            } else {
                Db::rollback();
                $this->error('处理失败',url('grabTask/appointment'));
            }
        }
    }

    //计算佣金
    private function cal_commission($process_id=0, $price=0, $deal_num=0) {
        $commission = 0;
        $money = $price*$deal_num;
        $config = Db::name('process_config')->where(['process_id'=>$process_id])->find();
        if ($config) {
            if ($config['type'] == 0) {
                $commission = Db::name('process_config a')
                    ->join('range_config b', 'a.id=b.process_config_id')
                    ->where(['a.process_id'=>$process_id, 'b.type'=>0])
                    ->value('b.commission');
            } else {
                $ranges = Db::name('process_config a')
                    ->join('range_config b', 'a.id=b.process_config_id')
                    ->field('b.*')
                    ->where(['a.process_id'=>$process_id, 'b.type'=>0])
                    ->select();
                foreach ($ranges as $key => $val) {
                    if ($money <= $val['end_price'] && $money > $val['start_price']) {
                        if ($val['range_type'] == 0) {
                            $commission += $val['range_num'];
                        } else if ($val['range_type'] == 1) {
                            $commission += ($money - $val['start_price'])*$val['range_num'];
                        } else if ($val['range_type'] == 2) {
                            $commission += ceil(($money - $val['start_price'])/$val['step_num'])*$val['range_num'];
                        }
                    } else if ($money > $val['end_price']) {
                        if ($val['range_type'] == 0) {
                            $commission += $val['range_num'];
                        } else if ($val['range_type'] == 1) {
                            $commission += ($val['end_price'] - $val['start_price'])*$val['range_num'];
                        } else if ($val['range_type'] == 2) {
                            $commission += ceil(($money - $val['start_price'])/$val['step_num'])*$val['range_num'];
                        }
                    }
                }
            }
        }
        return $commission;
    }

    //订单步骤进度
    public function steps() {
        $id = input('id');
        $steps = Db::name('order a')
            ->join('order_step b','a.id=b.order_id')
            ->join('process_step c', 'b.step_id=c.id')
            ->join('process_step d', 'a.now_step=d.id', 'left')
            ->where(['b.order_id'=>$id])
            ->field('a.now_step,c.step_instruction,b.*,c.step_sort,d.step_sort as now_step_sort')
            ->select();
        $data = array();
        foreach ($steps as $key => $val) {
            $data[$key] = $val;
            $data[$key]['step_instruction'] = strip_tags(htmlspecialchars_decode($val['step_instruction']));
            if ($val['input_text']) {
                $data[$key]['input_text'] = json_decode($val['input_text'], true);
            }
        }
        $this->assign('steps', $data);
        return $this->fetch();
    }

    //导出订单excel
    public function outExcelRecharge() {
        $where = 'a.businesses_id='.$this->user_id;
        $shop_name = input('shop_name');
        $start_time = input('start_time');
        $end_time = input('end_time');
        $status = input('status');
        if ($start_time) {
            $where .= ' and a.create_time > '.strtotime($start_time);
            $this->assign('start_time', $start_time);
        }
        if ($end_time) {
            $where .= ' and a.create_time < '.strtotime($end_time);
            $this->assign('end_time', $end_time);
        }
        if (!empty($shop_name)) {
            $where .= " and d.name like '%$shop_name%'";
            $this->assign('shop_name', $shop_name);
        }
        if (is_numeric($status)&& $status>-1) {
            $where .= " and a.status=".$status;
        }
        $orderModel = new OrderModel();
        $data = $orderModel->alias('a')
            ->join('order_step ot', 'ot.order_id=a.id and ot.step_type =11', 'LEFT')
            ->join('user b', 'b.id=a.brush_guest_id')
            ->join('brush_guest bg', 'bg.user_id=a.brush_guest_id')
            ->join('task c', 'a.task_id=c.id')
            ->join('shop d', 'c.shop_id=d.id')
            ->field('d.name as shop_name, a.order_number, b.user_login, a.amount,a.create_time,bg.taobao_ww,a.status,a.id,ot.input_text')
            ->where($where)->select()->toArray();

        foreach ($data as $k=>$v){
            $data[$k]['create_time'] = date('Y-m-d',$v['create_time']);
            $data[$k]['status'] = $v['status_text'] = $this->order_status[$v['status']];
            $data[$k]['input_text'] = json_decode($v['input_text'], true)[0]['value'];
        }
//        echo '<pre>';
//        var_dump($data);die;
        $field = array(
            'A' => array('id', 'ID'),
            'B' => array('shop_name', '店铺名称'),
            'C' => array('order_number', '订单编号'),
            'D' => array('input_text', '淘宝订单号'),
            'E' => array('amount', '金额'),
            'F' => array('taobao_ww', '淘宝旺旺'),
            'G' => array('create_time', '时间'),
            'H' => array('status', '状态')
        );
        $this->phpExcelList($field, $data, '订单列表_' . date('Y-m-d'));
    }

}
