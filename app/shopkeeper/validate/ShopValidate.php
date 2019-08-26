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

class ShopValidate extends Validate
{
    protected $rule = [
        'name'  => 'require|unique:shop,name',
        'link'  => 'require',
        'trademanager'  => 'require',
    ];

    protected $message = [
        'name.require' => '店铺名不能为空',
        'name.unique' => '店铺名已存在',
        'link.require' => '链接不能为空',
        'trademanager.require' => '旺旺号不能为空',
    ];

}