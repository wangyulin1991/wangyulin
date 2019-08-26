<?php
// +----------------------------------------------------------------------
// | Copyright (c) 2013-2018 http://www.thinkcmf.com All rights reserved.
// +----------------------------------------------------------------------
// | Author: Dean <zxxjjforever@163.com>
// +----------------------------------------------------------------------
// | Licensed ( http://www.apache.org/licenses/LICENSE-2.0 )
// +----------------------------------------------------------------------
namespace api\home\controller;

use api\home\model\AreaModel;
use cmf\controller\RestBaseController;

class AreaController extends RestBaseController
{
    public function getNextLevel(){
        $id = $this->request->param('id',0,'intval');
        $areaModel = new AreaModel();
        $data['area'] = $areaModel->where('parentId = ' . $id)->select()->toArray();
        if ($data['area']  && isset($data['area'][0])) {
            if ($data['area'][0]['level']==2) {
                $data['child'] = $areaModel->where('parentId = ' . $data['area'][0]['id'])->select()->toArray();
            }
            return $this->success('获取成功',$data);
        } else {
            return $this->error('获取失败');
        }
    }

    public function index()
    {
        $areaModel = new AreaModel();
        $province = $areaModel->where('level = 1')->select()->toArray();

        $province_id = $this->request->param('province',0,'intval');
        $city_id = $this->request->param('city',0,'intval');

        if ($province_id > 0) {
            $city = $areaModel->where('parentId = ' . $province_id)->select()->toArray();
        } else if ($province  && isset($province[0])) {
            $city = $areaModel->where('parentId = ' . $province[0]['id'])->select()->toArray();
        } else {
            $city = [];
        }

        if ($city_id > 0) {
            $region = $areaModel->where('parentId = ' . $city_id)->select()->toArray();
        } else if ($city && isset($city[0])) {
            $region = $areaModel->where('parentId = ' . $city[0]['id'])->select()->toArray();
        } else  {
            $region = [];
        }
        return $this->success('获取成功',['province'=>$province, 'city'=>$city, 'region'=>$region]);
    }
}
