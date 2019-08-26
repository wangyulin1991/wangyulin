<?php
// +----------------------------------------------------------------------
// | ThinkCMF [ WE CAN DO IT MORE SIMPLE ]
// +----------------------------------------------------------------------
// | Copyright (c) 2013-2018 http://www.thinkcmf.com All rights reserved.
// +----------------------------------------------------------------------
// | Licensed ( http://www.apache.org/licenses/LICENSE-2.0 )
// +---------------------------------------------------------------------
// | Author: Dean <zxxjjforever@163.com>
// +----------------------------------------------------------------------
namespace cmf\controller;

use think\Db;

class ShopkeeperBaseController extends HomeBaseController
{
    protected $user;
    protected $user_id;
    protected $shopkeeper_id;
    public function initialize()
    {
        parent::initialize();
        $this->checkShopkeeperLogin();
        $this->user_id = cmf_get_current_user_id();
        $this->user = Db::name('user')->where(['id'=>$this->user_id,'user_type'=>2])->find();
        $this->shopkeeper_id = Db::name('shopkeeper')->where(['user_id'=>$this->user_id])->value('id', 0);
    }

    //导出excel
    public function phpExcelList($field, $list, $title='文件')
    {
        vendor('phpExcel.PHPExcel');
        $objPHPExcel = new \PHPExcel();
        $objWriter = new \PHPExcel_Writer_Excel5($objPHPExcel); //设置保存版本格式
        foreach ($list as $key => $value) {
            foreach ($field as $k => $v) {
                if ($key == 0) {
                    $objPHPExcel->getActiveSheet()->setCellValue($k . '1', $v[1]);
                }
                $i = $key + 2; //表格是从2开始的
                $objPHPExcel->getActiveSheet()->setCellValue($k . $i, $value[$v[0]]);
            }

        }
        header("Pragma: public");
        header("Expires: 0");
        header("Cache-Control:must-revalidate, post-check=0, pre-check=0");
        header("Content-Type:application/force-download");
        header("Content-Type:application/vnd.ms-execl");
        header("Content-Type:application/octet-stream");
        header("Content-Type:application/download");;
        header('Content-Disposition:attachment;filename='.$title.'.xls');
        header("Content-Transfer-Encoding:binary");
//        $objWriter->save($title.'.xls');
        $objWriter->save('php://output');
    }
}