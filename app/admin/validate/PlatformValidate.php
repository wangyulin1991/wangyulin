<?php

namespace app\admin\validate;

use think\Validate;

class PlatformValidate extends Validate
{
    protected $rule = [
        'platform_name' => 'require|length:2,25',
        'logo' => 'require|length:0,255',
        'link' => 'length:0,255',
    ];

    protected $message = [
        'platform_name.require' => '平台名称不能为空',
        'platform_name.chsAlpha' => '平台名称只能是中文或英文',
        'platform_name.length' => '平台名称长度为2到25个字',
        'logo.require' => '请上传平台图标',
        'logo.length' => '平台图标地址错误',
        'link.length' => '平台链接长度为0到255个字',
    ];
}