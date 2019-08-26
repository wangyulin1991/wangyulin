<?php
/**
 * Created by PhpStorm.
 * User: Admin
 * Date: 2019/1/26 0026
 * Time: 16:17
 */

namespace app\shopkeeper\controller;

use cmf\controller\ShopkeeperBaseController;
use think\Db;

class RecommendController extends ShopkeeperBaseController
{
    public function index() {

        /**搜索条件**/
        $user_id = cmf_get_current_user_id();
        $param = $this->request->param();
        $where = 'a.user_type = 2 and b.f_id='.$user_id;
        $dateStr = date('Y-m-d', time());
        $timestamp0 = strtotime($dateStr);
        $timestamp24 = strtotime($dateStr) + 86400;
//        $where .= ' and e.create_time > '.$timestamp0;
//        $where .= ' and e.create_time < '.$timestamp24;

        $startTime = empty($param['start_time']) ? 0 : strtotime($param['start_time']);
        $endTime   = empty($param['end_time']) ? 0 : strtotime($param['end_time']);
        if (!empty($startTime)) {
            $where .= ' and a.create_time >= '.$startTime;
            $this->assign('start_time', $param['start_time']);
        }
        if (!empty($endTime)) {
            $where .= ' and a.create_time <= '.$endTime;
            $this->assign('end_time', $param['end_time']);
        }
        if (!empty($param['mobile'])) {
            $where .= ' and a.mobile = '.$param['mobile'];
            $this->assign('mobile', $param['mobile']);
        }

        $users = Db::name('user a')
            ->join('shopkeeper b', 'a.id=b.user_id')
            ->where($where)
            ->field('a.id as aid, a.user_login, a.user_nickname, a.user_status, a.mobile,a.qq,a.create_time')
            ->order("a.id DESC")
            ->paginate(15);
        // 获取分页显示
        $temp = $users->items();
        $page = $users->render();
        $this->assign("page", $page);
        $this->assign("items", $temp);

        return $this->fetch();
    }

    //未使用
    public function makeMoney(){
        $dateStr = date('Y-m-d', time());
        $timestamp0 = strtotime($dateStr);
        $timestamp24 = strtotime($dateStr) + 86400;
        $where = 'b.user_type = 2 ';
        $where .= ' and e.create_time > '.$timestamp0;
        $where .= ' and e.create_time < '.$timestamp24;
        $reslult = Db::name('shopkeeper a')
            ->join('user b', 'a.user_id=b.id')
            ->join('shop c', 'a.id=c.shopkeeper_id', 'left')
            ->join('task d', 'c.id=d.shop_id', 'left')
            ->join('order e', 'd.id=e.task_id and e.status=4', 'left')
            ->where($where)
            ->group('a.id')
            ->field('a.id as shopkeeperId,a.money,a.user_id,count(e.id) as com_order_count,a.f_id,a.gf_id')
            ->select()->toArray();
        $arr1=[];
        $arr2=[];
        foreach ($reslult as $k=>$v){
            if($v['f_id'] > 0){
                $arr1[$k]['shopkeeperId']=$v['shopkeeperId'];
                $arr1[$k]['f_id']=$v['f_id'];
                $arr1[$k]['gf_id']=$v['gf_id'];
                $arr1[$k]['user_id']=$v['user_id'];
                $arr1[$k]['up_money']=$v['com_order_count']*0.6;
            }
            if($v['gf_id'] > 0){
                $arr2[$k]['shopkeeperId']=$v['shopkeeperId'];
                $arr2[$k]['f_id']=$v['f_id'];
                $arr2[$k]['gf_id']=$v['gf_id'];
                $arr2[$k]['user_id']=$v['user_id'];
                $arr2[$k]['up_money']=$v['com_order_count']*0.4;
            }
        }

        $item1=[];
        $item2=[];
        if(!empty($arr1)){
            foreach($arr1 as $k=>$v){
                if(!isset($item1[$v['f_id']])){
                    $item1[] = $v;
                }
            }
        }
        if(!empty($arr2)){
            foreach($arr2 as $k=>$v){
                if(!isset($item2[$v['gf_id']])){
                    $item2[] = $v;
                }
            }
        }
        if(!empty($item1)){
            foreach($item1 as $k=>$v){
                $balance =Db::name('shopkeeper')->where('user_id = '.$k)->value('money');
                if($v['up_money'] > 0) {
                    $flag = false;
                    Db::startTrans();
                    try {
                        $data = array(
                            'account_no' => "YJ".date('YmdHis').$this->random(2,1),
                            'from_user'=>$v['user_id'],
                            'to_user'=>$v['f_id'],
                            'f_id'=>$v['f_id'],
                            'money'=>$v['up_money'],
                            'balance'=>$balance+$v['up_money'],
                            'create_time' => time(),
                            'grade' => '1',
                        );

                        $res= Db::name('account_recommend')->data($data)->insert();
                        if ($res) {
                            $re = Db::name('shopkeeper')->where('user_id = '.$v['f_id'])->setField('money', $data['balance']);
                            if ($re) {
                                $flag = true;
                                Db::commit();
                            }
                        }
                    } catch (\Exception $e) {
                        Db::rollback();
                    }
                }
            }
        }
        if(!empty($item2)){
            foreach($item2 as $k=>$v){
                $balance =Db::name('shopkeeper')->where('user_id = '.$k)->value('money');
                if($v['up_money'] > 0) {
                    $flag = false;
                    Db::startTrans();
                    try {
                        $data = array(
                            'account_no' => "YJ".date('YmdHis').$this->random(2,1),
                            'from_user'=>$v['user_id'],
                            'to_user'=>$v['gf_id'],
                            'f_id'=>$v['f_id'],
                            'money'=>$v['up_money'],
                            'balance'=>$balance+$v['up_money'],
                            'create_time' => time(),
                            'grade' => '2',
                        );
                        $res= Db::name('account_recommend')->data($data)->insert();
                        if ($res) {
                            $re = Db::name('shopkeeper')->where('user_id = '.$v['gf_id'])->setField('money', $data['balance']);
                            if ($re) {
                                $flag = true;
                                Db::commit();
                            }
                        }
                    } catch (\Exception $e) {
                        Db::rollback();
                    }
                }
            }
        }

    }

