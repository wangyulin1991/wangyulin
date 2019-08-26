<?php
/**
 * Created by billy.
 *
 * Name:
 * Author: billy
 * Date: 2019/2/7
 * Time: 15:17
 */
namespace api\task\controller;

use api\task\model\ProcessModel;
use api\task\model\ShopModel;
use api\user\model\NextIncomeLogModel;
use think\Db;
use cmf\controller\RestUserBaseController;
use api\task\model\TaskModel;
use api\task\model\OrderModel;
use api\task\model\ProcessStepModel;
use api\task\model\OrderStepModel;
use org\mission\StepFactory;
class IndexController extends RestUserBaseController
{
    //任务列表
    public function index(){
        $page = $this->request->param("page", 1, 'intval');
        $sort = $this->request->param("sort", '');
        $startTime = $this->request->param('start_time');
        $endTime = $this->request->param('endTime');
        $startTime = empty($startTime) ? 0 : strtotime($startTime);
        $endTime   = empty($endTime) ? 0 : strtotime($endTime);
        $process_id = $this->request->param('process_id');
        $is_appointment = $this->request->param('is_appointment','0', 'intval');
        $process_id = explode(',',$process_id);
        $process_ids = [];
        foreach ($process_id as $key => $value){
            if((int)$value>0){
                $process_ids[]=(int)$value;
            }
        }
        $config = Db::name('config')->where(['id'=>1])->find();
        //每月最大接单数
        $max_num_month = $config['max_num_month'];
        if (time() - $this->user['create_time'] < $config['reg_day']*86400) {
            $max_num_month = $config['max_task_count_new'];
        }
        $orderModel = new OrderModel();
        //该用户每月接单信息
        $res = $orderModel->where(['brush_guest_id'=>$this->userId, 'create_time'=>['between', [time()-30*86400, time()]]])->select();
        //$res = $orderModel->where(['brush_guest_id'=>'107'])->select();
        $count = $res->count();
        if ($count>=$max_num_month) {
            $this->error('已达到一月最大接单数');
            exit;
        }
        //同时接单数
        $count = $orderModel->alias('o')->join('task t','o.task_id = t.id')
            ->where(['o.brush_guest_id'=>$this->userId, 'o.status'=>['in', [0,1,2,3]], 'o.confirm_pay'=>0,'t.process_id'=>['not in',[4,11]]])->count();
        if ($count>=1) {
            $this->error('请先完成之前的任务');
            exit;
        }
        $orderInfo= $res->toArray();
        $data = array();
        foreach ($orderInfo as $k=>$val){
            $data[$k]= $val['shop_id'];
        }
        $num = array_count_values($data); //统计每个商铺id的数量
        //同一商铺每月最大接单数$config['max_num_user_month']
        foreach ($num as $k=>$v){
            if($v <= $config['max_num_user_month']){
                unset($num[$k]);
            }
        }
        $data2 = array();
        foreach ($num as $k=>$v){
            $data2[] = $k;
        }
        $shop_id = implode(",", $data2);
        $where = 't.status = 1 and t.remain_task_num > 0';
        if(!empty($shop_id)){
            $where .= ' and t.shop_id not in('.$shop_id.')';
        }
        //echo $where;die;
        if ($process_ids&&count($process_ids)>1) {
            $where .= ' and t.process_id in ('.implode(',',$process_ids).')';
        } else if ($process_ids&&count($process_ids)==1){
            $where .= ' and t.process_id ='.$process_ids[0];
        }
        if (!empty($startTime)) {
            $where .= ' and t.start_time >= '.$startTime;
        }
        if (!empty($endTime)) {
            $where .= ' and t.start_time < '.$endTime;
        }
        if (is_numeric($is_appointment)) {
            if ($is_appointment == 1) {
                $where .= ' and t.start_time >'.time().' and t.is_show = 1';
            } else {
                $where .= ' and t.start_time <='.time();
            }
        }
        $order = 't.create_time desc';
        if ($sort == 'commission_down') {
            $order = 't.commission desc';
        } elseif ($sort == 'commission_up') {
            $order = 't.commission asc';
        } elseif ($sort == 'time_down') {
            $order = 't.create_time desc';
        } elseif ($sort == 'time_up') {
            $order = 't.create_time asc';
        }

        //$where .= ' and b.platform_status = 1 and b.id_user = '.$this->userId;
        $taskModel = new TaskModel();
        $result = $taskModel->alias('t')
            ->join('process p', 't.process_id=p.id','LEFT')
            //->join('brush_platform b','p.platform_id = b.id_platform')
            ->field('p.name as task_type,t.*')
            ->where($where)->order('t.is_appointment asc, '.$order)
            ->page($page,20)
            ->select()->toArray();
        foreach ($result as $k=>$v){
            $result[$k]['task_name'] = mb_substr($v['task_name'],0,3).'***';
        }
        $this->success('获取成功！', $result);
    }

