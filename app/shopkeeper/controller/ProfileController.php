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

use cmf\controller\ShopkeeperBaseController;
use think\Db;
use app\admin\model\Menu;
use app\common\service\PayService;
use think\Validate;

class ProfileController extends ShopkeeperBaseController
{

    /**
     *  欢迎页
     */
    public function index()
    {
        $user = Db::name('user')->where(['id'=>$this->user_id])->find();
        $this->assign('user', $user);
        return $this->fetch();
    }

    //个人信息
    public function info() {
        if ($this->request->isPost()) {
            $param = $this->request->param();
            if ($param['birthday']) {
                $param['birthday'] = strtotime($param['birthday']);
            }
            $user = cmf_get_current_user();
            $user['user_nickname'] = $param['user_nickname'];
            $user['sex'] = $param['sex'];
            $user['avatar'] = $param['avatar'];
            session('user', $user);
            $rs = Db::name('user')->where(['id'=>$this->user_id])->update($param);
            if ($rs) {
                $this->success('更新成功');
            } else {
                $this->error('更新失败');
            }
        }
    }

    // 用户密码修改
    public function pass()
    {
        if ($this->request->isPost()) {
            $validate = new Validate([
                'old_password'     => 'require',
                'password'         => 'require',
                'confirm_password' => 'require|confirm:password'
            ]);

            $validate->message([
                'old_password.require'     => '请输入您的旧密码!',
                'password.require'         => '请输入您的新密码!',
                'confirm_password.require' => '请输入确认密码!',
                'confirm_password.confirm' => '两次输入的密码不一致!'
            ]);

            $data = $this->request->param();
            if (!$validate->check($data)) {
                $this->error($validate->getError());
            }

            $userId       = $this->user_id;
            $userPassword = Db::name("user")->where('id', $userId)->value('user_pass');

            if (!cmf_compare_password($data['old_password'], $userPassword)) {
                $this->error('旧密码不正确!');
            }

            $code = Db::name('sms_record')->where(['type'=>1, 'mobile'=>$this->user['mobile']])->order('id', 'desc')->find();
            if (empty($code) || $code['code'] != $data['code']) {
                $this->error('验证码不正确!');
            } else if ($code['expire_time'] < time()){
                $this->error('验证码已过期!');
            }

            Db::name("user")->where('id', $userId)->update(['user_pass' => cmf_password($data['password'])]);

            $this->success("密码修改成功!", url('profile/pass'));
        }
        return $this->fetch();
    }

    //短信验证码
    public function send_sms_code() {
        $code = $this->random(6,1);
        $rs = sendSmsCode($this->user['mobile'], array($code, '10'));
        if ($rs['code'] == 0) {
            $rs = Db::name('sms_record')->insertGetId(['mobile'=>$this->user['mobile'], 'type'=>1, 'code'=>$code, 'expire_time'=>time()+600, 'create_time'=>time()]);
            if ($rs) {
                $this->success('发送成功');
            }
        }
    }

    //转账信息
    public function transfer() {
        if ($this->request->isPost()) {
            $rs = Db::name('shopkeeper')->where(['id'=>$this->shopkeeper_id])->update($this->request->param());
            if ($rs) {
                $this->success('更新成功');
            } else {
                $this->error('更新失败');
            }
        }
        $this->assign('user', Db::name('shopkeeper')->find(['id'=>$this->shopkeeper_id]));
        return $this->fetch();
    }

    //充值 废弃
    public function recharge() {
        if ($this->request->isPost()) {
            $validate = new Validate([
                'pay_way'     => 'require',
                'vild_code'     => 'require',
                'money'         => 'require|number|gt:0',
            ]);

            $validate->message([
                'pay_way.require'     => '请选择支付方式!',
                'vild_code.require'         => '数据传输错误!',
                'money.require'         => '请输入充值金额!',
                'money.number' => '必须是数字!',
                'money.gt' => '充值金额必须大于0!'
            ]);

            $data = $this->request->param();

            if (!$validate->check($data)) {
                $this->error($validate->getError());
            }
            if($data['pay_way'] == 0){
                $this->assign('is_success', 'Y');
                $data = array(
                    'order_no' => "ZF".date('YmdHis').$this->random(2,1),
                    'money' => $data['money'],
                    'type' => 1,
                    'pay_way' => 0,
                    'purpose' => 1,
                    'vild_code' => $data['vild_code'],
                    'user_id' => cmf_get_current_user_id(),
                    'status' => 0,
                    'create_time' => time(),
                );
                $id = Db::name('account_statement')->insertGetId($data['order_no']);
                if ($id) {
                    $this->redirect('alipay/recharge', array($data['order_no'], $data['money']));
                } else{
                    $this->error('调起支付宝失败!');
                }
            }else if($data['pay_way'] == 1){
                $this->assign('is_success', 'Y');
                $data = array(
                    'order_no' => "ZF".date('YmdHis').$this->random(2,1), //商户订单号
                    'money' => $data['money'],
                    'type' => 1,
                    'pay_way' => 1,
                    'purpose' => 1,
                    'vild_code' => $data['vild_code'],
                    'user_id' => cmf_get_current_user_id(),
                    'status' => 0,
                    'create_time' => time(),//商户订单时间
                );
                $dt_order = date('YmdHms',$data['create_time']);
                $no_order = substr($data['order_no'],2);
                $id = Db::name('account_statement')->insertGetId($data);

                if ($id) {
                    $data['no_order'] = $no_order;
                    $data['dt_order'] = $dt_order;
                    $this->redirect('Llpay/recharge', array($data['no_order'], $data['money'],$data['dt_order']));
                } else{
                    $this->error('调起连连支付失败!');
                }
            }
        }
        $this->assign('vild_code', time());
        return $this->fetch();
    }

    //二维码充值
    public function qr_recharge() {
        if ($this->request->isPost()) {
            $data = $this->request->param();
            $data = array(
                'order_no' => "ZF".date('YmdHis').$this->random(2,1),
                'money' => $data['money'],
                'type' => 1,
                'pay_way' => 0,
                'purpose' => 1,
                'vild_code' => $data['vild_code'],
                'user_id' => cmf_get_current_user_id(),
                'status' => 0,
                'create_time' => time(),
            );
            $id = Db::name('account_statement')->insertGetId($data);
            if ($id) {
                $pay = new AlipayController();
                $rs = $pay->qr_recharge($data['order_no'], $data['money']);
                if ($rs) {
                    $url = 'http://qr.liantu.com/api.php?text='.$rs;
                    $this->assign('qr', $url);
                    //$this->assign('vild_code', $data['vild_code']);
                    $this->assign('order_no', $data['order_no']);
                    return $this->fetch('qr');
                } else {
                    $this->error('调起支付宝失败!');
                }
            } else{
                $this->error('调起支付宝失败!');
            }
        }
        $this->assign('vild_code', time());
        return $this->fetch();
    }
}
