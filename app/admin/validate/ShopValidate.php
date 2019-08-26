<?php
/**
 * Created by PhpStorm.
 * User: Administrator
 * Date: 2019/1/25
 * Time: 19:42
 */

namespace app\admin\validate;

use think\Validate;
use think\Db;

class ShopValidate extends Validate
{
    protected $rule = [
        'shopkeeper_id'  => 'require',
        'name'  => 'require',
        'link'  => 'require',
        'trademanager'  => 'require',
    ];

    protected $message = [
        'mobile.require' => '商户不能为空',
        'name.require' => '店铺名不能为空',
        'link.require' => '链接不能为空',
        'trademanager.require' => '链接不能为空',
    ];

}