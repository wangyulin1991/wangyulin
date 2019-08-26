<?php
/**
 * Created by PhpStorm.
 * User: Administrator
 * Date: 2019/1/25
 * Time: 17:49
 */

namespace app\admin\controller;

use cmf\controller\AdminBaseController;
use think\Db;
use think\Validate;

class ShopkeeperController extends AdminBaseController
{
    //商户列表
    public function index() {

        /**搜索条件**/
        $param = $this->request->param();
        $where = 'a.user_type = 2 and a.user_status=1';
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
        if (!empty($param['section_id'])) {
            $where .= ' and b.section_id = '.$param['section_id'];
        }
        $this->assign('section_id', input('section_id'));
        if (!empty($param['mobile'])) {
            $where .= ' and a.mobile = '.$param['mobile'];
            $this->assign('mobile', $param['mobile']);
        }
        if (!empty($param['qq'])) {
            $where .= ' and a.qq = '.$param['qq'];
            $this->assign('qq', $param['qq']);
        }
        $users = Db::name('user a')
            ->join('shopkeeper b', 'a.id=b.user_id')
            ->join('shop c', 'b.id=c.shopkeeper_id', 'left')
            ->join('task d', 'c.id=d.shop_id', 'left')
            ->where($where)
            ->field('b.id as shopkeeper_id,b.task_audit, a.id, a.user_login, a.user_status, a.mobile,a.qq,b.money,b.name,b.alipay_no,b.bank_no,b.is_rec,a.create_time,a.last_login_time, count(d.id) as task_count')
            ->group('a.id')
            ->order("a.id DESC")
            ->paginate(15);
        // 获取分页显示
        $temp = $users->items();
        foreach ($temp as $key => $val) {
            $temp[$key]['shop_count'] = Db::name('shop')->where(['shopkeeper_id'=>$val['shopkeeper_id']])->count();
        }
        $page = $users->render();
        $this->assign("page", $page);
        $this->assign("users", $users);
        $this->assign("items", $temp);
        $this->assign('sections', Db::name('section')->select());
        return $this->fetch();
    }

    //待审核商户列表
    public function auditList() {

        /**搜索条件**/
        $param = $this->request->param();
        $where = 'a.user_type = 2 and user_status=0';
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
        if (!empty($param['section_id'])) {
            $where .= ' and b.section_id = '.$param['section_id'];
        }
        $this->assign('section_id', input('section_id'));
        if (!empty($param['mobile'])) {
            $where .= ' and a.mobile = '.$param['mobile'];
            $this->assign('mobile', $param['mobile']);
        }
        if (!empty($param['qq'])) {
            $where .= ' and a.qq = '.$param['qq'];
            $this->assign('qq', $param['qq']);
        }
        $users = Db::name('user a')
            ->join('shopkeeper b', 'a.id=b.user_id')
            ->join('shop c', 'b.id=c.shopkeeper_id', 'left')
            ->join('task d', 'c.id=d.shop_id', 'left')
            ->where($where)
            ->field('b.id as shopkeeper_id, a.id, a.user_login, a.user_status, a.mobile,a.qq,b.money,b.name,b.alipay_no,b.bank_no,a.create_time, count(d.id) as task_count')
            ->group('a.id')
            ->order("a.id DESC")
            ->paginate(15);
        // 获取分页显示
        $temp = $users->items();
        foreach ($temp as $key => $val) {
            $temp[$key]['shop_count'] = Db::name('shop')->where(['shopkeeper_id'=>$val['shopkeeper_id']])->count();
        }
        $page = $users->render();
        $this->assign("page", $page);
        $this->assign("users", $users);
        $this->assign("items", $temp);
        $this->assign('sections', Db::name('section')->select());
        return $this->fetch();
    }

