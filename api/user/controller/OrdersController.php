<?php
/**
 * Created by billy.
 *
 * Name:
 * Author: billy
 * Date: 2019/2/14
 * Time: 19:01
 */
namespace api\user\controller;

use api\task\model\TaskModel;
use cmf\controller\RestUserBaseController;
use api\task\model\OrderModel;
use think\Db;

class OrdersController extends RestUserBaseController
{
    private $order_status=['进行中','待审核','未通过','已打款','已结束','已过期','申诉中'];
    //我的任务列表
    public function listing()
    {
        $page = $this->request->param("page", 1, 'intval');
        $status = $this->request->param("status", 0, 'intval');
        if ($status !== null) {
            if ($status == 4) {
                $map['o.status'] = ['in', [4,6]];
            } else {
                $map['o.status'] = (int)$status;
            }
        }
        $time = time();
        $map['brush_guest_id'] = $this->userId;
        $orderModel = new OrderModel();
        $orders = $orderModel->alias('o')->join('task t', 'o.task_id = t.id')->field('t.id as task_id, o.id, o.order_number, t.task_name, t.product_img, o.amount, o.deadline, o.apply_cash, t.brush_guest_money')->where($map)->page($page,10)->select()->toArray();
        foreach ($orders as $k=>$v){
            $orders[$k]['task_name'] = mb_substr($v['task_name'],0,3).'***';
        }

        if ($status == 2) {
            $list = Db::name('appointment a')->join('task b','a.task_id=b.id')->field('b.id as task_id, b.task_no, b.task_name, b.brush_guest_money, b.product_img, b.start_time, a.reject_time as deadline')->where(['a.status'=>['in', [0,2]], 'a.brush_guest_id'=>$this->userId])->page($page,10)->select()->toArray();
            $orders = array_merge($orders, $list);
        }

//        $orderModel->save(['status'=>5],'brush_guest_id = ' . $this->userId . ' AND status=0 AND deadline <' . $time);
        if ($orders) {
            foreach ($orders as $key=>$order){
                $orders[$key]['deadline_text'] = date('Y年m月d日 H时i分s秒',$order['deadline']);
                $orders[$key]['product_img'] = cmf_get_image_url($order['product_img']);
                if (!isset($order['apply_cash'])) {
                    $orders[$key]['apply_cash'] = 0;
                }
                $orders[$key]['apply_cash_text'] = '订单付款后3天并且需要物流显示已经签收，才能进行订单签收!';
            }
            $this->success('获取成功！', $orders);
        } else {
            $this->success('没有数据',[]);
        }
    }

    //任务详情
    public function detail()
    {
        $task_id = $this->request->param("task_id", 0, 'intval');
        if ($task_id > 0) {
            $taskModel = new TaskModel();
            $info = $taskModel->alias('t')
                ->join('order o', 'o.task_id = t.id', 'LEFT')
                ->join('appointment ap', 'ap.order_id = o.id', 'LEFT')
                ->join('task_comment tc', 'tc.id = o.comment_id', 'LEFT')
                ->join('shop s', 's.id = t.shop_id', 'LEFT')
                ->join('process p', 'p.id = t.process_id', 'LEFT')
                ->field('o.id, o.order_number, ap.status as app_status, t.task_name, t.product_img, t.product_link, s.name as shop_name, p.name as process_name, o.key_word, ap.keyword as app_keyword, t.product_price as amount, o.status, o.deadline, t.brush_guest_money, tc.content, tc.img')
                ->where(['t.id'=>$task_id,'o.brush_guest_id'=>$this->userId])
                ->find();
            if($info){
                $info['product_img'] = cmf_get_image_url($info['product_img']);
                $info['task_name'] = mb_substr($info['task_name'],0,3).'***';
                $info['special_desc'] = $info['special_desc'].' 【请按要求做单！传错图片扣除佣金，请谨慎！】';
                if (is_numeric($info['status'])) {
                    $info['status_text'] = $this->order_status[$info['status']];
                } else {
                    $info['status_text'] = '未通过';
                }
                if ($info['img']) {
                    $info['img'] = explode(';', $info['img']);
                } else {
                    $info['img'] = array();
                }
                $this->success('获取成功！', $info);
            } else {
                $this->success('未找到订单',[]);
            }
        } else {
            $this->error('参数错误');
        }
    }

    public function appointment() {
        $orders = Db::name('appointment a')
            ->join('user b', 'b.id=a.brush_guest_id')
            ->join('task c', 'a.task_id=c.id')
            ->join('shop d', 'c.shop_id=d.id')
            ->field('d.name as shop_name, c.product_img, c.start_time,c.commission, c.product_price, a.keyword, b.user_login,a.create_time, a.status,a.id,c.id as task_id,c.task_name')
            ->where(['b.id'=>$this->userId, 'c.start_time'=>['gt', time()],'a.status'=>1])
            ->order("a.id DESC")
            ->select()->toArray();
        foreach ($orders as $key=>$order){
            $orders[$key]['product_img'] = cmf_get_image_url($order['product_img']);
        }
        $this->success('获取成功！', $orders);
    }
}