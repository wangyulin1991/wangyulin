<?php
// +----------------------------------------------------------------------
// | ThinkCMF [ WE CAN DO IT MORE SIMPLE ]
// +----------------------------------------------------------------------
// | Copyright (c) 2013-2018 http://www.thinkcmf.com All rights reserved.
// +----------------------------------------------------------------------
// | Licensed ( http://www.apache.org/licenses/LICENSE-2.0 )
// +---------------------------------------------------------------------
// | Author: Dean <zxxjjforever@163.com>
// +----------------------------------------------------------------------
namespace cmf\controller;

use think\Db;

class UserBaseController extends HomeBaseController
{
    protected $user;
    protected $user_id;
    protected $shopkeeper_id;
    public function initialize()
    {
        parent::initialize();
        $this->checkUserLogin();
        $this->user_id = cmf_get_current_user_id();
        $this->user = Db::name('user')->where(['id'=>$this->user_id])->find();
        $this->shopkeeper_id = Db::name('shopkeeper')->where(['user_id'=>$this->user_id])->value('id', 0);
    }
}