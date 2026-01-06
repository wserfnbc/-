<?php
namespace app\data\controller\article;

use app\data\model\DataArticle;
use think\admin\Controller;
use think\Collection;
use think\facade\Db;

/**
 * 内容管理
 * Class Goods
 * @package app\store\controller
 */
class Article extends Controller
{
    /**
     * 图文分类查看
     * @auth true
     * @menu true
     * @throws \think\Exception
     * @throws \think\db\exception\DataNotFoundException
     * @throws \think\db\exception\ModelNotFoundException
     * @throws \think\exception\DbException
     * @throws \think\exception\PDOException
     */
    public function cate()
    {
        //检测是否已开启栏目权限设置
        $setAdminCategory = $this->app->db->name('SystemConfig')->where(['id'=>'29'])->value('value');
        
        //检测当前管理员是否有设置栏目权限
        $systemUser = $this->app->session->get('user');
        if($systemUser['id'] == 10000 || empty($setAdminCategory)){
            $this->category_ids = 0;      //超级管理员或关闭栏目权限设置时为所有权限栏目
        }else{
            $this->category_ids = explode(",", $systemUser['category_ids']);      //当前管理员的权限栏目ID
        }
        
        $this->title = '内容管理';
        $query = $this->_query("DataArticleCate")->alias("c")->field("c.*,t.template_name,t.file_name,ct.template_name AS content_template_name,ct.file_name AS content_file_name")
            ->join("data_template t", "c.template_id = t.id", "LEFT")
            ->join("data_template ct", "c.content_template_id = ct.id", "LEFT")
            ->like('c.title')->equal('c.status');
        $query->where(["c.parent_id" => 0])->order('c.sort desc,c.id asc')->page(false);
      
    }

    /**
     * 数据列表处理
     * @param array $data
     * @throws \think\db\exception\DataNotFoundException
     * @throws \think\db\exception\ModelNotFoundException
     * @throws \think\exception\DbException
     */
    protected function _cate_page_filter(&$data)
    {
        foreach ($data as $key => &$vo) {
            $data[$key]['childCount'] = $this->app->db->name("DataArticleCate")->where(["parent_id" => $vo['id']])->count();
            $data[$key]['childList'] = $this->app->db->name("DataArticleCate")->alias("c")
                ->field("c.*,t.template_name,t.file_name,ct.template_name AS content_template_name,ct.file_name AS content_file_name")
                ->join("data_template t", "c.template_id = t.id", "LEFT")
                ->join("data_template ct", "c.content_template_id = ct.id", "LEFT")
                ->where(["c.parent_id" => $vo['id']])->order('c.sort desc,c.id asc')->select()->toArray();
            //三级分类
            foreach ($data[$key]['childList'] AS $k => $v){
                $data[$key]['childList'][$k]['childCount'] = $this->app->db->name("DataArticleCate")->where(["parent_id" => $v['id']])->count();
                $data[$key]['childList'][$k]['childList'] = $this->app->db->name("DataArticleCate")->alias("c")
                    ->field("c.*,t.template_name,t.file_name,ct.template_name AS content_template_name,ct.file_name AS content_file_name")
                    ->join("data_template t", "c.template_id = t.id", "LEFT")
                    ->join("data_template ct", "c.content_template_id = ct.id", "LEFT")
                    ->where(["c.parent_id" => $v['id']])->order('c.sort desc,c.id asc')->select()->toArray();
                //四级分类
                foreach ($data[$key]['childList'][$k]['childList'] AS $k4 => $v4){
                    $data[$key]['childList'][$k]['childList'][$k4]['childCount'] = $this->app->db->name("DataArticleCate")->where(["parent_id" => $v4['id']])->count();
                    $data[$key]['childList'][$k]['childList'][$k4]['childList'] = $this->app->db->name("DataArticleCate")->alias("c")
                        ->field("c.*,t.template_name,t.file_name,ct.template_name AS content_template_name,ct.file_name AS content_file_name")
                        ->join("data_template t", "c.template_id = t.id", "LEFT")
                        ->join("data_template ct", "c.content_template_id = ct.id", "LEFT")
                        ->where(["c.parent_id" => $v4['id']])->order('c.sort desc,c.id asc')->select()->toArray();
                }
            }
        }

    }

