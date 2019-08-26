<?php
/**
 * Created by PhpStorm.
 * User: Admin
 * Date: 2019/1/28 0028
 * Time: 16:41
 */

namespace app\shopkeeper\controller;


use app\shopkeeper\model\PlatformModel;
use cmf\controller\ShopkeeperBaseController;
use cmf\lib\Upload;
use think\Db;
use app\common\service\PayService;
use think\Validate;

class TaskController extends ShopkeeperBaseController
{

    //任务查询
    public function index() {
        $process_id = input('process_id');
        $status = input('status');
        $keyword = input('keyword');
        $task_name = input('task_name');
        $user_id = cmf_get_current_user_id();

        $where = 'a.id = ' . $user_id;

        if ($status>-1&&$status<3) {
            $where .= " and d.status='{$status}'";
        } else {
            $where .= " and d.status='1'";
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

        $this->assign('status', $status);
        $this->assign('task_name', $task_name);
        $this->assign('keyword', $keyword);
        $this->assign('process_id', $process_id);

        $tasks = Db::name('user a')
            ->join('shopkeeper b', 'a.id=b.user_id')
            ->join('shop c', 'b.id=c.shopkeeper_id')
            ->join('task d', 'c.id=d.shop_id')
            ->join('process e', 'd.process_id=e.id')
            ->field('a.user_login, a.qq, c.name, c.link, c.type, d.*,e.name as task_type')
            ->where($where)
            ->order('d.create_time', 'desc')
            ->group('d.task_no')
            ->paginate(50);
        $list = array();
        foreach ($tasks as $k => $v) {
            $list[$k] = $v;
            $list[$k]['com_count'] = Db::name('task t')->join('order o','o.task_id=t.id and o.status in (4,6)')->where(['t.task_no'=>$v['task_no']])->count();
        }

        $this->assign('process', Db::name('process')->select());
        $this->assign('tasks', $list);
        $this->assign('page', $tasks->render());
        return $this->fetch();
    }

    //待审核的任务
    public function audit() {
        $process_id = input('process_id');
        $keyword = input('keyword');
        $task_name = input('task_name');
        $user_id = cmf_get_current_user_id();
        $where = 'd.status = 0  and a.id = ' . $user_id;

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

        $tasks = Db::name('user a')
            ->join('shopkeeper b', 'a.id=b.user_id')
            ->join('shop c', 'b.id=c.shopkeeper_id')
            ->join('task d', 'c.id=d.shop_id')
            ->join('process e', 'd.process_id=e.id')
            ->field('a.user_login, a.qq, c.name, c.link, c.type, d.*,e.name as task_type')
            ->where($where)
            ->order('d.create_time', 'desc')
            ->group('d.task_no')
            ->paginate(50);
        $this->assign('process', Db::name('process')->select());
        $this->assign('tasks', $tasks);
        $this->assign('page', $tasks->render());
        return $this->fetch();
    }

    //任务草稿
    public function draft() {
        $process_id = input('process_id');
        $keyword = input('keyword');
        $task_name = input('task_name');
        $user_id = cmf_get_current_user_id();
        $where = 'd.status = 9  and a.id = ' . $user_id;

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

        $tasks = Db::name('user a')
            ->join('shopkeeper b', 'a.id=b.user_id')
            ->join('shop c', 'b.id=c.shopkeeper_id')
            ->join('task d', 'c.id=d.shop_id')
            ->join('process e', 'd.process_id=e.id')
            ->field('a.user_login, a.qq, c.name, c.link, c.type, d.*,e.name as task_type')
            ->where($where)
            ->order('d.create_time', 'desc')
            ->group('d.task_no')
            ->paginate(50);
        $this->assign('process', Db::name('process')->select());
        $this->assign('tasks', $tasks);
        $this->assign('page', $tasks->render());
        return $this->fetch();
    }

    //拒绝的任务
    public function reject() {
        $process_id = input('process_id');
        $keyword = input('keyword');
        $task_name = input('task_name');
        $user_id = cmf_get_current_user_id();
        $where = 'd.status = 2  and a.id = ' . $user_id;

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

        $tasks = Db::name('user a')
            ->join('shopkeeper b', 'a.id=b.user_id')
            ->join('shop c', 'b.id=c.shopkeeper_id')
            ->join('task d', 'c.id=d.shop_id')
            ->join('process e', 'd.process_id=e.id')
            ->field('a.user_login, a.qq, c.name, c.link, c.type, d.*,e.name as task_type')
            ->where($where)
            ->order('d.create_time', 'desc')
            ->group('d.task_no')
            ->paginate(50);
        $this->assign('process', Db::name('process')->select());
        $this->assign('tasks', $tasks);
        $this->assign('page', $tasks->render());
        return $this->fetch();
    }

    //任务查询
    public function his_index() {
        $status = input('status');
        $process_id = input('process_id');
        $keyword = input('keyword');
        $task_name = input('task_name');
        $where = 'd.status =3 and b.id='.$this->shopkeeper_id;

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
            ->field('a.user_login, a.qq, c.name, c.link, c.type, d.*,e.name as task_type')
            ->where($where)
            ->group('d.task_no')
            ->order('d.create_time', 'desc')
            ->paginate(50);
        $task_num = 0;
        $commission = 0;
        $money = 0;
        $list = array();
        foreach ($tasks as $k => $v) {
            $list[$k] = $v;
            $list[$k]['com_count'] = Db::name('task t')->join('order o','o.task_id=t.id and o.status in (4,6)')->where(['t.task_no'=>$v['task_no']])->count();
            $task_num  += $v['task_num'];
            $commission  += $v['commission'];
            $money  += $v['total_money'];
        }
        $this->assign('process', Db::name('process')->select());
        $this->assign('tasks', $list);
        $this->assign('page', $tasks->render());
        $this->assign('total', $tasks->total());
        $this->assign('task_num', $task_num);
        $this->assign('commission', $commission);
        $this->assign('money', $money);
        return $this->fetch();
    }

    //发布任务信息校验
    public function vldTask() {
        if ($this->request->isAjax()) {
            $params = $this->request->param();
            $res = $this->validate($params, 'Task.first');
            $flag = false;
            if ($res !== true) {
                $this->error($res);
            } else {
                $task_no = input('task_no');

                $money = Db::name('shopkeeper')->where(['id' => $this->shopkeeper_id])->value('money');

                $params['commission'] = $this->cal_commission($params['process_id'], $params['product_price']);
                if ($params['commission'] <= 0) {
                    $this->error('发布任务失败');
                }

                /*$need_money = $params['task_num'] * ($params['commission'] + $params['product_price'] * $params['deal_num']);
                if ($task_no) {
                    $task = Db::name('task')->where(['task_no'=>$task_no])->find();
                    if ($task['status'] != 9) {
                        if ($task['total_money'] < $need_money && $money < $need_money-$task['total_money']) {
                            $this->error('需要补款, 余额不足, 请充值','',2);
                        }
                    } else {
                        if ($money < $need_money) {
                            $this->error('余额不足,请充值','',2);
                        }
                    }
                } else {
                    if ($money < $need_money) {
                        $this->error('余额不足,请充值','',2);
                    }
                }*/
            }
        }
        $this->success('校验成功');
    }

    //发布任务 第一页
    public function add_first() {
        if ($this->request->post()) {
            $params = $this->request->param();
            $res = $this->validate($params, 'Task.first');
            $res = true;
            $flag = false;
            if ($res !== true) {
                $this->error($res);
            } else {
                $money = Db::name('shopkeeper')->where(['id'=>$this->shopkeeper_id])->value('money');
                if (!key_exists('step_id', $params)) $params['step_id'] = array();
                $step_fee = $this->cal_step_fee($params['step_id']);
                $params['step_fee'] = $step_fee;
                $params['commission'] = $this->cal_commission($params['process_id'], $params['product_price']) + $step_fee;
                Db::startTrans();
                try {
                    if (!input('product_img') && $this->request->file('product_img')) {
                        $uploader = new Upload();
                        $uploader->setFormName('product_img');
                        $result = $uploader->upload();
                        if ($result === false) {
                            $this->error($uploader->getError());
                        } else {
                            $params['product_img'] = $result['filepath'];
                            $result = true;
                        }
                    } else {
                        $result = true;
                    }

                    if ($result) {
                        if (key_exists('keyword', $params)) {
                            $params['keyword'] = implode(',', $params['keyword']);
                        }
                        //$params['total_money'] = $need_money;
                        $params['shopkeeper_id'] = $this->shopkeeper_id;
                        $params['task_no'] = $this->get_task_no();
                        $params['create_time'] = time();
                        $params['fee_step'] = implode(',',$params['step_id']);
                        unset($params['step_id']);
                        $params['status'] = 9;//草稿

                        if(isset($params['brush_action_input']) && !empty($params['brush_action_input'])){
                            $params['brush_action_input'] = json_encode($params['brush_action_input'],JSON_UNESCAPED_UNICODE);
                        }

                        $id = Db::name('task')->insertGetId($params);
                        if ($id) {
                            $process_id = Db::name('task')->where('id',$id)->value('process_id');
                            $this->assign('id', $id);
                            $this->assign('process_id', $process_id);
                            $this->assign('task_num', $params['task_num']);
                            $this->assign('task_no', $params['task_no']);
                            Db::commit();
                            $flag = true;
                        }
                    }
                } catch (\Exception $e) {
                    Db::rollback();
                }

                if (!$flag) {
                    Db::rollback();
                    $this->error('发布任务失败');
                }
            }
            //}
            return $this->fetch('add_second');
        }

        //$processes = Db::name('process')->where('status=1')->select();
        $shops = Db::name('user a')
            ->join('shopkeeper b', 'a.id=b.user_id')
            ->join('shop c', 'b.id=c.shopkeeper_id')
            ->field('c.id, c.name')
            ->where(['a.id'=>$this->user_id])
            ->select();
        $platformModel = new PlatformModel();
        $platforms = $platformModel->where('status=1')->select()->toArray();

        $this->assign('platforms',$platforms);
        //$this->assign('processes', $processes);
        $this->assign('shops', $shops);
        return $this->fetch();
    }

    //发布任务 第二页
    public function add_second() {
        if ($this->request->isPost()) {
            $flag = false;
            $id = input('id', 0);
            $process_id = input('process_id', 0);
            $params = $this->request->param();
            $task = Db::name('task')->find(['id'=>$id]);
            if ($task) {

                //分段发布任务
                $sub_id = $params['sub_id'];
                $subs = array();
                $total_sub_task_num = 0;
                if (!empty($sub_id)&&is_array($sub_id)) {
                    foreach ($sub_id as $key => $val) {
                        $start_time = $params['start_time'.$val];
                        if($process_id == 11 || $process_id == 4){
                            $order_time = $params['order_time'.$val];
                            $subs[$key]['order_time']=strtotime($order_time);
                        }
                        $sub_task_num = input('sub_task_num'.$val);
                        if (empty($start_time) || !$sub_task_num) {
                            continue;
                        }
                        if (strtotime($start_time) < time()) {
                            $this->error('开始时间不能小于当前时间');
                        }
                        //第二天23:50:00 时间戳
                        $time = strtotime(date('Y-m-d',strtotime('+1 day')))+ 85800;
                        if($process_id == 11 && strtotime($order_time) > $time){
                            $this->error('订单时间不能大于第二天时间');
                        }

                        //第三天23:50:00 时间戳
                        $time4 = strtotime(date('Y-m-d',strtotime('+2 day')))+ 85800;
                        if($process_id == 4 && strtotime($order_time) > $time4){
                            $this->error('订单时间不能大于第三天时间');
                        }
                        $subs[$key]['start_time']=strtotime($start_time);
                        $subs[$key]['sub_task_num']=$sub_task_num;
                        $subs[$key]['remain_task_num'] = $sub_task_num;
                        $total_sub_task_num += $sub_task_num;
                    }
                }

                //评论图片
                $comment_suf = $params['comment_suf'];
                $comments = array();
                $comment_money = 0;
                if (!empty($comment_suf)&&is_array($comment_suf)) {
                    foreach ($comment_suf as $key => $val) {
                        $fee = 0;
                        $comment_img = null;
                        if (key_exists('comment_img_'.$val, $params)) {
                            $comment_img = $params['comment_img_'.$val];
                            if ($comment_img) {
                                $comment_img = array_filter($comment_img);
                            }
                        }
                        $comment = input('comment_'.$val);
                        if (empty($comment) && (empty($comment_img) || count($comment_img) < 1)) {
                            continue;
                        }
                        if ($comment) {
                            $comment_money++;
                            $fee += 0.5;
                            $comments[$key]['content'] = $comment;
                        } else {
                            $comments[$key]['content'] = '';
                        }
                        if ($comment_img) {
                            $comment_money += count($comment_img);
                            $fee += count($comment_img)*0.5;
                            $comments[$key]['img'] = implode(';', $comment_img);
                        } else {
                            $comments[$key]['img'] = '';
                        }
                        $comments[$key]['commission']= $fee;
                        $comments[$key]['task_no']=$task['task_no'];
                        $comments[$key]['create_time']=time();
                    }
                }
                if (!count($subs)) $this->error('请完善发布设置');
                if ($total_sub_task_num != $task['task_num']) $this->error('分批发放的任务数量和总数量不匹配');

                $money = Db::name('shopkeeper')->where(['id'=>$this->shopkeeper_id])->value('money');
                $need_money = $task['task_num']*($task['commission'] + $task['product_price']) + $comment_money;
                if ($money < $need_money) {
                    $this->error('余额不足,请充值','',2);
                } else {
                    Db::startTrans();
                    try {
                        //$task['aging'] = $params['aging'];
                        $task['status'] = 0;
                        $task['comment_fee'] = $comment_money;
                        $task['total_money'] = $need_money;
                        $task['before_money'] = $need_money;
                        $task['brush_guest_money'] = $this->cal_brush_commission($task['process_id'], $task['product_price'], $task['fee_step']);
                        unset($task['id']);
                        Db::name('task')->where(['id'=>$id])->delete();
                        $data = array();
                        foreach ($subs as $key => $val) {
                            $task['start_time']=$val['start_time'];
                            if(!empty($val['order_time'])){
                                $task['order_time']=$val['order_time'];
                            }
                            $task['sub_task_num']=$val['sub_task_num'];
                            $task['remain_task_num']=$val['remain_task_num'];
                            $task['sub_total_money']=$val['sub_task_num']*($task['commission']+$task['product_price']);
                            $data[$key] = $task;
                        }
                        $rs = Db::name('task')->insertAll($data);
                        if ($rs) {
                            if (count($comments) >= 1) {
                                $rs = Db::name('task_comment')->insertAll($comments);
                            }
                            if ($rs) {
                                $rs = Db::name('shopkeeper')->where(['id'=>$this->shopkeeper_id])->setDec('money', $need_money);
                                if ($rs) {
                                        $data = array('order_no'=>$task['task_no'],'status'=>1,'type'=>2,'purpose'=>2,'money'=>$need_money,'balance'=>$money-$need_money,'shop_id'=>$task['shop_id'],'user_id'=>$this->user_id,'create_time'=>time());
                                        $rs = Db::name('account_statement')->insertGetId($data);
                                        if ($rs) {

                                            /************任务不审核直接发布*************/
                                            $task_audit = Db::name('shopkeeper')->where(['id'=>$this->shopkeeper_id])->value('task_audit');
                                            if($task_audit == 1){
                                                $tasks = Db::name('task')->where(['task_no'=>$task['task_no']])->select()->toArray();
                                                foreach ($tasks as $key => $val) {
                                                    $is_show = $val['start_time']-time()>3600?1:0;
                                                    Db::name('task')->where(['id'=> $val['id']])->update(['status'=>1, 'is_show'=>$is_show, 'audit_time'=>time()]);
                                                }

                                                $redis = new \TPRedis(array('host' => \think\Config::get('database.REDIS_HOST'), 'auth' => \think\Config::get('database.REDIS_AUTH'), 'prefix' => \think\Config::get('database.REDIS_PREFIX')));
                                                foreach ($tasks as $key => $val){
                                                    $cache_name = 'task_num_'.$val['id'];
                                                    $redis->delete($cache_name);
                                                    for($i=0;$i<$val['sub_task_num'];$i++){
                                                        $redis->lPush($cache_name,1);
                                                    }
                                                    $re = $redis->set('task_start_time_'.$val['id'],$val['start_time']);
                                                }
                                            }
                                            /*************************************/

                                            Db::commit();
                                            $flag = true;
                                        }
                                    }
                                }
                            }
                    } catch (\Exception $e) {
                        Db::rollback();
                    }
                }
            }
            if ($flag) {
                $this->success('发布任务成功', url('task/audit'),'',3);
            } else {
                Db::rollback();
                $this->error('发布任务失败'.$e);
            }
        }

        return $this->fetch();
    }

    //编辑任务 第一页
    public function edit_first() {
        $task_no = input('task_no');
        if ($this->request->post()) {
            $params = $this->request->param();
            Db::name('task')->where(['task_no'=>$task_no])->update(['brush_action_input' => ' ']);
            $res = $this->validate($params, 'Task.first');
            $flag = false;
            if ($res !== true) {
                $this->error($res);
            } else {
                //重新计算佣金,多退少补
                //$money = Db::name('shopkeeper')->where(['id'=>$this->shopkeeper_id])->value('money');

                //$task = Db::name('task')->where(['task_no'=>$task_no])->find();

                //收费步骤
                if (!key_exists('step_id', $params)) $params['step_id'] = array();
                $params['step_fee'] = $this->cal_step_fee($params['step_id']);

                //计算佣金 缺少评论金额
                $params['commission'] = $this->cal_commission($params['process_id'], $params['product_price']) + $params['step_fee'];
                if ($params['commission'] <= 0) {
                    $this->error('发布任务失败!!!');
                }

                //$need_money = $task['total_money'] - $task['comment_fee']*$task['task_num'] - $params['task_num']*($params['commission']+$params['product_price']*$params['deal_num']);
                //$gap = 0;
                /*if ($need_money < 0 && $money < abs($need_money)) {
                    if ($task['status'] == 9) {
                        $this->error('余额不足,请充值');
                    } else {
                        $this->error('需要补款,余额不足,请充值');
                    }
                } else {*/
                Db::startTrans();
                try {
                    if (!input('product_img') && $this->request->file('product_img')) {
                        $uploader = new Upload();
                        $uploader->setFormName('product_img');
                        $result = $uploader->upload();
                        if ($result === false) {
                            $this->error($uploader->getError());
                        } else {
                            $params['product_img'] = $result['filepath'];
                            $result = true;
                        }
                    } else {
                        $result = true;
                    }

                    if ($result) {
                        if (key_exists('keyword', $params)) {
                            $params['keyword'] = implode(',', $params['keyword']);
                        }
                        $params['remain_task_num'] = $params['task_num'];
                        $params['fee_step'] = implode(',',$params['step_id']);
                        unset($params['step_id']);
                        $params['create_time'] = time();
                        $status = Db::name('task')->where(['task_no'=>$task_no])->value('status');
                        if ($status != 2) {
                            $params['status'] = 9;//草稿
                        }
                        if(isset($params['brush_action_input']) && !empty($params['brush_action_input'])){
                            $params['brush_action_input'] = json_encode($params['brush_action_input'],JSON_UNESCAPED_UNICODE);
                        }
                        $rs = Db::name('task')->where(['task_no'=>$task_no])->update($params);
                        if ($rs) {
                            $this->assign('task_no', $task_no);
                            Db::commit();
                            $flag = true;
                        }
                    }
                } catch (\Exception $e) {
                    Db::rollback();
                }

                if (!$flag) {
                    Db::rollback();
                    $this->error('发布任务失败');
                }
            }

            $tasks =  Db::name('task')->where(['task_no'=>$task_no])->select()->toArray();
            foreach ($tasks as $key => $val) {
                if ($val['status'] == 9) {
                    $tasks[$key]['start_time'] = strtotime(date('Y-m-d H:00:00', time()+3600));
                    $tasks[$key]['sub_task_num'] = $val['task_num'];
                }
            }
            $info = $tasks[0];
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
            $info['keyword'] = explode(',', $info['keyword']);
            $this->assign('task', $info);
            $this->assign('process_id', $info['process_id']);
            $this->assign('tasks', $tasks);
            return $this->fetch('edit_second');
        }

        $info =  Db::name('task')->where(['task_no'=>$task_no])->find();
        $info['fee_step'] = explode(',', $info['fee_step']);
        if ($info['keyword']) {
            $info['keyword'] = explode(',', $info['keyword']);
        } else {
            $info['keyword'] = array();
        }

        $this->assign('task', $info);
        $processes = Db::name('process')->where(['platform_id'=>$info['platform_id'],'status'=>1])->select();
        $steps = Db::name('process_step')->where(['process_id'=>$info['process_id'], 'is_base'=>0])->select();

        $shops = Db::name('user a')
            ->join('shopkeeper b', 'a.id=b.user_id')
            ->join('shop c', 'b.id=c.shopkeeper_id')
            ->field('c.id, c.name')
            ->where(['a.id'=>$this->user_id, 'c.type'=>$info['platform_id']])
            ->select();
        $platformModel = new PlatformModel();
        $platforms = $platformModel->where('status=1')->select()->toArray();
        $this->assign('platforms',$platforms);
        $this->assign('processes', $processes);
        $this->assign('steps', $steps);
        $this->assign('shops', $shops);
        return $this->fetch();
    }

    //编辑任务 第二页
    public function edit_second() {
        if ($this->request->post()) {
            $flag = false;
            $task_no = input('task_no');
            $process_id = input('process_id', 0);
            $params = $this->request->param();
            $task = Db::name('task')->where(['task_no'=>$task_no])->find();
            if ($task) {
                $sub_id = $params['sub_id'];
                $subs = array();
                $total_sub_task_num = 0;
                if (!empty($sub_id)&&is_array($sub_id)) {
                    foreach ($sub_id as $key => $val) {
                        $start_time = $params['start_time'.$val];
                        $sub_task_num = input('sub_task_num'.$val);
                        if (empty($start_time) || !$sub_task_num) {
                            continue;
                        }
                        if (strtotime($start_time) < time()) {
                            $this->error('开始时间不能小于当前时间');
                        }

                        if($process_id == 11 || $process_id == 4){
                            $order_time = $params['order_time'.$val];
                            $subs[$key]['order_time']=strtotime($order_time);
                        }
                        //第二天23:50:00 时间戳
                        $time = strtotime(date('Y-m-d',strtotime('+1 day')))+ 85800;
                        if($process_id == 11 && strtotime($order_time) > $time){
                            $this->error('订单时间不能大于第二天时间');
                        }

                        //第三天23:50:00 时间戳
                        $time4 = strtotime(date('Y-m-d',strtotime('+2 day')))+ 85800;
                        if($process_id == 4 && strtotime($order_time) > $time4){
                            $this->error('订单时间不能大于第三天时间');
                        }

                        $subs[$key]['start_time']=strtotime($start_time);
                        $subs[$key]['sub_task_num']=$sub_task_num;
                        $subs[$key]['remain_task_num'] = $sub_task_num;
                        $total_sub_task_num += $sub_task_num;
                    }
                }
                $comment_fee = 0;
                $comments = array();
                if (key_exists('comment_suf', $params)) {
                    $comment_suf = $params['comment_suf'];
                    foreach ($comment_suf as $key => $val) {
                        $fee = 0;
                        $comment = input('comment_'.$val);
                        $comment_img = null;
                        if (key_exists('comment_img_'.$val, $params)) {
                            $comment_img = $params['comment_img_'.$val];
                            if ($comment_img) {
                                $comment_img = array_filter($comment_img);
                            }
                        }
                        if (empty($comment) && (empty($comment_img) || count($comment_img) < 1)) {
                            continue;
                        }
                        if ($comment) {
                            $fee += 0.5;
                            $comment_fee++;
                            $comments[$key]['content'] = $comment;
                        } else {
                            $comments[$key]['content'] = '';
                        }
                        if ($comment_img) {
                            $comment_fee += count($comment_img);
                            $fee += count($comment_img)*0.5;
                            $comments[$key]['img'] = implode(';', $comment_img);
                        } else {
                            $comments[$key]['img'] = '';
                        }
                        $comments[$key]['commission']= $fee;
                        $comments[$key]['status'] = 0;
                        $comments[$key]['task_no'] = $task_no;
                        $comments[$key]['create_time'] = time();
                    }
                }

                if (!count($subs)) $this->error('请完善发布设置');
                if ($total_sub_task_num != $task['task_num']) $this->error('分批发放的任务数量和总数量不匹配');

                $money = Db::name('shopkeeper')->where(['id'=>$this->shopkeeper_id])->value('money');//余额
                $need_money = $task['task_num']*($task['commission'] + $task['product_price']) + $comment_fee;//当前总金额
                if ($task['status'] != 9 && $task['before_money'] < $need_money && $need_money - $task['before_money'] > $money) {
                    $this->error('需补款'.($need_money - $task['before_money']).'元,余额不足,请充值','',2);
                } else if ($task['status'] == 9 && $money < $need_money) {
                    $this->error('余额不足,请充值','',2);
                }

                Db::startTrans();
                try {
                    $rs = 1;
                    if ($task['status'] != 9) {//拒绝状态
                        $data = array(
                            'order_no' => $task['task_no'],
                            'pay_way' => 0,
                            'user_id' => $this->user_id,
                            'status' => 1,
                            'create_time' => time(),
                        );
                        if ($task['before_money'] > $need_money) {
                            $rs = Db::name('shopkeeper')->where(['id'=>$this->shopkeeper_id])->setInc('money', $task['before_money']-$need_money);
                            if ($rs) {
                                $data['type'] = 1;
                                $data['shop_id'] = $task['shop_id'];
                                $data['purpose'] = 3;
                                $data['money'] = $task['before_money']-$need_money;
                                $data['balance'] = $money+$data['money'];
                                $rs = Db::name('account_statement')->insert($data);
                            }
                        } else if ($task['before_money'] < $need_money) {
                            $rs = Db::name('shopkeeper')->where(['id'=>$this->shopkeeper_id])->setDec('money', $need_money-$task['before_money']);
                            if ($rs) {
                                $data['type'] = 2;
                                $data['shop_id'] = $task['shop_id'];
                                $data['purpose'] = 4;
                                $data['money'] = $need_money-$task['before_money'];
                                $data['balance'] = $money-$data['money'];
                                $rs = Db::name('account_statement')->insert($data);
                            }
                        }
                    } else {//草稿状态，需扣钱
                        $rs = Db::name('shopkeeper')->where(['id'=>$this->shopkeeper_id])->setDec('money', $need_money);
                        if ($rs) {
                            $data = array('order_no'=>$task['task_no'],'status'=>1,'type'=>2,'purpose'=>2,'money'=>$need_money,'balance'=>$money-$need_money,'shop_id'=>$task['shop_id'],'user_id'=>$this->user_id,'create_time'=>time());
                            $rs = Db::name('account_statement')->insertGetId($data);
                        }
                    }
                    if ($rs) {
                        $task['before_money'] = $need_money;
                        $task['total_money'] = $need_money;
                        $task['comment_fee'] = $comment_fee;
                        $task['brush_guest_money'] = $this->cal_brush_commission($task['process_id'], $task['product_price'], $task['fee_step']);
                        $task['status'] = 0;

                        unset($task['id']);
                        Db::name('task')->where(['task_no'=>$task_no])->delete();
                        if (count($comments) >= 1) {
                            Db::name('task_comment')->where(['task_no'=>$task_no])->delete();
                            $rs = Db::name('task_comment')->insertAll($comments);
                        }
                        if ($rs) {
                            $data = array();
                            foreach ($subs as $key => $val) {
                                $task['start_time']=$val['start_time'];
                                if(!empty($val['order_time'])){
                                    $task['order_time']=$val['order_time'];
                                }
                                $task['sub_task_num']=$val['sub_task_num'];
                                $task['remain_task_num']=$val['remain_task_num'];
                                $task['sub_total_money']=$val['sub_task_num']*($task['commission']+$task['product_price']);
                                $data[$key] = $task;
                            }
                            $rs = Db::name('task')->insertAll($data);
                            /************任务不审核直接发布*************/
                            $task_audit = Db::name('shopkeeper')->where(['id'=>$this->shopkeeper_id])->value('task_audit');
                            if($task_audit == 1){
                                $tasks = Db::name('task')->where(['task_no'=>$task['task_no']])->select()->toArray();
                                foreach ($tasks as $key => $val) {
                                    $is_show = $val['start_time']-time()>3600?1:0;
                                    Db::name('task')->where(['id'=> $val['id']])->update(['status'=>1, 'is_show'=>$is_show, 'audit_time'=>time()]);
                                }
                                $redis = new \TPRedis(array('host' => \think\Config::get('database.REDIS_HOST'), 'auth' => \think\Config::get('database.REDIS_AUTH'), 'prefix' => \think\Config::get('database.REDIS_PREFIX')));
                                foreach ($tasks as $key => $val){
                                    $cache_name = 'task_num_'.$val['id'];
                                    $redis->delete($cache_name);
                                    for($i=0;$i<$val['sub_task_num'];$i++){
                                        $redis->lPush($cache_name,1);
                                    }
                                    $re = $redis->set('task_start_time_'.$val['id'],$val['start_time']);
                                }
                            }
                            /*************************************/
                            if ($rs) {
                                Db::commit();
                                $flag = true;
                            }
                        }
                    }
                } catch (\Exception $e) {
                    Db::rollback();
                }

                if ($flag) {
                    $this->success('发布任务成功', url('task/audit'));
                } else {
                    Db::rollback();
                    $this->error('发布任务失败2');
                }
            }
        }

        return $this->fetch();
    }

    //查看任务
    public function view() {
        $task_no = input('task_no');
        $tasks =  Db::name('task')->where(['task_no'=>$task_no])->select();
        $this->log_message($tasks);
        $info = $tasks[0];
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
        $this->assign('task', $info);
        $processes = Db::name('process')->where(['platform_id'=>$info['platform_id']])->select();
        $shops = Db::name('user a')
            ->join('shopkeeper b', 'a.id=b.user_id')
            ->join('shop c', 'b.id=c.shopkeeper_id')
            ->field('c.id, c.name')
            ->where(['a.id'=>$this->user_id])
            ->select();
        $platformModel = new PlatformModel();
        $platforms = $platformModel->where('status=1')->select()->toArray();
        $steps = Db::name('process_step')->where(['process_id'=>$info['process_id'], 'is_base'=>0])->select();
        $this->assign('platforms',$platforms);
        $this->assign('processes', $processes);
        $this->assign('shops', $shops);
        $this->assign('steps', $steps);
        $this->assign('tasks', $tasks);
        return $this->fetch();
    }

    //编辑任务 弃用
    public function edit() {
        $id = input('id');

        if ($this->request->post()) {
            $params = $this->request->param();
            $res = $this->validate($params, 'Task');
            $flag = false;
            if ($res !== true) {
                $this->error($res);
            } else {

                $money = Db::name('shopkeeper')->where(['id'=>$this->shopkeeper_id])->value('money');
                $need_money = $params['task_num']*($params['commission']+$params['product_price']);

                if ($money < $need_money) {
                    $this->error('余额不足,请充值');
                } else {
                    Db::startTrans();
                    try {
                        if (!input('product_img') && $this->request->file('product_img')) {
                            $uploader = new Upload();
                            $uploader->setFormName('product_img');
                            $result = $uploader->upload();
                            if ($result === false) {
                                $this->error($uploader->getError());
                            } else {
                                $params['product_img'] = $result['filepath'];
                                $result = true;
                            }
                        } else {
                            $result = true;
                        }
                        if ($result) {
                            $params['remain_task_num'] = $params['task_num'];
                            $params['start_time'] = strtotime($params['start_time']);
                            $params['status'] = 0;
                            $rs = Db::name('task')->where(['id'=>$id])->update($params);
                            if ($rs) {
                                Db::commit();
                                $flag = true;
                            }
                        }
                    } catch (\Exception $e) {
                        Db::rollback();
                    }

                    if ($flag) {
                        $this->success('发布任务成功');
                    } else {
                        Db::rollback();
                        $this->error('发布任务失败');
                    }
                }
            }
        }

        $info =  Db::name('user a')
            ->join('shopkeeper b', 'a.id=b.user_id')
            ->join('shop c', 'b.id=c.shopkeeper_id')
            ->join('task d', 'c.id=d.shop_id')
            ->join('process e', 'e.id=d.process_id')
            ->field('a.user_login, a.qq, c.name, c.link, c.type, d.*, e.name as process_name, d.reject_reason')
            ->where(['d.id'=>$id])
            ->find();
        $this->assign('task', $info);
        $processes = Db::name('process')->select();
        $shops = Db::name('user a')
            ->join('shopkeeper b', 'a.id=b.user_id')
            ->join('shop c', 'b.id=c.shopkeeper_id')
            ->field('c.id, c.name')
            ->where(['a.id'=>$this->user_id])
            ->select();
        $this->assign('processes', $processes);
        $this->assign('shops', $shops);
        return $this->fetch();
    }

    /**
     *   生成订单号
     */
    protected function get_task_no()
    {
        $ors = 'RW'.date('Ymd').$this->random(6,1);
        $count = Db::name('task')->where(['task_no'=>$ors])->count();
        if ($count) {
            return $this->get_task_no();
        }
        return $ors;
    }

    //充值
    public function recharge() {
        if ($this->request->isAjax()) {
            $pay = new PayService();
            $url = $pay->create_order();
            return;
        } else {
            return $this->fetch();
        }
    }

    //删除草稿
    function draft_del() {
        $id = input('id', 0);
        if (!$id) $this->error('数据传输错误');
        Db::name('task')->where(['id'=>$id])->delete();
        $this->success('删除成功!');
    }

    //计算佣金
    public function  calculate_commission() {
        $params = $this->request->param();
        if (!key_exists('step_id', $params)) $params['step_id'] = array();
        $commission = $this->cal_commission($params['process_id'], $params['price']) + $this->cal_step_fee($params['step_id']);
        $this->success('获取成功', '', $commission);
    }

    private function cal_commission($process_id=0, $price=0) {
        $commission = 0;
        $money = $price;
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

    //计算收费步骤费用
    public function cal_step_fee($step_id=array()) {
        $expenses = 0;
        if (count($step_id) > 0) {
            $expenses = Db::name('process_step')->where(['id'=>['in', $step_id]])->sum('expenses');
        }
        return $expenses;
    }

    //获取任务配置
    public function getTaskCf() {
        $id = input('id');
        $task_no = input('task_no');
        $types = Db::name('process')->where(['platform_id'=>$id,'status'=>1])->select();
        $shops = Db::name('shop')->where(['type'=>$id, 'shopkeeper_id'=>$this->shopkeeper_id])->select();
        if($task_no){
            $task = Db::name('task')->where(['task_no'=>$task_no])->value('process_id');
            $this->success('获取成功', '', array('types'=>$types, 'shops'=>$shops, 'task'=>$task));
        }
        $this->success('获取成功', '', array('types'=>$types, 'shops'=>$shops));
    }

    //查询流程步骤(可选步骤)
    public function loadStep() {
        $task_no = input('task_no');
        $process_id = input('process_id');
        $list = Db::name('process a')
            ->join('process_step b', 'a.id=b.process_id')
            ->join('process_step_action c', 'b.step_type=c.id')
            ->field('b.id, b.step_name,b.step_type, b.expenses,b.is_base,c.shop_action_input')
            ->where(['a.id'=>$process_id, 'b.is_base' => 0])
            ->select()->toArray();

        foreach ($list as $k=>$v){
            if($v['shop_action_input']){
                $list[$k]['shop_action_input']=json_decode($v['shop_action_input'],true);
            }
        }

        if ($task_no) {
            $task = Db::name('task')->where(['task_no' => $task_no])->find();
            if (!empty($task['brush_action_input'])) {
                $brush_action_input= json_decode($task['brush_action_input'], true);
            }
            foreach ($list as $k=>$v){
                foreach ($brush_action_input as $k1=>$v1){
                    if($v['step_type'] == $k1){
                        $list[$k]['brush_action_input']= $brush_action_input[$k1];
                    }
                }
            }

        }
        $this->success('获取成功', '', $list);
    }

    //查询流程步骤（所有步骤）
    public function loadBaseStep() {
        $process_id = input('process_id');
        $task_no = input('task_no');
        $shopList = Db::name('process a')
            ->join('process_step b', 'a.id=b.process_id')
            ->join('process_step_action c', 'b.step_type=c.id')
            ->field('b.id, b.step_name,b.step_type, b.expenses,b.is_base,c.shop_action_input')
            ->where(['a.id'=>$process_id])
            ->select()->toArray();

        foreach ($shopList as $k=>$v){
            if($v['shop_action_input']){
                $shopList[$k]['shop_action_input']=json_decode($v['shop_action_input'],true);
            }
        }
        if ($task_no) {
            $task = Db::name('task')->where(['task_no' => $task_no])->find();
            if (isset($task['brush_action_input']) && !empty($task['brush_action_input']) && !ctype_space($task['brush_action_input'])) {
                $brush_action_input= json_decode($task['brush_action_input'], true);
                foreach ($shopList as $k=>$v){
                    foreach ($brush_action_input as $k1=>$v1){
                        if($v['step_type'] == $k1){
                            $shopList[$k]['brush_action_input']= $brush_action_input[$k1];
                        }
                    }
                }
            }

        }
        $this->success('获取成功', '', $shopList);
    }

    //编辑流程步骤
    public function loadEditStep() {
        $process_id = input('process_id');
        $task_no = input('task_no');
        $shopList = Db::name('process a')
            ->join('process_step b', 'a.id=b.process_id')
            ->join('process_step_action c', 'b.step_type=c.id')
            ->field('b.id, b.step_name,b.step_type, b.expenses,b.is_base,c.shop_action_input')
            ->where(['a.id'=>$process_id])
            ->select()->toArray();

        foreach ($shopList as $k=>$v){
            if($v['shop_action_input']){
                $shopList[$k]['shop_action_input']=json_decode($v['shop_action_input'],true);
            }
        }
        if ($task_no) {
            $task = Db::name('task')->where(['task_no' => $task_no])->find();
            if (isset($task['brush_action_input']) && !empty($task['brush_action_input'])) {
                $brush_action_input= json_decode($task['brush_action_input'], true);

                foreach ($shopList as $k=>$v){
                    foreach ($brush_action_input as $k1=>$v1){
                        if($v['step_type'] == $k1){
                            $shopList[$k]['brush_action_input']= $brush_action_input[$k1];
                        }
                    }
                }
            }
            Db::name('task')->where(['task_no'=>$task_no])->update(['brush_action_input' => ' ']);

        }
        $this->success('获取成功', '', $shopList);
    }

    private function generate_sub_id() {
        $sub_id = strtotime().$this->random(6,1);
        $count = Db::name('task')->where(['sub_id'=>$sub_id])->count();
        if ($count) return generate_sub_id();
        return $sub_id;
    }

    //复制草稿
    public function copy() {
        $task_no = input('task_no');

        $data = Db::name('task')->where(['task_no'=>$task_no, 'shopkeeper_id'=>$this->shopkeeper_id])->find();
        if ($data) {
            Db::startTrans();
            $rs = true;
            try {
                $data['status'] = 9;
                $data['task_no'] = $this->get_task_no();
                unset($data['id']);
                unset($data['reject_reason']);
                unset($data['start_time']);
                unset($data['remain_task_num']);
                unset($data['appeal_reason']);
                unset($data['is_appeal']);
                unset($data['auditer']);
                unset($data['audit_time']);
                unset($data['expire_time']);
                $rs = Db::name('task')->insertGetId($data);
                if ($rs) {
                    $comments = Db::name('task_comment')->field('content,img')->where(['task_no'=>$task_no])->select();
                    $new = array();
                    foreach ($comments as $key => $val) {
                        $new[$key]['content'] = $val['content'];
                        $new[$key]['img'] = $val['img'];
                        $new[$key]['task_no'] = $data['task_no'];
                        $new[$key]['create_time'] = time();
                    }
                    if (count($new) > 0) {
                        $rs = Db::name('task_comment')->insertAll($new);
                    }
                }
                if ($rs) {
                    Db::commit();
                }

            } catch (\Exception $e) {
                Db::rollback();
            }

            if ($rs) {
                $this->success('复制成功', url('task/draft'));
            } else {
                Db::rollback();
                $this->error('复制失败');
            }
        } else {
            $this->error('复制失败');
        }
    }


    //计算买手佣金
    private function cal_brush_commission($process_id=0, $total_money=0, $fee_step) {
        $commission = 0;
        $money = $total_money;
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

        $steps = explode(',', $fee_step);
        if ($steps && count($steps) > 0) {
            $commission += Db::name('process_step')->where(['id'=>['in', $steps]])->sum('commission');
        }

        return $commission;
    }
}