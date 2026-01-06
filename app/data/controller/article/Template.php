<?php
namespace app\data\controller\article;

use think\admin\Controller;

/**
 * 内容模型管理
 * Class GoodsCate
 * @package app\store\controller
 */
class Template extends Controller
{
    /**
     * 绑定数据表
     * @var string
     */
    protected $table = 'DataTemplate';

    /**
     * 图文分类管理
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
        if(!isset($_GET['type']) || (isset($_GET['type']) && empty($_GET['type']))){
            $template_type = 0;
        }else{
            $template_type = $_GET['type'];
        }
        if($template_type == 1){
            $this->title = '内容模型';
        }elseif($template_type == 2){
            $this->title = '单页模型';
        }else{
            $this->title = '栏目模型';
        }
        $this->template_type = $template_type;

        $query = $this->_query($this->table);
        $query->where(['template_type'=>$template_type])->order('sort desc,id desc')->page();
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
        if($this->template_type == 1){
            $fileType = "show_";
        }elseif($this->template_type == 2){
            $fileType = "page_";
        }else{
            $fileType = "list_";
        }
        $files = scandir($_SERVER["DOCUMENT_ROOT"]."/app/index/view/article/");
        $templateFilesList = [];
        // 读取指定目录下的所有文件
        foreach ($files AS $key => $value){
            if(strstr($value, $fileType)){
                $templateFilesList[] = strstr($value, $fileType);
            }
        }
        foreach ($data as $key => &$vo) {
            if(!in_array($vo['file_name'], $templateFilesList)){
                $data[$key]['file_name'] = $vo['file_name']." <b style='color: #ff0000;'>(文件已缺失，请检查！)<b>";
            }
        }

    }

    /**
     * 添加图文分类
     * @auth true
     * @throws \think\Exception
     * @throws \think\db\exception\DataNotFoundException
     * @throws \think\db\exception\ModelNotFoundException
     * @throws \think\exception\DbException
     * @throws \think\exception\PDOException
     */
    public function add()
    {
        $this->title = '添加模型';

        $this->template_type = $_GET['template_type'] ?? 0;
        if($this->template_type == 1){
            $fileType = "show_";
        }elseif($this->template_type == 2){
            $fileType = "page_";
        }else{
            $fileType = "list_";
        }
        $files = scandir($_SERVER["DOCUMENT_ROOT"]."/app/index/view/article/");
        $templateFilesList = [];
        // 读取指定目录下的所有文件
        foreach ($files AS $key => $value){
            if(strstr($value, $fileType)){
                $templateFilesList[] = strstr($value, $fileType);
            }
        }
        $this->find_file_list = $templateFilesList;

        $this->_form($this->table, 'form');
    }

