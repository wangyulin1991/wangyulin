<?php
/**
 * Created by PhpStorm.
 * User: Admin
 * Date: 2019/1/28 0028
 * Time: 19:09
 */

namespace app\shopkeeper\validate;

use think\Db;
use think\Validate;

class TaskValidate extends Validate
{

    protected $rule = [
        'shop_id' => 'require',
        'process_id' => 'require',
        'task_name' => 'require',
        'keyword' => 'require',
//        'product_img' => 'require',
        'product_link' => 'require|url',
        'product_price' => 'require|number|egt:0',
        //'show_price' => 'require|number|gt:0',
        'task_num' => 'require|integer|egt:1',
        //'commission' => 'require|number|gt:0',
        'deal_num' => 'require|integer|gt:0',
        //'need_chat' => 'require',
        //'start_time' => 'require|date|afterToday',
        //'aging' => 'require|number',
    ];

    protected $message = [
        'process_id.require' => '请选择任务类型',
        'task_name.require' => '请填写任务标题',
        'shop_id.require' => '请选择目标店铺',
        'keyword.require' => '请输入关键字',
        'product_img.require' => '产品主图不能为空',
        'product_link.require' => '宝贝链接不能为空',
        'product_price.require' => '交易价格不能为空',
        'product_price.number' => '交易价格无效的数字',
        'product_price.gt' => '交易价格必须大于0',
//        'show_price.require' => '显示价格不能为空',
//        'show_price.number' => '显示价格无效的数字',
//        'show_price.gt' => '显示价格必须大于0',
        'deal_num.require' => '下单笔数不能为空',
        'deal_num.number' => '下单笔数无效的数字',
        'deal_num.gt' => '下单笔数必须大于0',
        'task_num.require' => '任务发放数量不能为空',
        'task_num.number' => '任务发放数量无效的数字',
        'task_num.egt' => '任务发放数量至少为1',
        /*'need_chat.require' => '请选择是否聊天',*/
        'start_time.require' => '请选择任务开启时间',
        'start_time.date' => '无效的时间格式',
        'start_time.afterToday' => '开始时间必须大于当前时间',
        //'aging.require' => '任务时效不能为空',
        //'aging.number' => '任务时效无效的数字'
    ];

    protected $scene = [
        'first'  => ['process_id','task_name','keyword','shop_id','product_img','product_link','product_price', 'task_num','deal_num']
    ];

    public function afterToday($value) {
        if (strtotime($value) < time()) {
            return '开始时间必须大于当前时间';
        } else {
            return true;
        }
    }
}