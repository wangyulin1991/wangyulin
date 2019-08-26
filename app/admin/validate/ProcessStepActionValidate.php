<?php

namespace app\admin\validate;

use think\Validate;

class ProcessStepActionValidate extends Validate
{
    protected $rule = [
        'action_name' => 'require|chsAlpha|length:2,25',
        'action_type' => 'require|length:2,45',
    ];

    protected $message = [
        'action_name.require' => '名称不能为空',
        'action_name.chsAlpha' => '名称只能是中文或英文',
        'action_name.length' => '名称长度为2到25个字',
        'action_type.require' => '请选择类型',
        'action_type.length' => '请选择类型长度错误',
    ];
}