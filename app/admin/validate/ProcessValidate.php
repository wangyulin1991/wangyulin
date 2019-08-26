<?php
/**
 * Created by billy.
 *
 * Name:
 * Author: billy
 * Date: 2019/1/28
 * Time: 21:00
 */

namespace app\admin\validate;

use think\Validate;

class ProcessValidate extends Validate
{
    protected $rule = [
        'name' => 'require',
    ];

    protected $message = [
        'name.require' => '流程名称不能为空',
    ];

    protected $scene = [
        'add' => ['name'],
    ];
}