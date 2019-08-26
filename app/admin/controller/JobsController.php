<?php

namespace app\admin\controller;

use app\admin\model\JobsModel;
use cmf\controller\AdminBaseController;
use think\Db;
use app\admin\model\AdminMenuModel;

class JobsController extends AdminBaseController
{
    public function index()
    {
        $jobsModel = new JobsModel();
        $jobs = $jobsModel->select()->toArray();
        $this->assign('jobs',$jobs);
        return $this->fetch();
    }

    public function add(){
        if ($this->request->isPost()) {
            $result = $this->validate($this->request->param(), 'Jobs');
            if ($result !== true) {
                $this->error($result);
            } else {
                $data = $this->request->param();
                $jobsModel = new JobsModel();
                $jobsModel->allowField(true)->save($data);
                $this->success("添加成功！", url('Jobs/index'));
            }
        } else {
            return $this->fetch();
        }
    }

    public function edit()
    {
        if ($this->request->isPost()) {
            $result = $this->validate($this->request->param(), 'Jobs');
            if ($result !== true) {
                $this->error($result);
            } else {
                $data = $this->request->param();
                $jobsModel = new JobsModel();
                $jobsModel->allowField(true)->isUpdate(true)->save($data);
                $this->success("编辑成功！", url('Jobs/index'));
            }
        } else {
            $id = $this->request->param('id',0,'intval');
            if ($id>0) {
                $jobsModel = new JobsModel();
                $job = $jobsModel->find($id);
                $this->assign('job',$job);
                return $this->fetch();
            } else {
                $this->error('参数错误');
            }
        }
    }
}