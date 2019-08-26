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

class RestUserBaseController extends RestBaseController
{

    public function _initialize()
    {

        if (empty($this->user)) {
            $this->error(['code' => 10001, 'msg' => '登录已失效!']);
        }

        if ($this->user['user_status'] == 0 )
        {
            $this->error(['code' => 10002, 'msg' => '账号被封禁!']);
        }

        if ($this->user['user_status'] == 2 )
        {
            $this->error(['code' => 10002, 'msg' => '未验证']);
        }

    }

    protected function log_message($msg = '')
    {
        $file = '/www/wwwroot/mission/data/log/log.txt';
        file_put_contents($file, date('Y-m-d H:i:s') . "\r\n" . var_export($msg, true) . "\r\n\r\n", FILE_APPEND | LOCK_EX);
    }

}