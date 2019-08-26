<?php
/**
 * Created by billy.
 *
 * Name:
 * Author: billy
 * Date: 2019/2/7
 * Time: 16:51
 */
namespace api\task\model;

use api\common\model\CommonModel;
use think\Db;
class OrderModel extends CommonModel
{
    public function createOrderNumber()
    {
        $orderId = date('YmdHis').substr(implode(NULL, array_map('ord', str_split(substr(uniqid(), 7, 13), 1))), 0, 8);
        return $orderId;
    }
}