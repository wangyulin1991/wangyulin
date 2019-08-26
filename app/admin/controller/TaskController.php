<?php
/**
 * Created by PhpStorm.
 * User: Admin
 * Date: 2019/1/28 0028
 * Time: 16:41
 */

namespace app\admin\controller;


use app\shopkeeper\model\PlatformModel;
use cmf\controller\AdminBaseController;
use think\Db;
use app\common\service\PayService;
use think\Validate;

class TaskController extends AdminBaseController
{
    //任务列表
    public function index() {
        $status = input('status');
        $process_id = input('process_id');
        $keyword = input('keyword');
        $user_login = input('user_login');
        $task_name = input('task_name');
        $task_no = input('task_no');
        $param = $this->request->param();
        $where = 'd.status not in (0, 9)';
        if ($status>-1) {
            $where .= " and d.status='{$status}'";
        }

        if ($process_id) {
            $where .= ' and d.process_id='.$process_id;
        }

        if ($keyword) {
            $where .= " and d.keyword like '%".$keyword."%'";
        }

        if ($task_name) {
            $where .= " and d.task_name like '%".$task_name."%'";
        }
        if ($user_login) {
            $where .= " and (a.user_login like '%$user_login%' or a.mobile like '%$user_login%' or a.qq like '%$user_login%')";
        }

        if ($task_no) {
            $where .= " and d.task_no like '%".$task_no."%'";
        }
        $this->assign('task_name', $task_name);
        $this->assign('keyword', $keyword);
        $this->assign('task_name', $task_name);
        $this->assign('task_no', $task_no);
        $this->assign('user_login', $user_login);
        $this->assign('process_id', $process_id);
        $this->assign('status', $status);

        $tasks = Db::name('user a')
            ->join('shopkeeper b', 'a.id=b.user_id')
            ->join('shop c', 'b.id=c.shopkeeper_id')
            ->join('task d', 'c.id=d.shop_id')
            ->join('process e', 'd.process_id=e.id')
            ->field('a.user_login, a.qq, c.name, c.link, c.type, e.name as process_type, d.id,d.task_no,d.task_name,d.product_price,d.commission,d.task_num,d.product_link,d.create_time,d.status,d.start_time,d.process_id, sum(d.remain_task_num) as remain_task_num')
            ->where($where)
            ->order('d.create_time', 'desc')
            ->group('d.task_no')
            ->paginate(15);
        $total = $tasks->total();
        $commission = Db::name('user a')
            ->join('shopkeeper b', 'a.id=b.user_id')
            ->join('shop c', 'b.id=c.shopkeeper_id')
            ->join('task d', 'c.id=d.shop_id')
            ->join('process e', 'd.process_id=e.id')
            ->where($where)
            ->sum('d.commission');

        $tasks->appends($param);
        $nowTotal = $tasks->count();
        $this->assign('process', Db::name('process')->select());
        $this->assign('page', $tasks->render());
        $this->assign('total',$total);
        $this->assign('nowTotal',$nowTotal);
        $this->assign('commission',$commission);
        $this->assign('tasks', $tasks);
        return $this->fetch();
    }

    //待审核列表
    public function audit_index() {
        $status = input('status');
        $process_id = input('process_id');
        $keyword = input('keyword');
        $task_name = input('task_name');
        $where = 'd.status = 0';
        if ($process_id) {
            $where .= ' and d.process_id='.$process_id;
        }

        if ($keyword) {
            $where .= " and d.keyword like '%".$keyword."%'";
        }

        if ($task_name) {
            $where .= " and d.task_name like '%".$task_name."%'";
        }
        $this->assign('task_name', $task_name);
        $this->assign('keyword', $keyword);
        $this->assign('process_id', $process_id);
        $this->assign('status', $status);

        $tasks = Db::name('user a')
            ->join('shopkeeper b', 'a.id=b.user_id')
            ->join('shop c', 'b.id=c.shopkeeper_id')
            ->join('task d', 'c.id=d.shop_id')
            ->join('process e', 'd.process_id=e.id')
            ->field('a.user_login, a.qq, c.name, d.product_link as link, c.type, e.name as process_type, d.*, sum(d.remain_task_num) as remain_task_num')
            ->where($where)
            ->order('d.create_time', 'desc')
            ->group('d.task_no')
            ->paginate(15);
        $this->assign('process', Db::name('process')->select());
        $this->assign('page', $tasks->render());
        $this->assign('tasks', $tasks);
        return $this->fetch();
    }