    public function getTaskInfo() {
        $id = $this->request->param("id", 0, 'intval');
        if (!$id) $this->error('传输错误');
        $taskModel = new TaskModel();
        $info = $taskModel->alias('a')
            ->join('shop b', 'a.shop_id=b.id')
            ->join('process c', 'a.process_id=c.id')
            ->field('a.*, b.name as shop_name, c.name as task_type, case when a.start_time <='.time().' then 1 else 0 end as is_grab')
            ->where(['a.id'=>$id])
            ->find();
        if ($info) {
            $info['product_img'] = cmf_get_image_url($info['product_img']);
            $info['task_name'] = mb_substr($info['task_name'],0,3).'***';
            $info['special_desc'] = $info['special_desc'].' 【请按要求做单！传错图片扣除佣金，请谨慎！】';
        }
        $this->success('获取成功', $info);
    }

    //预约任务
    public function appointment()
    {
        $id = $this->request->param("id", 0, 'intval');
        if ($id > 0) {
            if($this->user['audit_status'] != 1){
                $this->error('您的账号还未通过审核！');
                exit;
            }
//            $hao = Db::name('brush_guest')->where('user_id',$this->userId)->value('is_black');
//            if($hao == 4){
//                $this->error('您的账号暂时不能接任务！');
//                exit;
//            }

            $redis = new \TPRedis(array('host' => \think\Config::get('database.REDIS_HOST'), 'auth' => \think\Config::get('database.REDIS_AUTH'), 'prefix' => \think\Config::get('database.REDIS_PREFIX')));

            $config = Db::name('config')->where(['id'=>1])->find();
            $taskModel = new TaskModel();
            $task = $taskModel->alias('a')->join('shopkeeper b','a.shopkeeper_id=b.id')->field('a.*,b.user_id')->where(['a.id'=>$id])->find();
            $shopkeeper_uid = $task['user_id'];
            if (time() + 660 >= $task['start_time']) {
                $this->error('任务即将开始，无法预约，请及时抢单');
                exit;
            }

            $cache_name = 'task_num_'.$id;
            $count=$redis->lPop($cache_name);
            if (!$count){
                $this->error('预约人数已满！');
                exit;
            }

            //每月最大接单数
            $max_num_month = $config['max_num_month'];
            if (time() - $this->user['create_time'] < $config['reg_day']*86400) {
                $max_num_month = $config['max_task_count_new'];
            }
            $orderModel = new OrderModel();
            $count = $orderModel->where(['brush_guest_id'=>$this->userId, 'create_time'=>['between', [time()-30*86400, time()]]])->count();
            if ($count>=$max_num_month) {
                $redis->lPush($cache_name,1);
                $this->error('已达到每月最大接单数');
                exit;
            }

            //同时接单数
            $count = $orderModel->alias('o')->join('task t','o.task_id = t.id')
                ->where(['o.brush_guest_id'=>$this->userId, 'o.status'=>['in', [0,1,2,3]], 'o.confirm_pay'=>0,'t.process_id'=>['not in',[4,11]]])->count();
            if ($count>=1) {
                $redis->lPush($cache_name,1);
                $this->error('请先完成之前的任务');
                exit;
            }

            $count = Db::name('appointment')->where(['brush_guest_id'=>$this->userId, 'order_id'=>0,'status'=>1])->count();
            if ($count>=1) {
                $redis->lPush($cache_name,1);
                $this->error('已有预约任务！');
                exit;
            }

            $time = time();
            $today = date('Y-m-d');
            $lastdaytime = strtotime($today);
            $count = $orderModel->where("brush_guest_id = {$this->userId} and create_time>={$lastdaytime} and create_time < {$time}")->count();
            if ($count >= 3) {
                $redis->lPush($cache_name,1);
                $this->error('已达到一天最大接单数');
                exit;
            }

            //同一商铺每月最大接单数
            $count = $orderModel->where(['brush_guest_id'=>$this->userId, 'shop_id'=>$task['shop_id'], 'create_time'=>['between', [time()-30*86400, time()]]])->count();
            if ($count >= $config['max_num_user_month']) {
                $redis->lPush($cache_name,1);
                $this->error('已达同一店铺每月最大接单数');
                exit;
            }

            $ids = $taskModel->where(['task_no'=>$task['task_no']])->column('id');
            $count = $orderModel->where(['brush_guest_id'=>$this->userId, 'task_id'=>['in',$ids]])->count();
            if ($count>0) {
                $redis->lPush($cache_name,1);
                $this->error('您已经接过该任务');
                exit;
            }

            $has_app = Db::name('appointment')->where(['brush_guest_id'=>$this->userId, 'task_no'=>$task['task_no']])->count();
            if ($has_app) {
                $redis->lPush($cache_name,1);
                $this->error('您已经预约过该任务');
                exit;
            }

            //同一商铺每月预约单数
            $count = Db::name('appointment')->where(['brush_guest_id'=>$this->userId, 'shop_id'=>$task['shop_id']])->count();
            if ($count >= $config['max_num_user_month']) {
                $redis->lPush($cache_name,1);
                $this->error('已预约过该店铺任务');
                exit;
            }

            //预约被拒绝后，一个月之内不能接该商家的任何任务
            $count = Db::name('appointment')->where(['brush_guest_id'=>$this->userId, 'shopkeeper_id'=>$task['user_id'], 'status'=>0,'create_time'=>['between', [time()-30*86400, time()]]])->count();
            if ($count>0) {
                $redis->lPush($cache_name,1);
                $this->error('不能再预约该商家的任务');
                exit;
            }

            //判断用户是否已经有订单
            /*$orderModel = new OrderModel();
            $count = $orderModel->where(['brush_guest_id'=>$this->userId, 'status'=>0])->count();
            if ($count>0) {
                $this->error('请先完成之前的任务');
                exit;
            }*/



            $count = Db::name('appointment')->where(['task_id'=>$id, 'status'=>1])->count();
            if ($task['sub_task_num'] <= 0) {
                $this->error('预约人数已满');
                exit;
            }
            Db::startTrans();
            $task = $taskModel->lock(true)->where(['id'=>$id])->find();
            $flag=false;
            if ($task['status'] == 1 && $task['remain_task_num'] > 0) {
                try{
                    $key_word = '';
                    if (!empty($task['keyword'])) {
                        $keywords = explode(',',$task['keyword']);
                        shuffle( $keywords);
                        $key_word = $keywords[0];
                    }

                    $rs = Db::name('appointment')->insert(['task_id'=>$id, 'shop_id'=>$task['shop_id'], 'brush_guest_id'=>$this->userId, 'shopkeeper_id'=>$shopkeeper_uid, 'task_no'=>$task['task_no'],'status'=>1, 'keyword'=>$key_word, 'create_time'=>time()]);
                    if ($rs) {
                        $rs = $taskModel->where(['id'=>$id])->setDec('remain_task_num', 1);
                        if ($task['sub_task_num'] == 1) {
                            $taskModel->where(['id'=>$id])->update(['is_appointment' => 0]);//预约人数已满
                        }
                    }
                    if ($rs) {
                        $flag = true;
                        Db::commit();
                    }
                }catch (\Exception $e){
                    Db::rollback();
                    $this->error('预约失败,请稍后重试！'.$e);
                }
                if ($flag) {
                    $this->success('预约成功！');
                } else {
                    $this->error('预约失败,请稍后重试！！');
                }
            } else {
                Db::rollback();
                $this->error('预约失败,请稍后重试！');
            }
        } else {
            $this->error('错误的参数');
        }
    }

