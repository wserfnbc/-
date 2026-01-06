<?php
namespace app\data\controller\article;

use think\admin\Controller;
use think\facade\Db;

/**
 * 模板字段管理
 * Class GoodsCate
 * @package app\store\controller
 */
class Field extends Controller
{

    /**
     * 绑定数据表
     * @var string
     */
    protected $table = 'DataTemplateField';

    /**
     * 字段列表管理
     * @auth true
     * @menu true
     * @throws \think\Exception
     * @throws \think\db\exception\DataNotFoundException
     * @throws \think\db\exception\ModelNotFoundException
     * @throws \think\exception\DbException
     * @throws \think\exception\PDOException
     */
    public function index()
    {
        $this->title = '字段列表';

        $this->template_id = $_GET['id'] ?? 0;

        $query = $this->_query($this->table);
        $query->where(["template_id" => $this->template_id])->order('is_sys desc,sort desc,id asc')->page();
    }

    /**
     * 数据列表处理
     * @param array $data
     * @throws \think\db\exception\DataNotFoundException
     * @throws \think\db\exception\ModelNotFoundException
     * @throws \think\exception\DbException
     */
    protected function _index_page_filter(&$data)
    {
        foreach ($data as $key => &$vo) {
            if($vo['filed_type'] == 'text'){
                $data[$key]['filed_type'] = "单行文本";
            }elseif($vo['filed_type'] == 'radio'){
                $data[$key]['filed_type'] = "单选项";
            }elseif($vo['filed_type'] == 'checkbox'){
                $data[$key]['filed_type'] = "多选项";
            }elseif($vo['filed_type'] == 'textarea'){
                $data[$key]['filed_type'] = "多行文本";
            }elseif($vo['filed_type'] == 'file'){
                $data[$key]['filed_type'] = "单文件";
            }elseif($vo['filed_type'] == 'files'){
                $data[$key]['filed_type'] = "多图片";
            } elseif($vo['filed_type'] == 'editor'){
                $data[$key]['filed_type'] = "富文本编辑器";
            }

        }

    }

    /**
     * 添加字段
     * @auth true
     * @throws \think\Exception
     * @throws \think\db\exception\DataNotFoundException
     * @throws \think\db\exception\ModelNotFoundException
     * @throws \think\exception\DbException
     * @throws \think\exception\PDOException
     */
    public function add()
    {
        $this->title = '添加字段';
        $this->isAdd = 1;

        $this->_form($this->table, 'form');
    }

