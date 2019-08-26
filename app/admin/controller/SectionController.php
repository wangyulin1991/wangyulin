<?php
/**
 * Created by PhpStorm.
 * User: Administrator
 * Date: 2019/2/12
 * Time: 15:04
 */

namespace app\admin\controller;


use cmf\controller\AdminBaseController;
use think\Db;

class SectionController extends AdminBaseController
{
    public function index() {
        $name = input('name');
        $user_login = input('user_login');
        $user_nickname = input('user_nickname');
        $mobile = input('mobile');
        $startTime = input('start_time');
        $endTime   = input('end_time');
        $login_startTime = input('login_start_time');
        $login_endTime   = input('login_end_time');
        $where = 'b.user_type=1';
        if ($name) {
            $where .= " and a.name like '%{$name}%'";
            $this->assign('name', $name);
        }
        if ($user_login) {
            $where .= " and b.user_login like '%{$user_login}%'";
            $this->assign('user_login', $user_login);
        }
        if ($user_nickname) {
            $where .= " and b.user_nickname like '%{$user_nickname}%'";
            $this->assign('user_nickname', $user_nickname);
        }
        if ($mobile) {
            $where .= " and b.mobile like '%{$mobile}%'";
            $this->assign('mobile', $mobile);
        }
        if ($startTime) {
            $where .= " and b.create_time >= " .strtotime($startTime);
            $this->assign('start_time', $startTime);
        }
        if ($endTime) {
            $where .= " and b.create_time <= ".strtotime($endTime.' 23:59:59');
            $this->assign('end_time', $endTime);
        }
        if ($login_startTime) {
            $where .= " and b.last_login_time >= ".strtotime($login_startTime);
            $this->assign('login_start_time', $login_startTime);
        }
        if ($login_endTime) {
            $where .= " and b.last_login_time <= ".strtotime($login_endTime.' 23:59:59');
            $this->assign('login_end_time', $login_endTime);
        }
        $lists = Db::name('section a')->join('user b', 'a.user_id=b.id')
            ->field('b.mobile,b.user_login,b.user_nickname,b.user_pass,b.last_login_time,a.create_time,"" as payment, a.*')
            ->where($where)
            ->paginate(30);
        $this->assign('lists', $lists);
        $this->assign('page', $lists->render());
        return $this->fetch();
    }

    public function add() {

        if ($this->request->isPost()) {
            $param = $this->request->param();
            $pass = $this->random(6);
            $data = array(
                'user_type'=>1,
                'create_time' => time(),
                'user_login'=>$param['user_login'],
                'user_pass'=>cmf_password($pass),
                'user_nickname'=>$param['user_name'],
                'mobile'=>$param['mobile']
            );
            $id = Db::name('user')->insertGetId($data);
            if ($id) {
                $rs = Db::name('section')->insertGetId(array('user_id'=>$id, 'name'=>$param['name'], 'bank_no'=>$param['bank_no'], 'create_time'=>time()));
                if ($rs) {
                    $rs = sendActiveMsg($param['mobile'], array($pass));
                    if ($rs['code'] == 0) {
                        $rs = Db::name('sms_record')->insertGetId(['mobile'=>$data['mobile'], 'code'=>'pass', 'create_time'=>time()]);
                        $this->success('添加成功', url('section/index'));
                    } else {
                        $this->error('添加失败');
                    }
                } else {
                    $this->error('添加失败');
                }
            } else {
                $this->error('添加失败');
            }
        }

        return $this->fetch();
    }
}