    //抢任务
    public function getTask()
    {
        $id = $this->request->param("id", 0, 'intval');
        //echo $id;die;
        if ($id > 0) {
            if($this->user['audit_status'] != 1){
                $this->error('您的账号还未通过审核！');
                exit;
            }
//            $hao = Db::name('brush_guest')->where('user_id',$this->userId)->value('is_black');
//            if($hao == 4){
//                $this->error('您的账号暂时不能接任务！');
//                exit;
//            }
            $where = 't.id ='.$id;
            $taskModel = new TaskModel();
            $result = $taskModel->alias('t')
                ->join('process p', 't.process_id=p.id','LEFT')
                ->join('platform pf','p.platform_id=pf.id','LEFT')
                ->field('p.name as task_type,pf.platform_name,pf.id as id_platform')
                ->where($where)
                ->find()->toArray();
            $res= Db::name('brush_platform')->where(['id_platform'=>$result['id_platform'],'id_user'=>$this->userId,'platform_status'=>1])->find();
//            if(!$res){
//                $this->error('请先认证'.$result['platform_name'].'平台，通过后才可接京东任务！');
//                exit;
//            }

            $redis = new \TPRedis(array('host' => \think\Config::get('database.REDIS_HOST'), 'auth' => \think\Config::get('database.REDIS_AUTH'), 'prefix' => \think\Config::get('database.REDIS_PREFIX')));
            $start_time = $redis->get('task_start_time_'.$id);

            if ($start_time && $start_time > time()) {
                $this->error('任务未开始抢单，请耐心等待！');
                exit;
            }

            $cache_name = 'task_num_'.$id;
            $count=$redis->lPop($cache_name);
            if (!$count){
                $this->error('任务已经被抢完！');
            }

            $task = $taskModel->alias('a')->join('shopkeeper b','a.shopkeeper_id=b.id')->field('a.*,b.user_id')->where(['a.id'=>$id])->find();
            if (!$start_time) {
                $start_time = $task['start_time'];
            }

            if ($start_time > time()) {
                $redis->lPush($cache_name,1);
                $this->error('任务未开始抢单，请耐心等待！');
                exit;
            }

            $config = Db::name('config')->where(['id'=>1])->find();

            //每月最大接单数
            $max_num_month = $config['max_num_month'];
            if (time() - $this->user['create_time'] < $config['reg_day']*86400) {
                $max_num_month = $config['max_task_count_new'];
            }

            $orderModel = new OrderModel();

            //预约被拒绝后，一个月之内不能接该商家的任何任务
            $count = Db::name('appointment')->where(['brush_guest_id'=>$this->userId, 'shopkeeper_id'=>$task['user_id'], 'status'=>0,'create_time'=>['between', [time()-30*86400, time()]]])->count();
            if ($count>0) {

                $redis->lPush($cache_name,1);
                $this->error('不能再接该商家的任务');
                exit;
            }

            $count = Db::name('appointment')->where(['task_no'=>$task['task_no'],'brush_guest_id'=>$this->userId])->count();
            if ($count > 0) {
                $redis->lPush($cache_name,1);
                $this->error('您已经预约过该任务');
                exit;
            }

            //同一商铺每月预约单数
            $count = Db::name('appointment')->where(['brush_guest_id'=>$this->userId, 'shop_id'=>$task['shop_id']])->count();
            if ($count >= $config['max_num_user_month']) {
                $redis->lPush($cache_name,1);
                $this->error('已预约过该店铺任务');
                exit;
            }

            $ids = $taskModel->where(['task_no'=>$task['task_no']])->column('id');
            $count = $orderModel->where(['brush_guest_id'=>$this->userId, 'task_id'=>['in',$ids]])->count();
            if ($count>0) {
                $redis->lPush($cache_name,1);
                $this->error('您已经接过该任务');
                exit;
            }

            $count = $orderModel->where(['brush_guest_id'=>$this->userId, 'businesses_id'=>$task['user_id'], 'create_time'=>['between', [time()-30*86400, time()]]])->count();
            if ($count>=$max_num_month) {
                $redis->lPush($cache_name,1);
                $this->error('已达到一月最大接单数');
                exit;
            }

            //同时接单数
            $count = $orderModel->alias('o')->join('task t','o.task_id = t.id')
                ->where(['o.brush_guest_id'=>$this->userId, 'o.status'=>['in', [0,1,2,3]], 'o.confirm_pay'=>0,'t.process_id'=>['not in',[4,11]]])->count();
            if ($count>=1) {
                $redis->lPush($cache_name,1);
                $this->error('请先完成之前的任务');
                exit;
            }

//            $count = $orderModel->where(['brush_guest_id'=>$this->userId, 'create_time'=>['egt',time()-86400], 'create_time'=>['lt',time()]])->count();
            $time = time();
            $today = date('Y-m-d');
            $lastdaytime = strtotime($today);
            $count = $orderModel->where("brush_guest_id = {$this->userId} and create_time>={$lastdaytime} and create_time < {$time}")->count();
            if ($count >= 3) {
                $redis->lPush($cache_name,1);
                $this->error('已达到一天最大接单数');
                exit;
            }

            $count = $orderModel->where(['brush_guest_id'=>$this->userId, 'shop_id'=>$task['shop_id'], 'create_time'=>['between', [time()-30*86400, time()]]])->count();
            if ($count >= $config['max_num_user_month']) {
                $redis->lPush($cache_name,1);
                $this->error('已达同一店铺每月最大接单数');
                exit;
            }

            $taskModel->startTrans();
            $task = $taskModel->lock(true)->where(['id'=>$id])->find();
            $flag=false;
            if ($task['status']==1 && $task['remain_task_num']>0) {
                try{
                    //获取店铺和商户信息
                    $shopModel = new ShopModel();
                    $shop = $shopModel->alias('a')->join('shopkeeper b','a.shopkeeper_id = b.id','LEFT')->field('a.id,a.name,a.shopkeeper_id,b.user_id')->where(['a.id'=>$task['shop_id']])->find();

                    //获取类型名称
                    $processModel = new ProcessModel();
                    $process = $processModel->field('name')->where(['id'=>$task['process_id']])->find();
                    //修改任务数量
                    $taskData = ['remain_task_num'=>$task['remain_task_num']-1];
                    $taskModel->save($taskData,['id'=>$id]);

                    //写入订单信息
                    $ctime = time();
                    $order_number = $orderModel->createOrderNumber($this->userId);
                    $key_word = '';
                    if (!empty($task['keyword'])) {
                        $keywords = explode(',',$task['keyword']);
                        shuffle( $keywords);
                        $key_word = $keywords[0];
                    }
                    $comment = Db::name('task_comment')->where(['task_no'=>$task['task_no'], 'is_used'=>0])->find();
                    $orderData = [
                        'order_number'=>$order_number,
                        //'time'=>$task['start_time'] + $task['aging'] * 3600,
                        'platform_no'=>$task['platform_id'],
                        'shop_id'=>$shop['id'],
                        'shop_name'=>$shop['name'],
                        'process_name'=>$process['name'],
                        'task_picture'=>$task['product_img'],
                        'key_word'=>$key_word,
                        'paging_account'=>'',
                        'amount' => $task['product_price'],
                        'commission' => $task['brush_guest_money'] + $comment['commission'],
                        'task_id' => $task['id'],
                        'task_name' => $task['task_name'],
                        'brush_guest_id'=> $this->userId,
                        'businesses_id'=> $shop['user_id'],
                        'create_time'=> $ctime,
                        'deadline'=> $task['order_time']+3*86400,
                        'comment_id'=> $comment?$comment['id']:0,
                    ];
                    $order_id = $orderModel->insertGetId($orderData);
                    if ($orderData['comment_id'] > 0) {
                        Db::name('task_comment')->where(['id'=>$orderData['comment_id']])->update(['is_used'=>1]);
                    }
                    if ($order_id>0) {
                        $need_step = explode(',',$task['fee_step']);
                        //获取任务步骤
                        $processStepModel = new ProcessStepModel();
                        $steps = $processStepModel->where(['process_id'=>$task['process_id'],'status'=>1])->order('step_sort asc')->select()->toArray();
                        $aging = 0;
                        if (count($steps)>0) {
                            $stepData = [];
                            $p_step_id = 0;
                            foreach ($steps as $key => $step) {
                                if ($step['is_base']==0 && !in_array($step['id'], $need_step)) {
                                    continue;
                                }
                                $stepData[] = [
                                    'order_id' =>$order_id,
                                    'step_id' => $step['id'],
                                    'p_step_id' => $p_step_id,
                                    'step_name' => $step['step_name'],
                                    'user_id' => $this->userId,
                                    'wait_time' => $step['wait_time'],//要等待时间
                                    'step_type' => $step['step_type'],
                                    'is_change' => $step['is_change'],
                                    'do_day' => $step['do_day'],
                                    'input_text'=>'',
                                    'check_input'=>'',
                                    'create_time' => $ctime
                                ];
                                $p_step_id = $step['id'];
                                $aging += $step['wait_time']*3600;
                            }
                            $orderStepModel = new OrderStepModel();
                            $orderStepModel->saveAll($stepData);

                            //$orderModel->save(['deadline'=>strtotime(date('Y-m-d',$aging + $ctime + 86400))], ['id'=>$order_id]);
                            $taskModel->commit();
                            $flag = true;
                        }
                    } else{
                        $taskModel->rollback();
                    }
                }catch (\Exception $e){
                    $taskModel->rollback();
                }
                if ($flag) {
                    $this->success('抢单成功！');
                } else {
                    $redis->lPush($cache_name,1);
                    $this->error('抢单失败,请稍后重试！');
                }
            } else {
                $redis->lPush($cache_name,1);
                $taskModel->rollback();
                $this->error('抢单失败,请稍后重试！');
            }
        } else {
            $this->error('错误的参数');
        }
    }

