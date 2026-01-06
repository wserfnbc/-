<?php
namespace app\admin\controller;

use think\admin\Controller;

/**
 * 邮箱管理
 * Class GoodsCate
 * @package app\store\controller
 */
class Mail extends Controller
{
    /**
     * 绑定数据表
     * @var string
     */
    protected $table = 'SystemMailConfig';

    /**
     * 发送邮件设置
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

    /**
     * 发送测试邮件
     */
    public function test_send()
    {
        $toMail = $_POST['mail'];

        //显示网站的配置
        $sys = $this->app->db->name('SystemConfig')->where(['type'=>'base'])->column('name,value');
        $db = [];
        foreach ($sys AS $key => $val){
            $db[$val['name']] = $val['value'];
        }
        $name = $db['site_name'];                         //网站名称
        $title = "来自".$name."的测试邮件";                         //网站名称

        $return = \app\index\controller\Index::send_mail($toMail,$name,$title);     //发送邮件并返回结果
        if($return['code'] == 0){
            $this->error($return['info']);
        }else{
            $this->success($return['info']);
        }
    }


}