    /**
     * 编辑任务
     */
    public function edit() {
        $task_no = input('task_no');
        if ($this->request->isPost()) {
            $data = $this->request->param();
            if (empty($data['keyword'])) {
                $this->error('关键字不能为空');
            }  else {
                if (key_exists('keyword', $data)) {
                    $data['keyword'] = implode(',', $data['keyword']);
                }
                $rs = Db::name('task')->where(['task_no'=>$task_no])->update(['keyword'=>$data['keyword'], 'product_img'=>$data['product_img']]);
                $this->success('编辑任务成功');
            }
        }
        $info =  Db::name('user a')
            ->join('shopkeeper b', 'a.id=b.user_id')
            ->join('shop c', 'b.id=c.shopkeeper_id')
            ->join('task d', 'c.id=d.shop_id')
            ->join('process e', 'e.id=d.process_id')
            ->field('a.user_login, a.qq, c.name, c.link, c.type, d.*, e.name as process_name')
            ->where(['d.task_no'=>$task_no])
            ->group('d.task_no')
            ->find();
        $comments = Db::name('task_comment')->where(['task_no'=>$task_no])->select();
        $data = array();
        if ($comments) {
            foreach ($comments as $key => $val) {
                $data[$key]['content'] = $val['content'];
                if ($val['img']) {
                    $data[$key]['imgs'] = explode(';', $val['img']);
                }
            }
            $info['comment'] = $data;
        }
        $info['fee_step'] = explode(',', $info['fee_step']);
        if ($info['keyword']) {
            $info['keyword'] = explode(',', $info['keyword']);
        } else {
            $info['keyword'] = array();
        }
        $info['subs'] = Db::name('task')->field('id, start_time, sub_task_num')->where(['task_no'=>$task_no])->select();
        $this->assign('task', $info);
        $steps = Db::name('process_step')->where(['process_id'=>$info['process_id'],'is_base'=>0])->select();
        $processes = Db::name('process')->where(['platform_id'=>$info['platform_id']])->select();
        $shops = Db::name('user a')
            ->join('shopkeeper b', 'a.id=b.user_id')
            ->join('shop c', 'b.id=c.shopkeeper_id')
            ->field('c.id, c.name')
            ->select();
        $platformModel = new PlatformModel();
        $platforms = $platformModel->where('status=1')->select()->toArray();
        $this->assign('platforms',$platforms);
        $this->assign('processes', $processes);
        $this->assign('shops', $shops);
        $this->assign('steps', $steps);
        return $this->fetch();
    }

    /**
     *   生成订单号
     */
    protected function get_task_no()
    {
        $ors = $this->random(6,1);
        $count = Db::name('task')->where(['task_no'=>$ors])->count();
        if ($count) {
            return $this->get_task_no();
        }
        return $ors;
    }

    //审核任务
    public function audit() {
        $task_no = input('task_no');
        $status = input('status');
        $reason = input('reject_reason');
        $flag = false;

        if ($task_no && ($status || ($status == 2 && $reason))) {
            Db::startTrans();
            try {
                $tasks = Db::name('task')->where(['task_no'=>$task_no])->select()->toArray();
                if ($status == 1) {
                    foreach ($tasks as $key => $val) {
                        $is_show = $val['start_time']-time()>3600?1:0;
                        Db::name('task')->where(['id'=> $val['id']])->update(['status'=>$status, 'is_show'=>$is_show, 'auditer'=>cmf_get_current_admin_id(), 'audit_time'=>time()]);
                    }
                    Db::commit();
                    $flag = true;
                } else if ($status == 2) {
                    $rs = Db::name('task')->where(['task_no'=>$task_no])->update(['status'=>$status, 'reject_reason'=>$reason, 'auditer'=>cmf_get_current_admin_id(), 'audit_time'=>time()]);
                    if ($rs) {
                        $tasksInfo = Db::name('task')->where(['task_no'=>$task_no])->find();
                        Db::name('shopkeeper')->where(['id'=>$tasksInfo['shopkeeper_id']])->setInc('money',$tasksInfo['total_money']);
                        $data = array(
                            'order_no' => $tasksInfo['task_no'],
                            'money' => $tasksInfo['total_money'],
                            'shop_id'=> $tasksInfo['shop_id'],
                            'balance' => Db::name('shopkeeper')->where(['id'=>$tasksInfo['shopkeeper_id']])->value('money'),
                            'type' => 1,
                            'pay_way' => 0,
                            'purpose' => 3,
                            'user_id' => Db::name('shopkeeper')->where(['id'=>$tasksInfo['shopkeeper_id']])->value('user_id'),
                            'status' => 1,
                            'create_time' => time(),
                        );
                        $res = Db::name('account_statement')->insert($data);
                        if ($res) {
                            Db::commit();
                            $flag = true;
                        }
                    }
                }
                $redis = new \TPRedis(array('host' => \think\Config::get('database.REDIS_HOST'), 'auth' => \think\Config::get('database.REDIS_AUTH'), 'prefix' => \think\Config::get('database.REDIS_PREFIX')));
                foreach ($tasks as $key => $val){
                    $cache_name = 'task_num_'.$val['id'];
                    $redis->delete($cache_name);
                    for($i=0;$i<$val['sub_task_num'];$i++){
                        $redis->lPush($cache_name,1);
                    }
                    $rs = $redis->set('task_start_time_'.$val['id'],$val['start_time']);
                }
            } catch (\Exception $e) {
                Db::rollback();
            }
            if ($flag) {
                $this->success('审核成功', url('admin/Task/index'));
            } else {
                $this->error('审核失败');
                Db::rollback();
            }
        }

    }

    //结束任务
    public function end() {
        $task_no = input('task_no');
        if ($task_no) {
            $list = Db::name('task a')
                ->join('shopkeeper b', 'a.shopkeeper_id=b.id')
                ->field('a.shop_id, a.id, a.remain_task_num, a.commission,a.deal_num, b.user_id, a.task_no, a.product_price')
                ->where(['a.task_no'=>$task_no, 'a.status'=>1])
                ->select();
            foreach ($list as $key => $val) {
                Db::name('task')->where(['id'=>$val['id']])->update(['status'=>3]);
                if ($val['remain_task_num'] > 0) {
                    //$money = ($val['commission'] + $val['product_price']) * $val['remain_task_num'];
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
                        //'order_no' => "TK".date('YmdHis').$this->random(2,1),
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
            $this->success('任务结束');
        } else{
            $this->error('数据传输错误');
        }
    }



}