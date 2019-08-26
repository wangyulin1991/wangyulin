<?php
/**
 * Created by PhpStorm.
 * User: Administrator
 * Date: 2019/3/16
 * Time: 10:25
 */

namespace api\task\controller;


use api\task\model\OrderModel;
use cmf\controller\BaseController;
use dingtalk\DingTalk;
use think\Db;

class ScriptController extends BaseController
{
    //接单一小时未做到申请货款，结束
    public function order_end_hour() {

        $list = Db::name('order a')
            ->join('process_step b','a.now_step=b.id','left')
            ->join('task c','a.task_id=c.id')
            ->field('a.task_id, a.id, a.create_time,a.brush_guest_id,a.is_notice,a.businesses_id, b.step_sort, c.process_id,c.task_no,c.shop_id,c.order_time,c.product_price,c.commission')
            ->where(['a.status'=>['in',[0,3]]])
            ->select()->toArray();
        //申请货款
        $cash_sorts = Db::name('process a')->join('process_step b','a.id=b.process_id')
            ->join('process_step_action c','b.step_type=c.id')->field('a.id, b.step_sort')->where(['c.action_type'=>'GetCash'])->select()->toArray();
        //关键字搜索
        $keyword_sorts = Db::name('process a')->join('process_step b','a.id=b.process_id') ->join('process_step_action c','b.step_type=c.id')
            ->field('a.id, b.step_sort')->where(['c.action_type'=>'SearchKeyword'])->select()->toArray();
        //确认付款
        $pay_sorts = Db::name('process a')->join('process_step b','a.id=b.process_id')->join('process_step_action c','b.step_type=c.id')
            ->field('a.id, b.step_sort')->where(['c.action_type'=>'ConfirmPay'])->select()->toArray();
        //加购物车
        $cart_sorts = Db::name('process a')
            ->join('process_step b','a.id=b.process_id')
            ->join('process_step_action c','b.step_type=c.id')
            ->field('a.id, b.step_sort')
            ->where(['c.action_type'=>'ShoppingCart'])
            ->select()->toArray();
        $new = array_column($cash_sorts,'step_sort', 'id');
        $keyword = array_column($keyword_sorts,'step_sort', 'id');
        $pay = array_column($pay_sorts,'step_sort', 'id');
        $cart = array_column($cart_sorts,'step_sort', 'id');
        $redis = new \TPRedis(array('host' => \think\Config::get('database.REDIS_HOST'), 'auth' => \think\Config::get('database.REDIS_AUTH'), 'prefix' => \think\Config::get('database.REDIS_PREFIX')));
        $today = date('Y-m-d');
        $more_day = date('Y-m-d',strtotime("-1 day"));
        foreach ($list as $key => $val) {
            if($val['process_id'] == 4 || $val['process_id'] ==11){//多天任务 当天任务 1小时未做， 结束
                $create_day = date('Y-m-d',$val['create_time']);//开始时间 1小时未做 订单过期
                if (($create_day == $today || $create_day == $more_day) && time()-$val['create_time'] > 3600 && ($val['step_sort'] == null || $val['step_sort'] <= $keyword[$val['process_id']])) {
                    Db::name('order')->where(['id'=>$val['id']])->update(['status'=>5]);
                    Db::name('task')->where(['id'=>$val['task_id']])->setInc('remain_task_num', 1);
                    $cache_name = 'task_num_'.$val['task_id'];
                    $redis->lPush($cache_name,1);
                }
                $order_day = date('Y-m-d',$val['order_time']);//订单时间
                // 下单时间提醒，短信通知
                if ($order_day == $today && time()-$val['order_time'] >=0 && $val['step_sort'] <= $pay[$val['process_id']] && $val['is_notice'] == 0) {
                    $mobile = Db::name('user')->where('id',$val['brush_guest_id'])->value('mobile');
                    sendOrderTime($mobile, array());
                    Db::name('order')->where('id',$val['id'])->update(['is_notice'=>1]);
                }
                // 下单时间超1小时未做，订单过期
                if (($order_day == $today || $order_day == $more_day) && time()-$val['order_time'] >= 3600 && $val['step_sort'] <= $new[$val['process_id']]) {
                    Db::name('order')->where(['id'=>$val['id']])->update(['status'=>5]);
                    //退款给商户
                    $data = array(
                        'order_no' => $val['task_no'],
                        'money'=>$val['product_price']+$val['commission'],
                        'shop_id'=> $val['shop_id'],
                        'balance'=>Db::name('shopkeeper')->where('user_id',$val['businesses_id'])->value('money'),
                        'create_time'=>time(),
                        'pay_way' => 0,
                        'type' => 1,
                        'purpose' => 3,
                        'user_id' => $val['businesses_id'],
                        'status' => 1,
                    );
                    $res = Db::name('account_statement')->insertGetId($data);
                    if($res){
                        Db::name('shopkeeper')->where('user_id',$val['businesses_id'])->setInc('money',$val['product_price']+$val['commission']);
                    }
                }
            }else if($val['process_id'] == 13 ){//单纯搜索任务 当天 1小时未做， 结束
                $res_one = Db::name('order')->alias('a')
                    ->join('order_step b','a.id = b.order_id')
                    ->where(['a.id'=>$val['id'],'do_day'=>1])->field('b.id,b.order_id,b.step_name,b.create_time,b.input_text,b.do_day')->select()->toArray();
                $end_one = end($res_one);//最后一步
                $create_day = date('Y-m-d',$val['create_time']);
                if (($create_day == $today || $create_day == $more_day) && time()-$val['create_time'] > 3600 && ($val['step_sort'] == null || empty($end_one['input_text']))) {
                    Db::name('order')->where(['id'=>$val['id']])->update(['status'=>5]);
                    Db::name('task')->where(['id'=>$val['task_id']])->setInc('remain_task_num', 1);
                    $cache_name = 'task_num_'.$val['task_id'];
                    $redis->lPush($cache_name,1);
                }
            }else {//当天任务 1小时未做，及未到申请货款 结束
                $create_day = date('Y-m-d',$val['create_time']);
                if (($create_day == $today || $create_day == $more_day) && time()-$val['create_time'] > 3600 && ($val['step_sort'] == null || $val['step_sort'] <= $new[$val['process_id']])) {
                    Db::name('order')->where(['id'=>$val['id']])->update(['status'=>5]);
                    Db::name('task')->where(['id'=>$val['task_id']])->setInc('remain_task_num', 1);
                    $cache_name = 'task_num_'.$val['task_id'];
                    $redis->lPush($cache_name,1);
                }
            }
        }


    }

