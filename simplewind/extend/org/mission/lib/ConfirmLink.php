<?php
namespace org\mission\lib;

use org\mission\core\Step;
use think\Db;

class ConfirmLink extends Step
{
    public function execute()
    {
        $order = Db::name('order')->alias('a')
            ->join('task b','a.task_id=b.id')
            ->where(['a.id'=>$this->nowStep['order_id']])
            ->field('a.order_number, b.product_price, b.product_link')
            ->find();
        if (!$order){
            $this->res = ['status'=>false,'msg'=>'未能找到订单'];
            return;
        }
        $kouling = $this->getKouling($this->data['ProductLink']);
        if (empty($kouling)) {
            $this->res = ['status'=>false,'msg'=>'宝贝链接不对1'];
            return;
        }
        $link = parse_url(htmlspecialchars_decode($order['product_link']));
        if (!isset($link['query'])) {
            $this->res = ['status'=>false,'msg'=>'宝贝链接不对2'];
            return;
        }
        parse_str($link['query'], $query_arr);
        if (empty($query_arr['id']) || $query_arr['id'] != $this->getProductId($kouling)) {
            $this->res = ['status'=>false,'msg'=>'宝贝链接不对3'];
            return;
        }
        $this->res = ['status'=>true];
    }

    private function getKouling($tbToken) {
        //$reg  = "#\x{ffe5}([a-zA-Z0-9]{11})\x{ffe5}#isu";
        $reg = "#[\x{0020}-\x{FFFF}]([a-zA-Z0-9]{11})[\x{0020}-\x{FFFF}]#isu";
        if(preg_match ($reg, $tbToken, $m) == 1)
        {
            return $m[0];
        }
        return '';
    }

    private function getProductId($kouling) {
        $url1 ='https://api.open.21ds.cn/apiv1/tpwdtoid?apkey=b0f46b4f-d0c2-f928-9b2e-6649e95c9a25&tpwd='.$kouling;
        $url2 ='https://api.open.21ds.cn/apiv1/jiexitkl?apkey=b0f46b4f-d0c2-f928-9b2e-6649e95c9a25&kouling='.$kouling;
        $res = json_decode(sendCurl($url1));
        if ($res) {
            if ($res->code == 200) {
                return $res->data;
            } else {
                $res = json_decode(sendCurl($url2));
                if ($res->code == 200) {
                    return $this->getItemid($res->data->url);
                }
            }
        }
        return '';
    }

    function getItemid($tbToken) {
        $reg = '^https:\/\/a\.m\.taobao\.com/i(\d+)\.htm\?*^';
        if(preg_match ($reg, $tbToken, $m) == 1)
        {
            return $m[1];
        }
        return '';
    }
}