    /**
     * 编辑图文分类
     * @auth true
     * @throws \think\Exception
     * @throws \think\db\exception\DataNotFoundException
     * @throws \think\db\exception\ModelNotFoundException
     * @throws \think\exception\DbException
     * @throws \think\exception\PDOException
     */
    public function edit()
    {
        $this->title = '修改模型';

        $this->template_type = $_GET['template_type'] ?? 0;
        if($this->template_type == 1){
            $fileType = "show_";
        }elseif($this->template_type == 2){
            $fileType = "page_";
        }else{
            $fileType = "list_";
        }
        $files = scandir($_SERVER["DOCUMENT_ROOT"]."/app/index/view/article/");
        $templateFilesList = [];
        // 读取指定目录下的所有文件
        foreach ($files AS $key => $value){
            if(strstr($value, $fileType)){
                $templateFilesList[] = strstr($value, $fileType);
            }
        }
        $this->find_file_list = $templateFilesList;

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

        } else {
            //检测模型类型不是分类时，检查有没有创建数据表
            if($data['template_type'] > 0){
                //当创建模型类型为图文内容类型
                $tableName = substr($data['file_name'],0,strrpos($data['file_name'],"."));  //获得"."之前的字符串
                $typeName = substr($tableName,0,strrpos($tableName,"_"));                   //获得"_"之前的字符串，做为模型类型名称
                $tableName = substr($tableName,strripos($tableName,"_")+1);                 //获得"_"之后的字符串，做为数据表的尾名

                $prefix = config("database.connections.mysql.prefix");      //数据表前缀

                $data['table_name'] = "data_article_".$typeName."_".$tableName;
                $tableName = $prefix."data_article_".$typeName."_".$tableName;      //拼接成完整数据表名称

                $isExistTable = $this->app->db->query("show tables like '".$tableName."'");
                if($isExistTable){
                    //当数据表存在
                }else{
                    //当数据表不存在
//                    dump($isExistTable);die();
                    $createTableSQL = "CREATE TABLE ".$tableName." (".
                                    "id int(15) UNSIGNED NOT NULL AUTO_INCREMENT,".
                                    "cat_id int(15) NULL DEFAULT 0 COMMENT '所属分类ID',".
                                    "title varchar(255) CHARACTER SET utf8 COLLATE utf8_unicode_ci NULL DEFAULT NULL COMMENT '标题',".
                                    "subtitle longtext CHARACTER SET utf8 COLLATE utf8_unicode_ci NULL COMMENT '摘要',".
                                    "content longtext CHARACTER SET utf8 COLLATE utf8_unicode_ci NULL COMMENT '内容',".
                                    "logo varchar(255) CHARACTER SET utf8 COLLATE utf8_unicode_ci NULL DEFAULT NULL COMMENT '封面图',".
                                    "keywords varchar(255) CHARACTER SET utf8 COLLATE utf8_unicode_ci NULL DEFAULT NULL COMMENT '关键字',".
                                    "template_id int(10) NULL DEFAULT 0 COMMENT '模型ID',".
                                    "read_count int(10) NULL DEFAULT 0 COMMENT '浏览量',".
                                    "is_best tinyint(1) NULL DEFAULT 0 COMMENT '首页推荐',".
                                    "force_link varchar(255) CHARACTER SET utf8 COLLATE utf8_unicode_ci NULL DEFAULT NULL COMMENT '强制链接',".
                                    "associated longtext CHARACTER SET utf8 COLLATE utf8_unicode_ci NULL COMMENT '关联文章',".
                                    "status tinyint(1) NULL DEFAULT 1 COMMENT '状态',".
                                    "sort int(15) NULL DEFAULT 0 COMMENT '排序',".
                                    "create_at timestamp NULL DEFAULT CURRENT_TIMESTAMP COMMENT '创建时间',".
                                    "PRIMARY KEY (`id`) USING BTREE) ".
                                    "ENGINE = MyISAM AUTO_INCREMENT = 1 CHARACTER SET = utf8 COLLATE = utf8_unicode_ci ROW_FORMAT = DYNAMIC";
                    $this->app->db->query($createTableSQL);
//                    $this->app->db->name("DataTemplate")->where(["id" => $data['id']])->update(['table_name'=>$tableName]);
                }


            }

        }
    }

    /**
     * 表单结果处理
     * @param boolean $state
     */
    protected function _form_result(bool $state)
    {
        if ($state) {
            $this->success('模型设置成功！');
        }
    }

    /**
     * 禁用模型
     * @auth true
     * @throws \think\Exception
     * @throws \think\exception\PDOException
     */
    public function forbid()
    {
        $this->_save($this->table, ['status' => '0']);
    }

    /**
     * 启用模型
     * @auth true
     * @throws \think\Exception
     * @throws \think\exception\PDOException
     */
    public function resume()
    {
        $this->_save($this->table, ['status' => '1']);
    }

    /**
     * 删除模型
     * @auth true
     * @throws \think\Exception
     * @throws \think\exception\PDOException
     */
    public function remove()
    {
        $id = $_POST['id'];
//        $tableName = $this->app->db->name("DataTemplate")->where(["id" => $id])->value("table_name");
//        $count = $this->app->db->table($tableName)->count();
//        if($count == 0){
//            //删除数据表
//        }
        $this->_delete($this->table);
    }

}