    //任务未完成，更新状态
    public function order_overdue() {
        $list = Db::name('order a')
            ->join('process_step b','b.id=a.now_step')
            ->join('task c','a.task_id=c.id')
            ->join('user d','a.brush_guest_id=d.id')
            ->field('a.id, a.task_id, a.comment_id, a.order_number, a.now_step, b.step_sort as now_step_sort, a.businesses_id, a.brush_guest_id, a.amount, c.task_no, d.user_nickname, d.user_login')
            ->where(['a.status'=>0, 'a.deadline'=>['elt', time()], 'a.apply_cash'=>0])
            ->select();
        $redis = new \TPRedis(array('host' => \think\Config::get('database.REDIS_HOST'), 'auth' => \think\Config::get('database.REDIS_AUTH'), 'prefix' => \think\Config::get('database.REDIS_PREFIX')));
        foreach ($list as $key => $val) {
            Db::name('order')->where(['id'=>$val['id']])->update(['status'=>5]);
            Db::name('task')->where(['id'=>$val['task_id']])->setInc('remain_task_num', 1);
            Db::name('task_comment')->where(['id'=>$val['comment_id']])->update(['is_used'=>0]);
            $cache_name = 'task_num_'.$val['task_id'];
            $redis->lPush($cache_name,1);
            /*$step_sort = Db::name('order_step a')
                ->join('process_step b', 'a.step_id=b.id')
                ->join('process_step_action c','b.step_type=c.id')
                ->where(['a.order_id'=>$val['id'], 'c.action_type'=>'GetCash'])
                ->value('b.step_sort');
            if ($val['now_step_sort'] >= $step_sort) {
                //退钱
                Db::name('shopkeeper')->where(['user_id'=>$val['businesses_id']])->setInc('money', $val['amount']);
                $data = array(
                    //'order_no' => "TK".date('YmdHis').$this->random(2,1),
                    'order_no' => $val['task_no'],
                    'money' => $val['amount'],
                    'type' => 1,
                    'pay_way' => 0,
                    'purpose' => 5,
                    'user_id' => $val['businesses_id'],
                    'status' => 1,
                    'create_time' => time(),
                );
                Db::name('account_statement')->insert($data);
                Db::name('brush_guest')->where(['user_id'=>$val['brush_guest_id']])->setDec('balance', $val['amount']);
                $data = array(
                    //'order_no' => "TK".date('YmdHis').$this->random(2,1),
                    'order_no' => $val['task_no'],
                    'money' => $val['amount'],
                    'type' => 2,
                    'pay_way' => 0,
                    'purpose' => 5,
                    'user_id' => $val['brush_guest_id'],
                    'status' => 1,
                    'create_time' => time(),
                );
                Db::name('account_statement')->insert($data);

                DingTalk::push_msg_dingding('订单'.$val['order_number'].'已过期，请联系买手'.$val['user_nickname'].'退货款，手机号：'.$val['user_login']);
            }*/
        }
    }