    //添加商户
    public function add() {
        if ($this->request->isPost()) {
            $param  = $this->request->param();
            $res = $this->validate($param, 'Shopkeeper');

            $flag = false;
            if ($res !== true) {
                $this->error($res);
            } else {
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
                        'qq'=>$param['qq']
                    );
                    $res = Db::name('user')->insertGetId($data);
                    if ($res) {
                        $param['create_time'] = time();
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
        }
        $this->assign('sections', Db::name('section')->select());
        return $this->fetch();
    }

    /**
     * 编辑商户
     */
    public function edit() {
        $id = input('id');
        if (!$id) {
            $this->error('数据传输错误!');
        }
        if ($this->request->isPost()) {
            Db::name('user')->where('id='.$id)->update(['qq'=>input('qq')]);
            Db::name('shopkeeper')->where('user_id='.$id)->update([
                'name'=>input('name'),
                'province'=>input('province'),
                'city'=>input('city'),
                'region'=>input('region'),
                'alipay_no'=>input('alipay_no'),
                'bank_no'=>input('bank_no'),
                'is_rec'=>input('is_rec'),
                'task_audit'=>input('task_audit'),
            ]);
            $this->success('保存成功!');
        }
        $user = Db::name('user a')
            ->join('shopkeeper b', 'a.id=b.user_id')
            ->field('a.id, b.section_id,b.name,b.province,b.city,b.region,b.alipay_no,b.task_audit,b.is_rec,b.bank_no,a.qq')
            ->where('a.id='.$id)
            ->find();
        $this->assign('user', $user);
        return $this->fetch();
    }

    /**
     * 冻结商户
     */
    public function freeze() {
        $id = input('param.id', 0, 'intval');
        if (!$id) {
            $this->error('数据传输错误!');
        }
        Db::name('user')->where(['id'=>$id, "user_type" => 2])->setField('user_status', 0);
        $this->success('冻结成功');
        return $this->fetch();
    }

    /**
     * 激活商户
     */
    public function active() {
        $id = input('param.id', 0, 'intval');
        if ($id) {
            Db::name("user")->where(["id" => $id, "user_type" => 2])->setField('user_status', 1);
            $this->success("启用成功！", '');
        } else {
            $this->error('数据传入失败！');
        }
    }

    //显示商铺
    public function show_shop() {
        $id = input('id');
        $shops = Db::name('user a')
            ->join('shopkeeper b', 'a.id=b.user_id')
            ->join('shop c', 'b.id=c.shopkeeper_id')
            ->field('b.section_id, c.*')
            ->where(['a.id'=>$id])
            ->select();
        $this->assign('shops', $shops);
        return $this->fetch();
    }
    //商户充值
    public function recharge() {
        $id = input('id',0);
        $where = 'a.user_type = 2 and a.id='.$id;
        $users = Db::name('user a')
            ->join('shopkeeper b', 'a.id=b.user_id')
            ->where($where)
            ->field('b.id as shopkeeper_id,b.user_id,a.id, a.user_login, a.user_status, a.mobile,b.money')
            ->find();
        //var_dump($users);die;
        $this->assign('shoper', $users);
        return $this->fetch();
    }
    //改变余额
    public function money() {
        $validate = new Validate([
            'add_money'         => 'require|number|gt:0',
        ]);
        $validate->message([
            'add_money.require'=> '请输入充值金额!',
            'add_money.number' => '必须是数字!',
            'add_money.gt' => '充值金额必须大于0!'
        ]);
        $data = $this->request->param();
        if (!$validate->check($data)) {
            $this->error($validate->getError());
        }
        //充值后余额
        $all_money = $data['money'] + $data['add_money'];

        $data2 = array(
            'order_no' => "ZF".date('YmdHis').$this->random(2,1),
            'money'=>$data['add_money'],
            'balance'=>$all_money,
            'create_time'=>time(),
            'pay_way' => 0,
            'type' => 1,
            'purpose' => 1,
            'user_id' => $data['user_id'],
            'status' => 0,
            'cash_explain' => $data['cash_explain'],
        );
//$this->success($data2['user_id']);die;
        $aid = Db::name('account_statement')->insertGetId($data2);
        if($aid){
            $res = Db::name('shopkeeper')->update(['money' => $all_money , 'id' => $data['id']]);
            if($res){
                Db::name('account_statement')->update(['status' => 1 , 'id' => $aid]);
                return json(['msg'=>'充值成功！','code'=>200]);
            }else{
                return json(['msg'=>'充值失败！','code'=>400]);
            }
        }else{
            return json(['msg'=>'写入记录失败！','code'=>400]);
        }


    }

}