    public function getNowStep(){
        $order_id = $this->request->param("order_id", 0, 'intval');
        if($order_id > 0) {
            $orderModel = new OrderModel();
            $order = $orderModel->where(['id'=>$order_id])->find();
//            if ($order['deadline']<time()) {
//                $this->error('任务已过期');
//                exit;
//            }
            if ($order['status']>0) {
                $this->success('任务已完成',['status'=>$order['status']]);
                exit;
            }
            $orderStepModel = new OrderStepModel();
            if ($order['now_step']<1) {
                $step = $orderStepModel->alias('a')->join('process_step b','a.step_id = b.id','LEFT')->join('process_step_action c','a.step_type = c.id','LEFT')->field('a.*, b.step_instruction, c.action_input,c.action_type')->where(['a.order_id'=>$order_id, 'a.p_step_id'=>0])->find();
                if ($step) {
                    $orderModel->save(['now_step'=>$step['step_id']],['id'=>$order_id]);
                }
            } else {
                $step = $orderStepModel->alias('a')->join('process_step b','a.step_id = b.id','LEFT')->join('process_step_action c','a.step_type = c.id','LEFT')->field('a.*, b.step_instruction, c.action_input,c.action_type')->where(['a.order_id'=>$order_id, 'b.id'=>$order['now_step']])->find();
            }
            if ($step) {
                $step = $step->toArray();
            } else {
                $this->error('步骤出错，请联系管理员');
            }

            if (empty($step['action_type'])) {
                $this->error('步骤出错，请联系管理员');
            }

            $stepClass = StepFactory::getInstance($step['action_type']);
            $data = $stepClass->getStep($step);
            $action_input = json_decode($data['action_input'],true);
            unset($data['action_input']);
            $data['action_input']=array();
            foreach ($action_input as $input){
                $data['action_input'][]=$input;
            }
            //var_dump($data);die;
            /************************/
//            $shop_action_input = json_decode($data['shop_action_input'],true);
//            unset($data['shop_action_input']);
//            $data['shop_action_input']=array();
//            foreach ($shop_action_input as $input){
//                $data['shop_action_input'][]=$input;
//            }
            /************************/

            if($data['wait_time']>0){
                $data['wait_time'] = ($data['p_step_time'] + $data['wait_time'] * 3600) - time();
            }
            if ($data['wait_time']<0) {
                $data['wait_time'] =0;
            }
            $data['order_step_id'] = $data['id'];
            unset($data['id']);
            unset($data['function']);
            unset($data['shot_img']);
            //unset($data['step_instruction']);
            $this->success('获取成功', $data);
        } else {
            $this->error('错误的参数');
        }

    }