    //已弃用
    //任务未审核，自动审核通过
    public function order_auto_audit() {
        $list = Db::name('order a')
            ->join('order_step b','a.id=b.order_id')
            ->field('a.id, a.create_time, sum(b.wait_time) as wait_time')
            ->where('a.status=2')
            ->select();
        foreach ($list as $key => $val) {
            if ($val['create_time'] + $val['wait_time'] < strtotime(date('Y-m-d'))) {
                Db::name('order')->where(['id'=>$val['id']])->update(['status'=>4]);
            }
        }
    }

    //未接的剩余订单退钱给商家,在零点以后跑此脚本
    public function return_money() {
        $list = Db::name('task a')
            ->join('shopkeeper b', 'a.shopkeeper_id=b.id')
            ->field('a.shop_id, a.id, a.remain_task_num, a.commission, b.user_id, a.task_no, a.product_price')
            ->where(['a.start_time' => ['lt', strtotime(date('Y-m-d', time()))], 'a.status'=> 1])
            ->select();
        foreach ($list as $key => $val) {
            Db::name('task')->where(['task_no'=>$val['task_no']])->update(['status'=>3]);
            if ($val['remain_task_num'] > 0) {
                $money = ($val['commission'] + $val['product_price']) * $val['remain_task_num'];
                $comment_fee = Db::name('task_comment')->where(['is_used'=>0, 'task_no'=>$val['task_no']])->sum('commission')*2;
                if ($comment_fee > 0) {
                    $count = Db::name('task')->where(['task_no'=>$val['task_no'], 'status'=>1, 'id'=>['neq', $val['id']]])->count();
                    if ($count < 1) {
                        $money += $comment_fee;
                    }
                }

                Db::name('shopkeeper')->where(['user_id'=>$val['user_id']])->setInc('money', $money);
                $data = array(
                    'order_no' => $val['task_no'],
                    'money' => $money,
                    'shop_id'=> $val['shop_id'],
                    'balance' => Db::name('shopkeeper')->where(['user_id'=>$val['user_id']])->value('money'),
                    'type' => 1,
                    'pay_way' => 0,
                    'purpose' => 3,
                    'user_id' => $val['user_id'],
                    'status' => 1,
                    'create_time' => time(),
                );
                Db::name('account_statement')->insert($data);
            }
        }

        //多天任务
        $where =time().'-a.create_time > 3600 and c.process_id in(4,11) and a.status in(0,3)';
        $list = Db::name('order a')
            ->join('process_step b','a.now_step=b.id','left')
            ->join('task c','a.task_id=c.id')
            ->join('order_step os','a.id=os.order_id and os.step_id=b.id')
            ->field('a.task_id, a.id,a.shop_id,a.businesses_id,a.create_time,a.now_step,b.process_id, b.step_sort,os.step_name,os.input_text,c.shopkeeper_id,c.product_price,c.commission,c.task_num,c.task_no')
            ->where($where)
            ->select()->toArray();
        if($list){
            foreach ($list as $k=>$v){
                $first_day = date('Y-m-d',strtotime("-1 day"));
                $create_day = date('Y-m-d',$v['create_time']);//创建时间
                //第一天判断
                $res_one = Db::name('order')->alias('a')
                    ->join('order_step b','a.id = b.order_id')
                    ->where(['a.id'=>$v['id'],'do_day'=>1])->field('b.id,b.order_id,b.step_name,b.create_time,b.input_text,b.do_day')->select()->toArray();
                $end_one = end($res_one);//第一天最后一步

                if($first_day == $create_day && ($v['step_sort'] == null || empty($end_one['input_text']))){
                    Db::name('order')->where(['id'=>$v['id']])->update(['status'=>5]);
                    Db::name('shopkeeper')->where('id',$v['shopkeeper_id'])->setInc('money',$v['product_price']+$v['commission']);
                    $data = array(
                        'order_no' => $v['task_no'],
                        'money' => $v['product_price']+$v['commission'],
                        'shop_id'=> $v['shop_id'],
                        'balance' => Db::name('shopkeeper')->where(['user_id'=>$v['businesses_id']])->value('money'),
                        'type' => 1,
                        'pay_way' => 0,
                        'purpose' => 3,
                        'user_id' => $v['businesses_id'],
                        'status' => 1,
                        'create_time' => time(),
                    );
                    Db::name('account_statement')->insert($data);
                }
                //第二天任务判断
                $second_day = date('Y-m-d',strtotime("-2 day"));//第二天
                $res_two = Db::name('order')->alias('a')
                    ->join('order_step b','a.id = b.order_id')
                    ->where(['a.id'=>$v['id'],'do_day'=>2])->field('b.id,b.order_id,b.step_name,b.create_time,b.input_text,b.do_day')->select()->toArray();
                $end_two = end($res_two);//第二天最后一步
                if($create_day == $second_day && empty($end_two['input_text'])){
                    Db::name('order')->where(['id'=>$v['id']])->update(['status'=>5]);
                    Db::name('shopkeeper')->where('id',$v['shopkeeper_id'])->setInc('money',$v['product_price']+$v['commission']);
                    $data = array(
                        'order_no' => $v['task_no'],
                        'money' => $v['product_price']+$v['commission'],
                        'shop_id'=> $v['shop_id'],
                        'balance' => Db::name('shopkeeper')->where(['user_id'=>$v['businesses_id']])->value('money'),
                        'type' => 1,
                        'pay_way' => 0,
                        'purpose' => 3,
                        'user_id' => $v['businesses_id'],
                        'status' => 1,
                        'create_time' => time(),
                    );
                    Db::name('account_statement')->insert($data);
                }


            }
        }

    }