    /**
     * 内容管理
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
        $this->title = '内容列表';

        $cateId = isset($_GET['get_cat_id'])?$_GET['get_cat_id']:'';
        $this->cateId = $cateId;      //获得从图文分类栏目点击到此图文列表时的分类ID
        $this->templateId = isset($_GET['template_id'])?$_GET['template_id']:'';
        $this->contentTemplateId = $contentTemplateId = isset($_GET['content_template_id'])?$_GET['content_template_id']:'';
        $tableName = $this->app->db->name('DataTemplate')
            ->where(['id' => $contentTemplateId])->value("table_name");
        $this->tableName = $tableName;
        $this->isParentCat = 0;
        if(empty($tableName)){
            $this->isParentCat = 1;
            $this->list = [];
            $this->clist = [];
            return $this->fetch();
        }else{
            $query = $this->_query($tableName)->equal('status,cat_id#cat')->like('title');
            $query->order('sort desc,id desc')->page();
        }

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
        $this->clist = Db::name('DataArticleCate')->where(['status' => '1'])->select()->toArray();

        foreach ($data as &$vo) {
            list($vo['list'], $vo['cate']) = [[], []];

            foreach ($this->clist as $cate) if ($cate['id'] === $vo['cat_id']) $vo['cate'] = $cate;

        }

    }

    /**
     * 添加内容
     * @auth true
     * @throws \think\Exception
     * @throws \think\db\exception\DataNotFoundException
     * @throws \think\db\exception\ModelNotFoundException
     * @throws \think\exception\DbException
     * @throws \think\exception\PDOException
     */
    public function add()
    {
        $this->title = '添加内容';
        $this->isAddMode = '1';

        $contentTemplateId= $_GET['content_template_id']??0;
        $tableName = $this->app->db->name('DataTemplate')->where(["id" => $contentTemplateId])->value("table_name");        //内容模板获取数据表名

        $this->_form($tableName, 'form');
    }