    /**
     * 修改字段
     * @auth true
     * @throws \think\Exception
     * @throws \think\db\exception\DataNotFoundException
     * @throws \think\db\exception\ModelNotFoundException
     * @throws \think\exception\DbException
     * @throws \think\exception\PDOException
     */
    public function edit()
    {
        $this->title = '修改字段';
        $this->isAdd = 0;

        $this->_form($this->table, 'form');
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

            $this->template_id = $_GET['template_id'] ??0;
            $template = $this->app->db->name('DataTemplate')->where(["id" => $this->template_id])->find();
            $this->template = $template;

            $prefix = config("database.connections.mysql.prefix");      //数据表前缀
            //获得该模型的数据表信息
            $tableInfo = Db::query('SHOW FULL COLUMNS FROM ' . $prefix.$template['table_name']);
            foreach ($tableInfo AS $key => $value){
                if($value['Field'] == "template_id" || $value['Field'] == "is_best" || $value['Field'] == "status" || $value['Field'] == "sort" || $value['Field'] == "is_deleted" || $value['Field'] == "create_at"){
                    //去除查询结果中模型ID字段
                    unset($tableInfo[$key]);
                }
            }

            //获得模型字段表的信息
            $templateFields = Db::name($this->table)->where(['template_id'=>$this->template_id, 'status'=>1])->column("table_field");

            foreach ($tableInfo AS $key => $value){
                if(!in_array($value['Field'],$templateFields)){
                    //判断模型字段表是否存在模型的数据表字段，如果不存在则更新进模型字段表。用来创建新的模型里的默认系统字段
                    Db::name($this->table)->insert(['template_id' => $template['id'],
                        'table_name' => $template['table_name'],
                        'field_name' => $value['Field'],
                        'table_field' => $value['Field'],
                        'field_other_name' => $value['Comment'],
                        'table_field_comment' => $value['Comment'],
                        'table_field_type' => $value['Type'],
                        'table_field_collation' => $value['Collation'],
                        'table_field_null' => $value['Null'],
                        'table_field_default' => $value['Default'],
                        'is_sys' => 1,
                        'sort' => 50,
                    ]);

                }
            }

        } else {
            $data['table_field'] = $data['field_name'];
            $data['table_field_comment'] = $data['field_other_name'];
            $data['sort'] = 50;
            $data['filed_range'] = '255';       //字段长度范围
            $data['filed_setting'] = $data['filed_setting'] ?? "NULL";
            $data['table_field_null'] = "YES";
            $data['table_field_default'] = $data['filed_default_value'] ?? "";
            //根据字段类型进行调整设置
            if($data['filed_type'] == 'text'){
                //单行文本
                $data['table_field_type'] = 'VARCHAR('.$data["filed_range"].')';
                $data['table_field_collation'] = 'utf8_unicode_ci';
                $tableQuery = $data['table_field']." ".$data['table_field_type']." CHARACTER SET utf8 COLLATE ".$data['table_field_collation']." NULL DEFAULT NULL COMMENT '".$data['table_field_comment']."'";
            }elseif($data['filed_type'] == 'textarea'){
                //多行文本
                $data['table_field_type'] = 'LONGTEXT';
                $data['table_field_collation'] = 'utf8_unicode_ci';
                $tableQuery = $data['table_field']." ".$data['table_field_type']." CHARACTER SET utf8 COLLATE ".$data['table_field_collation']." NULL COMMENT '".$data['table_field_comment']."'";
            }elseif($data['filed_type'] == 'radio'){
                //单选
                $data['table_field_type'] = 'LONGTEXT';
                $data['table_field_collation'] = 'utf8_unicode_ci';     //字符规则(默认utf8_unicode_ci)
                $tableQuery = $data['table_field']." ".$data['table_field_type']." CHARACTER SET utf8 COLLATE ".$data['table_field_collation']." NULL COMMENT '".$data['table_field_comment']."'";
            }elseif($data['filed_type'] == 'checkbox'){
                //多选
                $data['table_field_type'] = 'LONGTEXT';
                $data['table_field_collation'] = 'utf8_unicode_ci';     //字符规则(默认utf8_unicode_ci)
                $tableQuery = $data['table_field']." ".$data['table_field_type']." CHARACTER SET utf8 COLLATE ".$data['table_field_collation']." NULL COMMENT '".$data['table_field_comment']."'";
            }elseif($data['filed_type'] == 'file'){
                //多选
                $data['table_field_type'] = 'LONGTEXT';
                $data['table_field_collation'] = 'utf8_unicode_ci';     //字符规则(默认utf8_unicode_ci)
                $tableQuery = $data['table_field']." ".$data['table_field_type']." CHARACTER SET utf8 COLLATE ".$data['table_field_collation']." NULL COMMENT '".$data['table_field_comment']."'";
            }elseif($data['filed_type'] == 'files'){
                //多选
                $data['table_field_type'] = 'LONGTEXT';
                $data['table_field_collation'] = 'utf8_unicode_ci';     //字符规则(默认utf8_unicode_ci)
                $tableQuery = $data['table_field']." ".$data['table_field_type']." CHARACTER SET utf8 COLLATE ".$data['table_field_collation']." NULL COMMENT '".$data['table_field_comment']."'";
            }elseif($data['filed_type'] == 'editor'){
                //多选
                $data['table_field_type'] = 'LONGTEXT';
                $data['table_field_collation'] = 'utf8_unicode_ci';     //字符规则(默认utf8_unicode_ci)
                $tableQuery = $data['table_field']." ".$data['table_field_type']." CHARACTER SET utf8 COLLATE ".$data['table_field_collation']." NULL COMMENT '".$data['table_field_comment']."'";
            }
            $prefix = config("database.connections.mysql.prefix");      //数据表前缀
            if ($data['isAdd']==0){
                //如果是修改状态，先删除对应数据表的字段
                // Db::query("ALTER TABLE ". $prefix.$data['table_name'] ." DROP ". $data['table_field']);
                
                Db::query("ALTER TABLE ". $prefix.$data['table_name'] ." MODIFY ".$tableQuery); 
            }else{
            
            Db::query("ALTER TABLE ". $prefix.$data['table_name'] ." ADD ".$tableQuery);
             }
            unset($data['isAdd']);

        }
    }

    /**
     * 表单结果处理
     * @param boolean $state
     */
    protected function _form_result(bool $state)
    {
        if ($state) {
            $this->success('字段设置成功！', 'javascript:history.back()');
        }
    }

    /**
     * 禁用字段
     * @auth true
     * @throws \think\Exception
     * @throws \think\exception\PDOException
     */
    public function forbid()
    {
        $this->_save($this->table, ['status' => '0']);
    }

    /**
     * 启用字段
     * @auth true
     * @throws \think\Exception
     * @throws \think\exception\PDOException
     */
    public function resume()
    {
        $this->_save($this->table, ['status' => '1']);
    }

    /**
     * 删除字段
     * @auth true
     * @throws \think\Exception
     * @throws \think\exception\PDOException
     */
    public function remove()
    {

        $templateId = $_POST['template_id'] ??0;
        $fieldId = $_POST['id'] ??0;
        $prefix = config("database.connections.mysql.prefix");      //数据表前缀
        $tableName = Db::name('DataTemplate')->where(["id" => $templateId])->value("table_name");   //字段所属的数据表
        $fieldName = Db::name($this->table)->where(['id'=>$fieldId, 'status'=>1])->value('field_name');     //获得字段名

        Db::query("ALTER TABLE ".$prefix.$tableName." DROP ".$fieldName);

        $this->_delete($this->table);
    }

}
