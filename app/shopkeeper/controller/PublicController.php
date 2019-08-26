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

use think\Db;
use think\facade\Validate;
use cmf\controller\HomeBaseController;
use app\shopkeeper\model\UserModel;

class PublicController extends HomeBaseController
{
    /**
     * 后台登陆界面
     */
    public function login()
    {
        $redirect = $this->request->param("redirect");
        if (empty($redirect)) {
            $redirect = $this->request->server('HTTP_REFERER');
        } else {
            if (strpos($redirect, '/') === 0 || strpos($redirect, 'http') === 0) {
            } else {
                $redirect = base64_decode($redirect);
            }
        }
        if(!empty($redirect)){
            session('login_http_referer', $redirect);
        }
        if (cmf_is_user_login()) { //已经登录时直接跳到首页
            return redirect($this->request->root() . '/shopkeeper');
        } else {
            return $this->fetch();
        }
    }

    /**
     * 登录验证提交
     */
    public function doLogin()
    {
        if ($this->request->isPost()) {
            $validate = new \think\Validate([
                'captcha'  => 'require',
                'username' => 'require',
                'password' => 'require|min:6|max:32',
            ]);
            $validate->message([
                'username.require' => '用户名不能为空',
                'password.require' => '密码不能为空',
                'password.max'     => '密码不能超过32个字符',
                'password.min'     => '密码不能小于6个字符',
                'captcha.require'  => '验证码不能为空',
            ]);

            $data = $this->request->post();
            if (!$validate->check($data)) {
                $this->error($validate->getError());
            }

            if (!cmf_captcha_check($data['captcha'])) {
                $this->error(lang('CAPTCHA_NOT_RIGHT'));
            }

            $userModel         = new UserModel();
            $user['user_pass'] = $data['password'];
            if (Validate::is($data['username'], 'email')) {
                $user['user_email'] = $data['username'];
                $log                = $userModel->doEmail($user);
            } else if (cmf_check_mobile($data['username'])) {
                $user['mobile'] = $data['username'];
                $log            = $userModel->doMobile($user);
            } else {
                $user['user_login'] = $data['username'];
                $log                = $userModel->doName($user);
            }
            $session_login_http_referer = session('login_http_referer');
            $redirect                   = empty($session_login_http_referer) ? $this->request->root(): $session_login_http_referer;
            switch ($log) {
                case 0:
                    $login_user = cmf_get_current_user();
                    if ($login_user['user_type'] == 2) {
                        $redirect = $this->request->root().'/shopkeeper/index/index';
                    }
                    cmf_user_action('login');
                    $this->success(lang('LOGIN_SUCCESS'), $redirect);
                    break;
                case 1:
                    $this->error(lang('PASSWORD_NOT_RIGHT'));
                    break;
                case 2:
                    $this->error('账户不存在');
                    break;
                case 3:
                    $this->error('账号被禁止访问系统');
                    break;
                case 4:
                    $this->error('非商戶账号禁止访问系统');
                    break;
                default :
                    $this->error('未受理的请求');
            }
        } else {
            $this->error("请求错误");
        }
    }

    public function findPassword() {
        if ($this->request->isPost()) {
            $validate = new \think\Validate([
                'username' => 'require',
            ]);
            $validate->message([
                'username.require' => '手机号不能为空',
            ]);

            $data = $this->request->post();
            if (!$validate->check($data)) {
                $this->error($validate->getError());
            }
            $username = input('username');
            $userModel = new UserModel();
            $result = $userModel->where('user_login', $username)->find();
            if ($result) {
                $temp_pw = $this->random(6,1);
                resetPassword($result['mobile'], array($temp_pw));
                $rs = Db::name('user')->where(['id'=>$result['id']])->update(['user_pass'=>cmf_password($temp_pw)]);
                if ($rs) {
                    $this->success('发送成功', url('shopkeeper/public/login'));
                }
            } else {
                $this->error('用户不存在！');
            }
        }
        return $this->fetch('find_pw');
    }


    /**
     * 后台管理员退出
     */
    public function logout()
    {
        session('SHOPKEEPER_ADMIN_ID', null);
        session("user", null);//只有前台用户退出
        return redirect(url('/shopkeeper', [], false, true));
    }

    protected function log_message($msg = '')
    {
        $file = '/home/wangmeng/web/mission/data/log/log.txt';
        file_put_contents($file, date('Y-m-d H:i:s') . "\r\n" . var_export($msg, true) . "\r\n\r\n", FILE_APPEND | LOCK_EX);
    }
}