    public function getStep(){
        $order_step_id = $this->request->param("order_step_id", 0, 'intval');
        if($order_step_id > 0 ) {

//            $orderModel = new OrderModel();
//            $order = $orderModel->where(['id'=>$order_id])->find();
//            if ($order['deadline']<time()) {
//                $this->error('任务已过期');
//                exit;
//            }
            /*if ($order['status']>0) {
                $this->success('任务已完成',['status'=>$order['status']]);
                exit;
            }*/
            $orderStepModel = new OrderStepModel();
            $step = $orderStepModel->alias('a')->join('process_step b','a.step_id = b.id','LEFT')->join('process_step_action c','a.step_type = c.id','LEFT')->field('a.*, b.step_instruction, c.action_input,c.action_type')->where(['a.id'=>$order_step_id,'a.status'=>1])->find();
            $input_text =[];
            if ($step['input_text']) {
                $temp = json_decode($step['input_text'],true);
                foreach ($temp as $key2=> $value){
                    if (isset($value['input_type'])&&$value['input_type']=='image') {
                        $value['url']=cmf_get_image_preview_url($value['value']);
                    }
                    $input_text[] = $value;
                }
                $step['action_input'] = $input_text;
            } else {
                $step['action_input'] = json_decode($step['action_input'],JSON_UNESCAPED_UNICODE);
            }
            $step['order_step_id'] = $step['id'];
            unset($step['id']);
            unset($step['input_text']);

            $stepClass = StepFactory::getInstance($step['action_type']);
            $data = $stepClass->getStep($step);
            //unset($data['function']);
            //unset($data['shot_img']);
            //unset($data['step_instruction']);
            $this->success('获取成功', $data);
        } else {
            $this->error('错误的参数');
        }

    }

