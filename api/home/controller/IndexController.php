<?php
// +----------------------------------------------------------------------
// | ThinkCMF [ WE CAN DO IT MORE SIMPLE ]
// +----------------------------------------------------------------------
// | Copyright (c) 2013-2017 http://www.thinkcmf.com All rights reserved.
// +----------------------------------------------------------------------
// | Author: Dean <zxxjjforever@163.com>
// +----------------------------------------------------------------------
namespace api\home\controller;

use api\home\model\AreaModel;
use api\task\model\ProcessModel;
use api\task\model\TaskModel;
use app\admin\model\BrushGuestModel;
use app\admin\model\JobsModel;
use app\admin\model\PlatformModel;
use think\Db;
use think\Validate;
use cmf\controller\RestUserBaseController;

class IndexController extends RestUserBaseController
{
    private $banner_id = 1;
    // api 首页
    public function index()
    {
        //app首页数据
        $data = ['banner_id'=>$this->banner_id,];

        //获取我的任务收入
        $brushGuestModel = new BrushGuestModel();
        $this->userId;
        $brushGuest = $brushGuestModel->where(['user_id'=>$this->userId])->find();
        if ($brushGuest) {
            $data['historical_income'] = $brushGuest['historical_income'];
            $data['balance'] = $brushGuest['balance'];
        } else {
            $data['historical_income'] = '0.00';
            $data['balance'] = '0.00';
        }

        $data['training'] = 'http://' . $_SERVER['HTTP_HOST'] ."/help.html";
        $data['help'] = 'http://' . $_SERVER['HTTP_HOST'] . "/qa.html";

        $this->success("请求成功!",$data);
    }

    public function process()
    {
        $platformModel = new PlatformModel();
        $platforms = $platformModel->where('status=1')->order('sort asc')->select()->toArray();

        $processModel = new ProcessModel();
        $processes = $processModel->where('status=1')->select()->toArray();

        foreach ($platforms as $key => $platform)
        {
            $platforms[$key]['process']=[];
            foreach ($processes as $process){
                if ($process['platform_id']==$platform['id']) {
                    $process['logo'] = cmf_get_image_preview_url($process['logo']);
                    $platforms[$key]['process'][] = $process;
                }
            }
            $platforms[$key]['logo'] = cmf_get_image_preview_url($platform['logo']);
        }
        $this->success('获取成功',$platforms);
    }

    public function jobs()
    {
        $jobsModel = new JobsModel();
        $jobs = $jobsModel->where('status = 1')->select()->toArray();
        $this->success('获取成功', $jobs);
    }

    public function area()
    {
        $result = cache('area_list_cache');
        if (!$result) {
            $areaModel = new AreaModel();
            $result = $areaModel->where('level=1')->select()->toArray();
            $c_result = $areaModel->where('level=2')->select()->toArray();
            $a_result = $areaModel->where('level=3')->select()->toArray();
            $area = [];
            foreach ($a_result as $key=>$value)
            {
                $area[$value['parentId']][]=$value;
            }

            $city = [];
            foreach ($c_result as $key=>$value){
                if (isset($area[$value['id']])) {
                    $value['area'] = $area[$value['id']];
                } else {
                    $value['area'] = [];
                }
                $city[$value['parentId']][] = $value;
            }

            foreach ($result as $key=>$value){
                if (isset($city[$value['id']])) {
                    $result[$key]['city'] = $city[$value['id']];
                } else {
                    $result[$key]['city'] = [];
                }
            }
            cache('area_list_cache',json_encode($result, JSON_UNESCAPED_UNICODE));
        } else {
            $result = json_decode($result,true);
        }
        
        $this->success('获取成功', $result);
    }

    public function get_levels(){
        return [
            'jd_level'=>get_jd_credit_level(),
            'tb_level'=>get_tb_credit_level()
        ];
    }
}
