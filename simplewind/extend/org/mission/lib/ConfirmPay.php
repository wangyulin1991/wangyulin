<?php
namespace org\mission\lib;

use api\task\model\OrderModel;
use org\mission\core\Step;
class ConfirmPay extends Step
{
    public function execute()
    {
        OrderModel::update(['confirm_pay'=>1], ['id'=>$this->nowStep['order_id']]);
    }

}