    /**
     * 编辑内容
     * @auth true
     * @throws \think\Exception
     * @throws \think\db\exception\DataNotFoundException
     * @throws \think\db\exception\ModelNotFoundException
     * @throws \think\exception\DbException
     * @throws \think\exception\PDOException
     */
    public function edit()
    {
        $this->title = '编辑图文';
        $this->isAddMode = '0';

        $contentTemplateId= $_GET['content_template_id']??0;
        $tableName = $this->app->db->name('DataTemplate')->where(["id" => $contentTemplateId])->value("table_name");        //内容模板获取数据表名

        $this->_form($tableName, 'form');
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
            $cateId = $_GET['get_cat_id']??0;
            $this->cateId = $cateId;      //获得传入的分类ID

            $this->catInfo = $this->app->db->name('DataArticleCate')->where(["id" => $cateId])->find();

            $this->template = isset($_GET['template'])?$_GET['template']:'default';

            $contentTemplateId= $_GET['content_template_id']??0;
            $this->contentTemplateId = $contentTemplateId;

            //获取自定义字段
            $fieldsList = Db::name("DataTemplateField")->field("field_name,field_other_name,filed_type,filed_setting,filed_default_value,sort")
                ->where(['template_id'=>$contentTemplateId, 'is_sys'=>0, 'status'=>1])->order("sort desc,id asc")->select()->toArray();
            foreach ($fieldsList AS $key => $value){
                //处理选项值设置字段
                $filed_setting = explode("\r\n",$value['filed_setting']);   //以换行分割成数组
                foreach ($filed_setting AS $k => $v){
                    $fieldsList[$key]["setting"][$k]['setting_name'] = substr($v,0,strrpos($v,"|"));
                    $fieldsList[$key]["setting"][$k]['setting_value'] = substr($v,strripos($v,"|")+1);
                }
            }
            $this->fieldsList = $fieldsList;

            //处理配置参数
            if(isset($data['configure_filed'])){
                $configureFiled = json_decode($data['configure_filed'],true);
            }else{
                $configureFiled = [];
            }


            //获得会员等级列表
            $this->configureFiled = $configureFiled;


        }else{

            if(isset($data['configure_name']) && isset($data['configure_option'])){
                foreach ($data['configure_name'] AS $c){
                    if(empty(trim($c))){
                        $this->error('参数名称不允许为空');
                    }
                }
                foreach ($data['configure_option'] AS $o){
                    if(empty(trim($o))){
                        $this->error('参数选项不允许为空');
                    }
                }
                $configureFiled = array_combine($data['configure_name'],$data['configure_option']);     //合并数组
                $data['configure_filed'] = json_encode($configureFiled);
                unset($data['member_rank'],$data['member_price']);

            }

            foreach ($data AS $key => $value){
                //处理多选项值设置字段
                if(is_array($value)){
                    $data[$key] = implode("|", $value); //将键名转成字符串存入字段中
                }
                
                //处理创建时间
                if($key == 'create_at'){
                    if(empty($value)){
                        unset($data[$key]);
                    }
                }
            }

        }
    }

    /**
     * 表单结果处理
     * @param boolean $result
     */
    protected function _form_result($result)
    {
        if ($result && $this->request->isPost()) {

            if(isset($_POST['file_url']) && $_POST['file_url']){
                $ext = substr($_POST['file_url'],strrpos($_POST['file_url'], '.')+1);   //获得上传文件后缀名
                $this->app->db->name($this->table)->where(['id' => $result])->update(["sort"=>$result, 'file_type'=>$ext]);
            }

            $this->success('数据更新成功！', 'javascript:history.back()');
        }
    }

    /**
     * 禁用内容
     * @auth true
     * @throws \think\Exception
     * @throws \think\exception\PDOException
     */
    public function forbid()
    {
        $contentTemplateId = isset($_POST['content_template_id'])?$_POST['content_template_id']:'';
        $tableName = $this->app->db->name('DataTemplate')
            ->where(['id' => $contentTemplateId])->value("table_name");
        $this->tableName = $tableName;

        $this->_save($this->tableName, ['status' => '0']);
    }

    /**
     * 启用内容
     * @auth true
     * @throws \think\Exception
     * @throws \think\exception\PDOException
     */
    public function resume()
    {
        $contentTemplateId = isset($_POST['content_template_id'])?$_POST['content_template_id']:'';
        $tableName = $this->app->db->name('DataTemplate')
            ->where(['id' => $contentTemplateId])->value("table_name");
        $this->tableName = $tableName;

        $this->_save($this->tableName, ['status' => '1']);
    }

    /**
     * 资讯类型图文关闭首页推荐
     * @auth true
     * @throws \think\Exception
     * @throws \think\exception\PDOException
     */
    public function no_recommend()
    {
        $contentTemplateId = isset($_POST['content_template_id'])?$_POST['content_template_id']:'';
        $tableName = $this->app->db->name('DataTemplate')
            ->where(['id' => $contentTemplateId])->value("table_name");
        $this->tableName = $tableName;

        $this->_save($this->tableName, ['is_best' => '0']);
    }

    /**
     * 资讯类型图文开启首页推荐
     * @auth true
     * @throws \think\Exception
     * @throws \think\exception\PDOException
     */
    public function to_recommend()
    {
        $contentTemplateId = isset($_POST['content_template_id'])?$_POST['content_template_id']:'';
        $tableName = $this->app->db->name('DataTemplate')
            ->where(['id' => $contentTemplateId])->value("table_name");
        $this->tableName = $tableName;

        $this->_save($this->tableName,  ['is_best' => '1']);
    }

    /**
     * 删除内容
     * @auth true
     * @throws \think\Exception
     * @throws \think\exception\PDOException
     */
    public function remove()
    {
        $contentTemplateId = isset($_POST['content_template_id'])?$_POST['content_template_id']:'';
        $tableName = $this->app->db->name('DataTemplate')
            ->where(['id' => $contentTemplateId])->value("table_name");
        $this->tableName = $tableName;

        $this->_delete($this->tableName);
    }
    
    /**
     * 批量移动图文
     * @auth true
     * @throws \think\Exception
     * @throws \think\db\exception\DataNotFoundException
     * @throws \think\db\exception\ModelNotFoundException
     * @throws \think\exception\DbException
     * @throws \think\exception\PDOException
     */
    public function move_batch()
    {
        $this->title = '批量添加图文分类';
        
        if ($this->request->isGet()) {
            $data = $this->request->get();
//            dump($data);die();
            $this->ids = $data['id'];
            $this->content_template_id = $data['content_template_id'];
            $this->this_cat = $data['this_cat'];
 $levels = 4;
             // 获取父分类列表，使用可配置层级
               $parent_cat = $this->app->db->name('DataArticleCate')->where(["parent_id" => 0])->select()->toArray();
            foreach ($parent_cat as &$vo) {
                $this->getChildren($vo, $levels); // 传递层级参数4
            }
        
//            dump($parent_cat);die();
            $this->parent_cat = $parent_cat;
            $this->fetch('move_batch');
        } else {
            $data = $this->request->post();

            $tableName = $this->app->db->name('DataTemplate')
                ->where(['id' => $data['content_template_id']])->value("table_name");
            $ids = explode(",",$data['id']);
//            dump($data);die();
            $this->app->db->name($tableName)->whereIn('id',$ids)->update(['cat_id'=>$data['move_id']]);
            $this->success("内容批量移动成功");
        }
        
        
    }
    /**
 * 递归获取子分类
 * @param array $item 分类项
 * @param int $remainingLevels 剩余层级
 */