    public function getDoneSteps(){
        $order_id = $this->request->param("order_id", 0, 'intval');
        if($order_id > 0) {
            /*$orderModel = new OrderModel();
            $order = $orderModel->where(['id'=>$order_id])->find();*/
            $orderStepModel = new OrderStepModel();
            $steps = $orderStepModel->alias('a')->join('process_step b','a.step_id = b.id','LEFT')->join('process_step_action c','a.step_type = c.id','LEFT')->field('a.*, b.step_instruction, c.action_input,c.action_type')->where(['a.order_id'=>$order_id, 'a.status'=> 1])->order('b.step_sort')->select();
            foreach ($steps as $key=>$step){
                if ($step['input_text']) {
                    $temp = json_decode($step['input_text'],true);
                    foreach ($temp as $key2=> $value){
                        if (isset($value['input_type'])&&$value['input_type']=='image') {
                            $temp[$key2]['value']=cmf_get_image_preview_url($value['value']);
                        }
                    }
                    $steps[$key]['input_text'] = $temp;
                } else {
                    $steps[$key]['input_text'] = [];
                }
                $steps[$key]['order_step_id'] = $step['id'];
                if (!$step['step_instruction']) {
                    $steps[$key]['step_instruction'] = '';
                }
            }
            $this->success('获取成功', $steps);
        } else {
            $this->error('错误的参数');
        }

    }