    //预约订单到任务开始日期自动更新状态并创建订单
    public function update_order_status() {
        $list = Db::name('appointment a')
            ->join('task b','a.task_id=b.id')
            ->join('brush_guest c','a.brush_guest_id=c.user_id')
            ->field('a.id, a.task_id, a.brush_guest_id, b.shopkeeper_id,b.brush_guest_money,b.task_no, b.product_price, b.product_img, b.task_name, a.keyword, b.shop_id, b.process_id,b.fee_step,b.order_time')
            ->where(['b.start_time' => ['elt', time()+600], 'a.order_id'=>0, 'a.status'=>1])
            ->limit(1000)
            ->select();
        if (empty($list) || count($list) <= 0) return;
        $ctime = time();
        $task_info = array();

        foreach ($list as $key => $val) {
            $redis = new \TPRedis(array('host' => \think\Config::get('database.REDIS_HOST'), 'auth' => \think\Config::get('database.REDIS_AUTH'), 'prefix' => \think\Config::get('database.REDIS_PREFIX')));
            //$count = Db::name('order')->where(['confirm_pay'=>0, 'status'=>['in',[0,1,2,3]], 'brush_guest_id'=>$val['brush_guest_id']])->count();
            $count =  Db::name('order')->alias('o')->join('task t','o.task_id = t.id')
                ->where(['o.brush_guest_id'=>$val['brush_guest_id'], 'o.status'=>['in', [0,1,2,3]], 'o.confirm_pay'=>0,'t.process_id'=>['not in',[4,11]]])->count();
            if ($count > 0) {
                Db::name('task')->where(['id'=>$val['task_id']])->setInc('remain_task_num', 1);
                $cache_name = 'task_num_'.$val['task_id'];
                $redis->lPush($cache_name,1);
                continue;
            }

            $time = time();
            $today = date('Y-m-d');
            $lastdaytime = strtotime($today);
            $count = Db::name('order')->where("brush_guest_id = {$val['brush_guest_id']} and create_time>={$lastdaytime} and create_time < {$time}")->count();
            if ($count >= 3) {
                Db::name('task')->where(['id'=>$val['task_id']])->setInc('remain_task_num', 1);
                $cache_name = 'task_num_'.$val['task_id'];
                $redis->lPush($cache_name,1);
                continue;
            }
            if (empty($task_info[$val['task_id']])) {
                $task_info[$val['task_id']] = $val;
                $shop_name = Db::name('shop')->where(['id'=>$val['shop_id']])->value('name');
                $process_name = Db::name('process')->where(['id'=>$val['process_id']])->value('name');
                $business_id = Db::name('shopkeeper')->where(['id'=>$val['shopkeeper_id']])->value('user_id');
                $task_info[$val['task_id']]['shop_name'] = $shop_name;
                $task_info[$val['task_id']]['process_name'] = $process_name;
                $task_info[$val['task_id']]['businesses_id'] = $business_id;
                $task_info[$val['task_id']]['steps'] = Db::name('process_step')->where(['process_id'=>$val['process_id'],'status'=>1])->order('step_sort asc')->select()->toArray();
            }

            $info = $task_info[$val['task_id']];
            $orderModel = new OrderModel();
            $order_number = $orderModel->createOrderNumber();
            $comment_id = Db::name('task_comment')->where(['task_no'=>$val['task_no'], 'is_used'=>0])->value('id');

            $orderData = [
                'order_number'=>$order_number,
                'shop_id'=>$info['shop_id'],
                'shop_name'=>$info['shop_name'],
                'process_name'=>$info['process_name'],
                'task_picture'=>$info['product_img'],
                'key_word'=>$info['keyword'],
                'comment_id'=>$comment_id?$comment_id:0,
                'paging_account'=>'',
                'amount' => $info['product_price'],
                'commission' => $info['brush_guest_money'],
                'task_id' => $val['task_id'],
                'task_name' => $info['task_name'],
                'brush_guest_id'=>$val['brush_guest_id'],
                'businesses_id'=> $info['businesses_id'],
                'deadline'=> $info['order_time']+3*86400,
                'is_appointment'=> 0,//不可预约
                'create_time'=> $ctime,
            ];
            $order_id = $orderModel->insertGetId($orderData);
            if ($order_id>0) {
                if ($orderData['comment_id'] > 0) {
                    $rs = Db::name('task_comment')->where(['id'=>$orderData['comment_id']])->update(['is_used'=>1]);
                }

                $rs = Db::name('appointment')->where(['id'=>$val['id']])->update(['order_id'=>$order_id, 'need_msg'=>1]);
                if ($rs) {
                    $need_step = explode(',',$info['fee_step']);
                    //获取任务步骤
                    $steps = $info['steps'];
                    if (count($steps)>0) {
                        $stepData = [];
                        $aging = 0;
                        $p_step_id = 0;
                        foreach ($steps as $key => $step) {
                            if ($step['is_base']==0&&!in_array($step['id'],$need_step)) {
                                continue;
                            }
                            $stepData[] = [
                                'order_id' =>$order_id,
                                'step_id' => $step['id'],
                                'p_step_id' => $p_step_id,
                                'step_name' => $step['step_name'],
                                'wait_time' => $step['wait_time'],
                                'user_id' => $val['brush_guest_id'],
                                'step_type' => $step['step_type'],
                                'is_change' => $step['is_change'],
                                'do_day' => $step['do_day'],
                                'input_text' => '',
                                'create_time' => $ctime
                            ];
                            $p_step_id = $step['id'];
                            $aging += $step['wait_time']*3600;
                        }
                        $rs = Db::name('order_step')->insertAll($stepData);
                        //$deadline = strtotime(date('Y-m-d',$aging + $ctime + 86400));
                        //$rs = $orderModel->save(['deadline'=>$deadline], ['id'=>$order_id]);
                    }
                }
            }
        }
        if (count($list) == 1000) $this->redirect('/api/task/script/update_order_status');
    }

