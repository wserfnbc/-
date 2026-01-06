<?php
namespace app\data\controller\article;

use think\admin\Controller;
use think\facade\Db;

/**
 * 单页图文管理
 * Class Goods
 * @package app\store\controller
 */
class Page extends Controller
{
    /**
     * 设置单页图文信息
     * @auth true
     * @throws \think\Exception
     * @throws \think\db\exception\DataNotFoundException
     * @throws \think\db\exception\ModelNotFoundException
     * @throws \think\exception\DbException
     * @throws \think\exception\PDOException
     */
    public function show()
    {
        $this->title = '单页图文设置';

        $templateId= $_GET['template_id']??0;
        $tableName = $this->app->db->name('DataTemplate')->where(["id" => $templateId])->value("table_name");        //内容模板获取数据表名

        $cateId = $_GET['get_cat_id']??0;
        $this->cateId = $cateId;      //获得传入的分类ID
        $this->catInfo = $this->app->db->name('DataArticleCate')->where(["id" => $cateId])->find();     //获得分类信息

        $prefix = config("database.connections.mysql.prefix");      //数据表前缀
        $this->vo = $this->app->db->table($prefix.strtolower($tableName))->where(["cat_id" => $cateId])->find();

        //获取自定义字段
        $fieldsList = Db::name("DataTemplateField")->field("field_name,field_other_name,filed_type,filed_setting,filed_default_value")
            ->where(['template_id'=>$templateId, 'is_sys'=>0, 'status'=>1])->order("sort desc,id asc")->select()->toArray();
        foreach ($fieldsList AS $key => $value){
            //处理选项值设置字段
            $filed_setting = explode("\r\n",$value['filed_setting']);   //以换行分割成数组
            foreach ($filed_setting AS $k => $v){
                $fieldsList[$key]["setting"][$k]['setting_name'] = substr($v,0,strrpos($v,"|"));
                $fieldsList[$key]["setting"][$k]['setting_value'] = substr($v,strripos($v,"|")+1);
            }
        }
        $this->fieldsList = $fieldsList;

        $this->_form(strtolower($tableName), 'page');
    }

    /**
     * 表单数据处理
     * @param array $data
     * @throws \think\db\exception\DataNotFoundException
     * @throws \think\db\exception\DbException
     * @throws \think\db\exception\ModelNotFoundException
     */
    protected function _form_filter(array &$data)
    {
        if ($this->request->isGet()) {


        }
    }

    /**
     * 表单结果处理
     * @param boolean $result
     */
    protected function _form_result($result)
    {
        if ($result && $this->request->isPost()) {
            $this->success('数据更新成功！', 'javascript:history.back()');
        }
    }

}
