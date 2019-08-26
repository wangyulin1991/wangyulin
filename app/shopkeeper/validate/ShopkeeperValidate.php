<?php
/**
 * Created by PhpStorm.
 * User: Administrator
 * Date: 2019/1/25
 * Time: 19:42
 */

namespace app\shopkeeper\validate;

use think\Validate;
use think\Db;

class ShopkeeperValidate extends Validate
{
    protected $rule = [
        'mobile'  => 'require|is_mobile|is_exist',
    ];

    protected $message = [
        'mobile.require' => '手机号不能为空',
        'mobile.is_mobile' => '手机号无效',
        'mobile.is_exist' => '手机号已经存在',
    ];

    protected $scene = [
        'add'  => ['mobile']
    ];

    public function is_mobile($value){
        $ismobile = preg_match("/^1[3|4|5|7|8|9]\d{9}$/",$value);
        if($ismobile){
            return true;
        }else{
            return '无效的手机号';
        }
    }

    public function is_exist($value) {
        $count = Db::name('user')->where("user_login", $value)->count();
        if ($count) {
            return '手机号已经存在';
        } else {
            return true;
        }
    }

}
