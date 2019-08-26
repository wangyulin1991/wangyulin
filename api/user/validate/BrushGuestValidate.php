<?php
/**
 * Created by billy.
 *
 * Name:
 * Author: billy
 * Date: 2019/1/31
 * Time: 14:52
 */

namespace api\user\validate;

use think\Validate;

class BrushGuestValidate extends Validate
{
    protected $regex = [ 'phone' => '/^1[345789]\d{9}$/'];

    protected $rule = [
        'real_name' => 'require|chsAlpha|length:2,25',
        'qq' => 'require|number|length:6,25',
        'cellphone' => 'require|unique:brush_guest|regex:phone',
        'taobao_ww' => 'require',
        'bank_number' => 'require',
        'taobao_tq' => 'require',
        'taobao_level_credit' => 'require',
        'alipay_photo' => 'require',
        //'jd_level_credit' => 'require',
        //'jd_photo' => 'require',
        'id_card_no' => 'require|length:18',
        'id_card_first' => 'require',
        'id_card_second' => 'require',
    ];

    protected $message = [
        'real_name.require' => '真实姓名不能为空',
        'real_name.chsAlpha' => '真实姓名只能是中文或英文',
        'real_name.length' => '真实姓名长度为2到25个字',
        'qq.require' => '请输入qq帐号',
        'qq.number' => '请输入正确的qq帐号',
        'qq.length' => '请输入正确的qq帐号',
        'cellphone.require' => '请输入手机号码',
        'cellphone.unique' => '手机号码已经被使用',
        'cellphone.regex' => '请输入正确的手机号',
        'taobao_ww.require' => '请输入淘宝旺旺号',
        'bank_number.require' => '请输入银行账号',
        'taobao_level.require' => '请上传淘气值截图',
        'taobao_level_credit.require' => '请上传淘宝信誉等级截图',
        'alipay_photo.require' => '请上传支付宝实名截图',
        //'jd_level_credit.require' => '请输入京东信誉等级',
        //'jd_photo.require' => '请上传京东实名截图',
        'id_card_no.require' => '请填写身份证号码',
        'id_card_no.length' => '身份证号码格式错误',
        'id_card_first.require' => '请上传身份证正面',
        'id_card_second.require' => '请上传身份证背面',
    ];

    protected $scene = [
        'edit' => ['real_name','taobao_ww','bank_number','taobao_tq','qq','taobao_level_credit','id_card_no','id_card_first','id_card_second'],
    ];
}