    //推荐佣金账目
    public function reMoneyList() {
        $start_time = input('start_time');
        $end_time = input('end_time');
        $mobile = input('mobile');
        //echo $this->user_id;die;
        $where = 'a.to_user = '.$this->user_id;
        if (!empty($start_time)) {
            $where .= ' and a.create_time > '.strtotime($start_time);
            $this->assign('start_time', $start_time);
        }
        if (!empty($end_time)) {
            $where .= ' and a.create_time < '.strtotime($end_time);
            $this->assign('end_time', $end_time);
        }

        $records = Db::name('account_recommend a')
            ->join('shopkeeper b','b.user_id = a.from_user', 'left')
            ->join('user c','b.user_id=c.id', 'left')
            ->field('a.money as makeMoney,a.create_time,a.grade,c.user_nickname,c.id')
            ->where($where)
            ->order('a.create_time', 'desc')
            ->paginate(30);

//        $all_money = 0;
//        foreach ($records as $key => $val) {
//            if ($val['money'] ) {
//                 $all_money += $val['money'];
//            }
//        }
        //print_r($all_money);die;
        $this->assign('records', $records);
        $this->assign('total', $records->total());
        $this->assign('page', $records->render());
//        $this->assign('all_money', $all_money);
        return $this->fetch();
    }

    //添加商户
    public function add() {
        $user_id = cmf_get_current_user_id();
        //$vip =Db::name('shopkeeper')->where('user_id = '.$user_id)->value('isVip');
        if ($this->request->isPost()) {
//            if($vip == 1){//判断是否为vip用户
                $param  = $this->request->param();

                $res = $this->validate($param, 'Shopkeeper');
                $flag = false;
                if ($res !== true) {
                    $this->error($res);
                } else {
                    //$param['f_id']= cmf_get_current_user_id();
                    //print_r($param);
                    Db::startTrans();
                    try {
                        $pass = $this->random(6);
                        $data = array(
                            'user_type'=>2,
                            'create_time' => time(),
                            'user_login'=>$param['mobile'],
                            'user_pass'=>cmf_password($pass),
                            'user_nickname'=>'商户'.$param['mobile'],
                            'mobile'=>$param['mobile'],
                            'qq'=>$param['qq'],
                            'user_status'=>1
                        );

                        $res = Db::name('user')->insertGetId($data);
                        if ($res) {
                            $param['create_time'] = time();
                            $param['f_id'] = $user_id;
                            $param['gf_id'] = $param['tj_id'];
                            unset($param['tj_id']);
                            unset($param['mobile']);
                            unset($param['qq']);
                            $param['user_id'] = $res;
                            $res = Db::name('shopkeeper')->insertGetId($param);
                            if ($res) {
                                $rs = shopkeeperAdd($data['mobile'], array($pass));
                                if ($rs['code'] == 0) {
                                    $rs = Db::name('sms_record')->insertGetId(['mobile'=>$data['mobile'], 'code'=>'pass', 'create_time'=>time()]);
                                    if ($rs) {
                                        $flag = true;
                                        Db::commit();
                                    }
                                }
                            }
                        }
                    } catch (\Exception $e) {
                        Db::rollback();
                    }

                    if ($flag) {
                        $this->success('添加成功');
                    } else {
                        Db::rollback();
                        $this->error('添加商家失败');
                    }
                }

//            }else{
//                return $this->error('请升级为Vip会员！');
//            }
        }
        $sections =Db::name('section s')
            ->join('shopkeeper sh','sh.user_id='.$user_id)
            ->where('s.id = sh.section_id')
            ->field('s.id,s.name,sh.f_id')
            ->find();

        $this->assign('sections', $sections);
        return $this->fetch();
    }
}
