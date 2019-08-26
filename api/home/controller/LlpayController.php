<?php
/**
 * Created by PhpStorm.
 * User: Admin
 * Date: 2019/2/1 0001
 * Time: 13:35
 */

namespace api\home\controller;
use cmf\controller\BaseController;
use think\Db;

class LlpayController extends BaseController
{
    //异步通知
    function notifyurl(){
        echo json_encode(["ret_code"=>'0000','ret_msg'=>'交易成功'],JSON_UNESCAPED_UNICODE);
        exit;
    }
}