    //预约通知短信
    public function sendMsgForApp() {
        $list = Db::name('appointment a')
            ->join('user b','a.brush_guest_id=b.id')
            ->field('a.id, b.mobile')
            ->where(['need_msg'=>1])
            ->limit(1000)
            ->select();
        if (empty($list) || count($list) <= 0) return;
        foreach ($list as $key => $val) {
            Db::name('appointment')->where(['id'=>$val['id']])->update(['need_msg'=>0]);
            sendOrderTip($val['mobile'], array());
        }
        if (count($list) == 1000) $this->redirect('/api/task/script/sendMsgForApp');
    }

    protected function log_message($msg = '')
    {
        $file = '/home/wangmeng/web/mission/data/log/log.txt';
        file_put_contents($file, date('Y-m-d H:i:s') . "\r\n" . var_export($msg, true) . "\r\n\r\n", FILE_APPEND | LOCK_EX);
    }

    public function addRedisVal() {
        $redis = new \TPRedis(array('host' => \think\Config::get('database.REDIS_HOST'), 'auth' => \think\Config::get('database.REDIS_AUTH'), 'prefix' => \think\Config::get('database.REDIS_PREFIX')));
        $key = input('redis_key');
        if ($key) {
            $redis->lPush($key, 1);
        }
    }
}