    public function doStep()
    {
        $order_step_id = $this->request->param("order_step_id", 0, 'intval');
        $data = $this->request->param();
        $orderStepModel = new OrderStepModel();

        $step = $orderStepModel->alias('a')->join('process_step b','a.step_id = b.id','LEFT')->join('process_step_action c','a.step_type = c.id','LEFT')->field('a.*, b.step_instruction, c.action_input,c.action_type')->where(['a.id'=>$order_step_id])->find();

        if ($step) {
            $orderModel = new OrderModel();
            $order = $orderModel->where(['id'=>$step['order_id'],'status'=>0])->find();
            if ($order) {
                if ($order['status']>0) {
                    $this->success('任务已完成',['status'=>$order['status']]);
                }
                if ($step['step_id'] >0 && $step['step_id'] > 0) {
                    $time = time();
                    if ($time<$step['p_step_time']+$step['wait_time']*3600) {
                        $second = $step['p_step_time']+$step['wait_time']*3600-$time;
                        $display_d = floor($second/3600/24);
                        $display_h = floor($second/3600%24);
                        $display_i = floor($second/60%60);
                        $display_s = floor($second%60);
                        $display_time = $display_d.'天'.$display_h.'时'.$display_i.'分'.$display_s.'秒';
                        $this->error($display_time.'后可以执行此步骤');
                    }
                    if (empty($step['action_type'])) {
                        $this->error('步骤出错，请联系管理员');
                    }
                    if (isset($step['action_input'])&&!empty($step['action_input'])){
                        $step['action_input'] = json_decode($step['action_input'],true);
                    }

                    $stepClass = StepFactory::getInstance($step['action_type']);
                    $res = $stepClass->doStep($step,$data,$this->user);
                    if (!$res['status']) {
                        $this->error($res['msg']);
                        exit;
                    }
                    if (!$step['status']) {
                        if ($step['is_change'] == 0) {
                            $orderStepModel->save(['is_change'=>0], ['order_id'=>$step['order_id']]);
                        }
                        $nextStep = $orderStepModel->where(['order_id'=>$step['order_id'], 'p_step_id'=>$step['step_id']])->find();

                        if ($nextStep) {
                            if($step['do_day'] != 0){
                                $order_id = $step['order_id'];//获取订单id
                                $order_time = Db::name('task a')
                                    ->join('order b','a.id = b.task_id')
                                    ->field('a.*')
                                    ->where('b.id',$order_id)->value('order_time');//获取下单时间

                                $res_one = $orderStepModel->where(['order_id'=>$step['order_id'],'do_day'=>1])->field('id,order_id,step_name,input_text,status,do_day')->select()->toArray();
                                $res_two = $orderStepModel->where(['order_id'=>$step['order_id'],'do_day'=>2])->field('id,order_id,step_name,input_text,status,do_day')->select()->toArray();
                                $res_three = $orderStepModel->where(['order_id'=>$step['order_id'],'do_day'=>3])->field('id,order_id,step_name,input_text,status,do_day')->select()->toArray();
                                if($res_one){
                                    $end_one = end($res_one);//第一天任务最后一步
                                }
                                if($res_two){
                                    $end_two = end($res_two);//第二天任务最后一步
                                    $first_two = array_shift($res_two);//第二天任务第一步
                                }
                                if($res_three){//三天任务
                                    if($end_one['input_text'] && $nextStep['do_day'] == 2){
                                        $today = date('Y-m-d', time());
                                        $timestamp = strtotime($today)+86400+3600;//第二天开始时间戳
                                        $hour = floor(($timestamp - time())/3600);//等待的时间
                                        if(empty($first_two['input_text'])){
                                            $orderStepModel->save(['wait_time'=>$hour], ['id'=>$nextStep['id']]);
                                        }
                                    }
                                    $first_three = array_shift($res_three);//第三天任务第一步
                                    if($end_two['input_text'] && $nextStep['do_day'] == 3){
                                        $hour = floor(($order_time-time())/3600);
                                        if(empty($first_three['input_text'])){
                                            $orderStepModel->save(['wait_time'=>$hour], ['id'=>$nextStep['id']]);
                                        }
                                    }
                                }else{//二天任务
                                    if($end_one['input_text'] && $nextStep['do_day'] == 2){
                                        $hour = floor(($order_time-time())/3600);
                                        $orderStepModel->save(['wait_time'=>$hour], ['id'=>$nextStep['id']]);
                                    }
                                }
                            }
                            /*$nexStepInfo = $processStepModel->find($nextStep['step_id']);
                               if ($nexStepInfo['wait_time']>0) {
                                   $act_time = $time+$nexStepInfo['wait_time']*3600;
                               } else {
                                   $act_time = 0;
                               }*/
                            $orderStepModel->save(['p_step_time'=>$time], ['id'=>$nextStep['id']]);
                            $orderModel->save(['now_step'=>$nextStep['step_id']],['id'=>$step['order_id']]);
                        } else {
                            Db::startTrans();
                            $flag = false;
                            try {
                                $orderModel->save(['status'=>4],['id'=>$step['order_id']]);
                                $info = $orderModel->alias('a')
                                    ->join('user b', 'a.brush_guest_id=b.id')
                                    ->join('brush_guest c', 'c.user_id=b.id')
                                    ->join('task d', 'd.id=a.task_id')
                                    ->field('a.amount,a.businesses_id,a.commission, c.id, d.process_id, c.p_user_id, d.product_price, d.deal_num')
                                    ->where(['a.id'=>$step['order_id']])
                                    ->find();
                                $config = Db::name('process_config')->where(['process_id'=>$info['process_id']])->find();
                                $rs = Db::name('brush_guest')->where(['id'=>$info['id']])->setInc('balance', $info['commission']);
                                $rs = Db::name('brush_guest')->where(['id'=>$info['id']])->setInc('historical_income', $info['commission']);
                                if ($info['p_user_id']) {
                                    $rs = Db::name('brush_guest')->where(['user_id'=>$info['p_user_id']])->setInc('balance', $config['parent_commission']);
                                    $rs = Db::name('brush_guest')->where(['user_id'=>$info['p_user_id']])->setInc('n_team_income', $config['parent_commission']);
                                    $rs = Db::name('brush_guest')->where(['user_id'=>$info['p_user_id']])->setInc('historical_income', $config['parent_commission']);
                                    $nIModel = new NextIncomeLogModel();
                                    $count = $nIModel->where(['from_user'=>$this->userId,'to_user'=>$info['p_user_id']])->find();
                                    if ($count){
                                        $nIModel->where(['from_user'=>$this->userId,'to_user'=>$info['p_user_id']])->setInc('money',$config['parent_commission']);
                                    } else {
                                        //$nIModel->save(['from_user'=>$this->userId,'p_user_id'=>$info['p_user_id'],'to_user'=>$info['p_user_id'],'money'=>$config['parent_commission']]);
                                        Db::name('next_income_log')->insert(['from_user'=>$this->userId,'p_user_id'=>$info['p_user_id'],'to_user'=>$info['p_user_id'],'money'=>$config['parent_commission']]);
                                    }
                                    if ($rs) {
                                        $gf_id = Db::name('brush_guest')->where(['user_id'=>$info['p_user_id']])->value('p_user_id');

                                        if ($gf_id) {
                                            $rs = Db::name('brush_guest')->where(['user_id'=>$gf_id])->setInc('balance', $config['ancestry_commission']);
                                            $rs = Db::name('brush_guest')->where(['user_id'=>$gf_id])->setInc('nn_team_income', $config['ancestry_commission']);
                                            $rs = Db::name('brush_guest')->where(['user_id'=>$gf_id])->setInc('historical_income', $config['ancestry_commission']);
                                            $count = $nIModel->where(['from_user'=>$this->userId,'to_user'=>$gf_id])->find();
                                            if ($count){
                                                $nIModel->where(['from_user'=>$this->userId,'to_user'=>$gf_id])->setInc('money',$config['ancestry_commission']);
                                            } else {
                                                Db::name('next_income_log')->insert(['from_user'=>$this->userId,'p_user_id'=>$info['p_user_id'],'to_user'=>$gf_id,'money'=>$config['ancestry_commission']]);
                                            }
                                            if ($rs) {
                                                $flag = true;
                                                Db::commit();
                                            }
                                        } else {
                                            $flag = true;
                                            Db::commit();
                                        }
                                    }
                                } else {
                                    $flag = true;
                                    Db::commit();
                                }

                                //商户佣金
                                $spinfo = Db::name('shopkeeper')->where('user_id',$info['businesses_id'])->field('money,user_id,f_id,gf_id')->find();
                                if($spinfo['f_id']){
                                    $rs = Db::name('shopkeeper')->where(['user_id'=>$spinfo['f_id']])->setInc('money', 0.5);
                                    $count = Db::name('account_recommend')->where(['from_user'=>$spinfo['user_id'],'to_user'=>$spinfo['f_id']])->find();
                                    if ($count){
                                        Db::name('account_recommend')->where(['from_user'=>$spinfo['user_id'],'to_user'=>$spinfo['f_id']])->setInc('money',0.6);
                                    } else {
                                        $data = array(
                                            'from_user'=>$spinfo['user_id'],
                                            'to_user'=>$spinfo['f_id'],
                                            'f_id'=>$spinfo['f_id'],
                                            'money'=>0.5,
                                            'create_time' => time(),
                                            'grade' => '1',
                                        );
                                        Db::name('account_recommend')->insert($data);
                                    }
                                    if($rs){
                                        $gf_id = Db::name('shopkeeper')->where('user_id',$spinfo['f_id'])->value('f_id');
                                        if($gf_id){
                                            $rs = Db::name('shopkeeper')->where(['user_id'=>$gf_id])->setInc('money', 0.5);
                                            $count = Db::name('account_recommend')->where(['from_user'=>$spinfo['user_id'],'to_user'=>$gf_id])->find();
                                            if ($count){
                                                Db::name('account_recommend')->where(['from_user'=>$spinfo['user_id'],'to_user'=>$gf_id])->setInc('money',0.4);
                                            } else {
                                                $data = array(
                                                    'from_user'=>$spinfo['user_id'],
                                                    'to_user'=>$gf_id,
                                                    'f_id'=>$spinfo['f_id'],
                                                    'money'=>0.5,
                                                    'create_time' => time(),
                                                    'grade' => '2',
                                                );
                                                Db::name('account_recommend')->insert($data);
                                            }
                                            if ($rs) {
                                                $flag = true;
                                                Db::commit();
                                            }
                                        }else{
                                            $flag = true;
                                            Db::commit();
                                        }
                                    }
                                }else{
                                    $flag = true;
                                    Db::commit();
                                }

                            } catch (\Exception $e) {
                                Db::rollback();
                            }
                        }
                    }
                    $this->success('步骤已提交');
                } else {
                    $this->error('任务步骤出错,请联系管理员!');
                }
            } else {
                $this->error('未找到订单或订单已完成');
            }
        } else {
            $this->error('任务步骤出错,请联系管理员!');
        }

    }

