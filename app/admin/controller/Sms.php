<?php
namespace app\admin\controller;

use think\admin\Controller;

/**
 * 邮箱管理
 * Class GoodsCate
 * @package app\store\controller
 */
class Sms extends Controller
{
    /**
     * 绑定数据表
     * @var string
     */
    protected $table = 'SystemSmsSetting';

    /**
     * 短信接口设置
     * @auth true
     * @menu true
     * @throws \think\Exception
     * @throws \think\db\exception\DataNotFoundException
     * @throws \think\db\exception\ModelNotFoundException
     * @throws \think\exception\DbException
     * @throws \think\exception\PDOException
     */
    public function config()
    {
        $this->_form($this->table, 'form');
    }


}