private function getChildren(&$item, $remainingLevels)
{
    if ($remainingLevels <= 0) {
        return;
    }
    
    // 获取子分类数量
    $item['childCount'] = $this->app->db->name("DataArticleCate")->where(["parent_id" => $item['id']])->count();
    
    // 获取子分类列表
    $item['childList'] = $this->app->db->name("DataArticleCate")->alias("c")
        ->field("c.*,t.template_name,t.file_name,ct.template_name AS content_template_name,ct.file_name AS content_file_name")
        ->join("data_template t", "c.template_id = t.id", "LEFT")
        ->join("data_template ct", "c.content_template_id = ct.id", "LEFT")
        ->where(["c.parent_id" => $item['id']])->order('c.sort desc,c.id asc')->select()->toArray();
    
    // 递归处理下一级分类，剩余层级减1
    $remainingLevels--;
    foreach ($item['childList'] as &$child) {
        $this->getChildren($child, $remainingLevels);
    }
}

    /**
     * 根据模型获得图文列表
     * @auth true
     * @throws \think\Exception
     * @throws \think\exception\PDOException
     */
    public function associated()
    {
        $this->article_id = $_GET['article_id'] ?? 0;
        $this->articleTable = $_GET['articleTable'] ?? '';          //主内容表

        $this->dataTemplate = Db::name('DataTemplate')->where(["template_type" => 1])->order("sort desc,id asc")->select();   //选择关联模型列表

        $articleAssociated = Db::name($this->articleTable)->where(['id' => $this->article_id])->value("associated");          //获得原先的关联信息

        $contentTemplateId = $_GET['template_id'] ?? 0;         //搜索关联里的内容模型选择
        if(empty($contentTemplateId)){
            $this->articleAssociated = json_decode($articleAssociated,true);
            if(!empty($this->articleAssociated['tableName'])){
                $this->tableName = $this->articleAssociated['tableName'];
                $this->ids = explode(",",$this->articleAssociated['id']);

            }else{

                $this->tableName = $this->app->db->name('DataTemplate')
                    ->where(['id' => $this->dataTemplate[0]['id']])->value("table_name");
                $this->ids = [];
            }

            $query = $this->_query($this->tableName)->like('title');
            $query->order('sort desc,id desc')->page(false);
        }else{
            $this->articleAssociated = json_decode($articleAssociated,true);
            if(!empty($this->articleAssociated['tableName'])){
                $this->ids = explode(",",$this->articleAssociated['id']);
            }else{
                $this->ids = [];
            }

            $this->tableName = $this->app->db->name('DataTemplate')
                ->where(['id' => $contentTemplateId])->value("table_name");

            $query = $this->_query($this->tableName)->like('title');
            $query->order('sort desc,id desc')->page(false);
        }


    }

    /**
     * 数据列表处理
     * @param array $data
     * @throws \think\db\exception\DataNotFoundException
     * @throws \think\db\exception\ModelNotFoundException
     * @throws \think\exception\DbException
     */
    protected function _associated_page_filter(&$data)
    {
        $this->clist = Db::name('DataArticleCate')->where(['status' => '1'])->select()->toArray();

        foreach ($data as &$vo) {
            list($vo['list'], $vo['cate']) = [[], []];

            foreach ($this->clist as $cate) if ($cate['id'] === $vo['cat_id']) $vo['cate'] = $cate;

        }

    }

    /**
     * 保存关联内容
     * @auth true
     * @throws \think\Exception
     * @throws \think\exception\PDOException
     */
    public function save_associated()
    {
        $associated['tableName'] = $_POST['tableName'];
        $articleTable = $_POST['articleTable'];
        $associated['id'] = $_POST['id'];

        $articleId = $_POST['article_id'];
        $articleAssociated = Db::name($articleTable)->where(['id' => $articleId])->value("associated");
        if(!empty($articleAssociated)){
            $articleAssociated = json_decode($articleAssociated, true);     //获得原先的关联信息
            $associated['id'] = $articleAssociated['id'].",".$associated['id'];     //原先如果有已关联的内容，也并进去。
        }



        Db::name($articleTable)->where(['id' => $articleId])->update(['associated' => json_encode($associated)]);

        $this->success('数据更新成功！');
    }

    /**
     * 查看关联内容列表
     * @auth true
     * @throws \think\Exception
     * @throws \think\exception\PDOException
     */
    public function associated_list()
    {
        $articleId = $_GET['article_id'] ?? 0;
        $this->article_id = $articleId;
        $articleTable = $_GET['articleTable'] ?? '';
        $this->articleTable = $articleTable;        //当前图文表
        $articleInfo = Db::name($articleTable)->where(["id" => $articleId])->find();   //当前图文信息
        $associated = json_decode($articleInfo['associated'], true);   //已设置的关联内容
        if(isset($articleInfo['associated'])){
            //如果存在已关联内容
            $ids = explode(",", $associated['id']);
            $this->tableName = $associated['tableName'];

            $query = $this->_query($associated['tableName'])->like('title');
            $query->where('id','IN',$ids)->order('sort desc,id desc')->page(false);
        }else{
            $ids = [0];
            $this->tableName = $articleTable;

            $query = $this->_query($articleTable)->like('title');
            $query->where('id','IN',$ids)->order('sort desc,id desc')->page(false);
        }




    }

    /**
     * 数据列表处理
     * @param array $data
     * @throws \think\db\exception\DataNotFoundException
     * @throws \think\db\exception\ModelNotFoundException
     * @throws \think\exception\DbException
     */
    protected function _associated_list_page_filter(&$data)
    {
        $this->clist = Db::name('DataArticleCate')->where(['status' => '1'])->select()->toArray();

        foreach ($data as &$vo) {
            list($vo['list'], $vo['cate']) = [[], []];

            foreach ($this->clist as $cate) if ($cate['id'] === $vo['cat_id']) $vo['cate'] = $cate;

        }

    }

    /**
     * 解除关联内容
     * @auth true
     * @throws \think\Exception
     * @throws \think\exception\PDOException
     */
    public function cancel_associated()
    {
        $associated['tableName'] = $_POST['tableName'];     //关联内容表
        $articleTable = $_POST['articleTable'];             //主内容表
        $cancelId = $_POST['id'];                   //需要解除的关联内容ID
        $cancelId = explode(",", $cancelId);
        $articleId = $_POST['article_id'];                  //主内容ID

        $articleAssociated = Db::name($articleTable)->where(['id' => $articleId])->value("associated");
        $articleAssociated = json_decode($articleAssociated, true);     //获得原先的关联信息
        $oldIds = explode(",", $articleAssociated['id']);
        foreach ($oldIds as $ko=>$vo) {
            foreach($cancelId as $vc){
                if($vo==$vc){
                    unset($oldIds[$ko]);//删除$a数组同值元素
                }
            }
        }
        $associated['id'] = implode(",",$oldIds);
        if(empty($associated['id'])){
            $associated = null;
        }else{
            $associated = json_encode($associated);
        }
        Db::name($articleTable)->where(['id' => $articleId])->update(['associated' => $associated]);

        $this->success('数据更新成功！');
    }

}