    public function getTaskType()
    {
        $process = new ProcessModel();
        $result = $process->order('id', 'asc')->where('status=1')->select()->toArray();
        $this->success('获取成功', $result);
    }

    public function upload() {
        $file = $this->request->file('file');
        // 移动到框架应用根目录/public/upload/ 目录下
        $info     = $file->validate([
            /*'size' => 15678,*/
            'ext' => 'jpg,png,gif,jpeg'
        ]);
        $fileMd5  = $info->md5();
        $fileSha1 = $info->sha1();

        $findFile = Db::name("asset")->where('file_md5', $fileMd5)->where('file_sha1', $fileSha1)->find();

        if (!empty($findFile)) {
            $this->success("上传成功!", ['url' => $findFile['file_path'], 'filename' => $findFile['filename'],'parseUrl'=>cmf_get_image_preview_url($findFile['file_path'])]);
        }
        $info = $info->move(ROOT_PATH . 'public' . DS . 'upload');

        if ($info) {
            $saveName     = $info->getSaveName();
            $originalName = $info->getInfo('name');//name,type,size
            $fileSize     = $info->getInfo('size');
            $suffix       = $info->getExtension();

            $fileKey = $fileMd5 . md5($fileSha1);

            $userId = $this->getUserId();
            Db::name('asset')->insert([
                'user_id'     => $userId,
                'file_key'    => $fileKey,
                'filename'    => $originalName,
                'file_size'   => $fileSize,
                'file_path'   => $saveName,
                'file_md5'    => $fileMd5,
                'file_sha1'   => $fileSha1,
                'create_time' => time(),
                'suffix'      => $suffix
            ]);

            $this->success("上传成功!", ['url' => $saveName, 'filename' => $originalName,'parseUrl'=>cmf_get_image_preview_url($saveName)]);
        } else {
            // 上传失败获取错误信息
            $this->error($file->getError());
        }
    }

    private function cal_commission($process_id=0, $price=0, $deal_num=0) {
        $commission = 0;
        $money = $price*$deal_num;
        $config = Db::name('process_config')->where(['process_id'=>$process_id])->find();
        if ($config) {
            if ($config['bg_type'] == 0) {
                $commission = Db::name('process_config a')
                    ->join('range_config b', 'a.id=b.process_config_id')
                    ->where(['a.process_id'=>$process_id, 'b.type'=>1])
                    ->value('b.commission');
            } else {
                $ranges = Db::name('process_config a')
                    ->join('range_config b', 'a.id=b.process_config_id')
                    ->field('b.*')
                    ->where(['a.process_id'=>$process_id, 'b.type'=>1])
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
}