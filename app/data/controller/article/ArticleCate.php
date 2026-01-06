<?php
namespace app\data\controller\article;

use app\data\model\DataArticleCate;
use think\admin\Controller;

/**
 * 图文分类管理
 * Class GoodsCate
 * @package app\store\controller
 */
class ArticleCate extends Controller
{
    /**
     * 绑定数据表
     * @var string
     */
    protected $table = 'DataArticleCate';

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
        $this->title = '图文分类列表';
        $query = $this->_query($this->table)->alias("c")->field("c.*,t.template_name,t.file_name,ct.template_name AS content_template_name,ct.file_name AS content_file_name")
                ->join("data_template t", "c.template_id = t.id", "LEFT")
                ->join("data_template ct", "c.content_template_id = ct.id", "LEFT")
                ->like('c.title#title')->equal('c.status#status');
        $query->where(["c.parent_id" => 0])->order('c.sort desc,c.id asc')->page(false);
    }

/**
 * 数据列表处理
 * @param array $data
 * @param int $levels 分类层级，默认4级
 * @throws \think\db\exception\DataNotFoundException
 * @throws \think\db\exception\ModelNotFoundException
 * @throws \think\exception\DbException
 */
protected function _index_page_filter(&$data, $levels = 5)
{
    foreach ($data as $key => &$vo) {
        // 递归获取子分类
        $this->getChildren($data[$key], $levels);
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
 * 批量添加图文分类
 * @auth true
 * @throws \think\Exception
 * @throws \think\db\exception\DataNotFoundException
 * @throws \think\db\exception\ModelNotFoundException
 * @throws \think\exception\DbException
 * @throws \think\exception\PDOException
 */
public function add_batch()
{
    $this->title = '批量添加图文分类';
    $levels = 5; // 默认层级为4级，可根据需要修改
    
    if ($this->request->isGet()) {
        $this->set_parent_id = $_GET['set_parent_id'] ?? 0;
        
        // 获取模板类型
        $templateType = isset($data['is_page']) && $data['is_page'] ? 2 : 0;
        
        // 处理每页显示数量
        if (empty($data['page_num'])) {
            $sys = $this->app->db->name('SystemConfig')->where(['type' => 'base'])->column('name,value');
            $dbConfig = [];
            foreach ($sys as $val) {
                $dbConfig[$val['name']] = $val['value'];
            }
            $data['page_num'] = $dbConfig['page_num'] ?? 10;
        }
        
        // 获取模板列表
        $this->template = $this->app->db->name('DataTemplate')
            ->where(["template_type" => $templateType, "status" => 1])
            ->order("sort desc,id asc")->select();
                
        $this->content_template = $this->app->db->name('DataTemplate')
            ->where(["template_type" => 1, "status" => 1])
            ->order("sort desc,id asc")->select();
        
        // 获取父分类列表，使用可配置层级
        $parent_cat = $this->app->db->name('DataArticleCate')->where(["parent_id" => 0])->select()->toArray();
        foreach ($parent_cat as &$vo) {
            $this->getChildren($vo, $levels);
        }
        
        $this->parent_cat = $parent_cat;
        $this->fetch('form_batch');
    } else {
        $data = $this->request->post();
        
        // 处理单页模板
        if ($data['is_page'] == 1) {
            $data['content_template_id'] = $data['template_id'];
        }
        
        // 处理批量添加
        $titleArr = explode("\r\n", $data['title']);
        unset($data['title']);
        
        // 获取系统配置的每页数量
        $sys = $this->app->db->name('SystemConfig')->where(['type' => 'base'])->column('name,value');
        $dbConfig = [];
        foreach ($sys as $val) {
            $dbConfig[$val['name']] = $val['value'];
        }
        $data['page_num'] = $dbConfig['page_num'] ?? 10;
        
        // 批量插入数据
        foreach ($titleArr as $value) {
            if (!empty(trim($value))) { // 过滤空行
                $data['title'] = $value;
                $this->app->db->name("DataArticleCate")->insert($data);
            }
        }
        
        $this->success("栏目批量添加成功");
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
        $this->title = '添加图文分类';

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
        $this->title = '修改图文分类';

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
        $this->set_parent_id = $_GET['set_parent_id'] ?? 0;

        // 确定模板类型
        $templateType = isset($data['is_page']) && $data['is_page'] ? 2 : 0;

        // 如果每页显示数量为0，则使用系统参数里的设置
        if (empty($data['page_num'])) {
            $sys = $this->app->db->name('SystemConfig')->where(['type' => 'base'])->column('name,value');
            $db = [];
            foreach ($sys as $key => $val) {
                $db[$val['name']] = $val['value'];
            }
            $data['page_num'] = $db['page_num'];
        }

        // 获取模板列表
        $this->template = $this->app->db->name('DataTemplate')
            ->where(["template_type" => $templateType, "status" => 1])
            ->order("sort desc,id asc")
            ->select();                 // 分类模板列表
            
        $this->content_template = $this->app->db->name('DataTemplate')
            ->where(["template_type" => 1, "status" => 1])
            ->order("sort desc,id asc")
            ->select();        // 内容模板列表

        // 使用递归获取无限层级分类
        $this->parent_cat = $this->getCategoryTree(0);

    } else {
        if ($data['is_page'] == 1) {
            $data['content_template_id'] = $data['template_id'];    // 如果是单页，内容模板ID为单页模板ID
            // 单页内容创建逻辑可以在这里完善
        }
    }
}

/**
 * 递归获取分类树结构（无限层级）
 * @param int $parentId 父分类ID
 * @return array 分类树结构
 * @throws \think\db\exception\DataNotFoundException
 * @throws \think\db\exception\DbException
 * @throws \think\db\exception\ModelNotFoundException
 */
private function getCategoryTree($parentId = 0)
{
    // 查询当前父ID下的所有子分类
    $categories = $this->app->db->name("DataArticleCate")->alias("c")
        ->field("c.*,t.template_name,t.file_name,ct.template_name AS content_template_name,ct.file_name AS content_file_name")
        ->join("data_template t", "c.template_id = t.id", "LEFT")
        ->join("data_template ct", "c.content_template_id = ct.id", "LEFT")
        ->where(["c.parent_id" => $parentId])
        ->order('c.sort desc,c.id desc')
        ->select()
        ->toArray();

    // 递归处理每个分类的子分类
    foreach ($categories as &$category) {
        // 获取当前分类的子分类数量
        $category['childCount'] = $this->app->db->name("DataArticleCate")
            ->where(["parent_id" => $category['id']])
            ->count();
            
        // 递归获取子分类
        $category['childList'] = $this->getCategoryTree($category['id']);
    }

    return $categories;
}


    /**
     * 表单结果处理
     * @param boolean $state
     */
    protected function _form_result(bool $state)
    {
        if ($state) {

            $this->success('图文分类设置成功！');
        }
    }

    /**
     * 禁用图文分类
     * @auth true
     * @throws \think\Exception
     * @throws \think\exception\PDOException
     */
    public function forbid()
    {
        $this->_save($this->table, ['status' => '0']);
    }

    /**
     * 启用图文分类
     * @auth true
     * @throws \think\Exception
     * @throws \think\exception\PDOException
     */
    public function resume()
    {
        $this->_save($this->table, ['status' => '1']);
    }

    /**
     * 是否导航显示
     * @auth true
     */
    public function is_nav()
    {
        DataArticleCate::mSave($this->_vali([
            'is_nav.in:0,1'  => '状态值范围异常！',
            'is_nav.require' => '状态值不能为空！',
        ]));
    }

    /**
     * 是否导航显示
     * @auth true
     */
    public function is_best()
    {
        DataArticleCate::mSave($this->_vali([
            'is_best.in:0,1'  => '状态值范围异常！',
            'is_best.require' => '状态值不能为空！',
        ]));
    }

    /**
     * 删除图文分类
     * @auth true
     * @throws \think\Exception
     * @throws \think\exception\PDOException
     */
    public function remove()
    {
        if ($this->request->isPost()) {
            $catId = $_POST['id'];
            //判断是否有下级分类
            $childCatList = $this->getChildCat($catId);
            if ($childCatList['countChild'] > 0) {
                $this->error('请先删除所有下级分类！');
            }

            //判断是否为单页，如果是单页则删除单页内容
            $is_page_catInfo = $this->app->db->name('DataArticleCate')->field("id,is_page,content_template_id")->where(['id'=>$catId])->find();    //分类信息
            if($is_page_catInfo['is_page'] == 1){
                $templateInfo = $this->app->db->name('DataTemplate')->field("table_name")->where(['id'=>$is_page_catInfo['content_template_id']])->find();
                //检查此分类的单页是否存在，如果存在则删除！
                $pageCount = $this->app->db->name($templateInfo['table_name'])->field("id")->where(['cat_id'=>$catId])->count();
                if($pageCount > 0){
                    $this->app->db->name($templateInfo['table_name'])->where(['cat_id'=>$catId])->delete();
                }
            }

            //获得所有内容数据表，判断分类下是否有内容
            $tables = $this->app->db->name("DataTemplate")->where("table_name != '' AND template_type > 0")->column('table_name');
            $count = 0;
            foreach ($tables as $key => $t) {
                $thisCount = $this->app->db->name($t)->where('cat_id', $catId)->count("id");
                $count += $thisCount;
            }
            if (!empty($count)) {
                $this->error('此分类及下级分类有图文内容，无法删除！');
            }
        }

        $this->_delete($this->table);
    }

    /* Ajax获取模板列表 */
    public function get_template()
    {
        $type = $_POST['type'];
        $this->template = $this->app->db->name('DataTemplate')->where(["template_type" => $type, "status" => 1])->order("sort desc,id desc")->select();        //分类模板列表
        echo $this->template;
    }

    /**
     * 获取当前分类下所有图文数量和下级分类数量
     * @auth true
     * @throws \think\Exception
     * @throws \think\exception\PDOException
     */
    public function getChildCat($catId)
    {
        $childCatCount = $this->app->db->name('DataArticleCate')->where(['parent_id'=>$catId])->count();    //下级分类数量
//        $childCatList = $this->app->db->name('DataArticleCate')->field("id,parent_id,title,content_template_id")->where(['parent_id'=>$catId])->select()->toArray();
//        return ['count'=>$childCatCount, 'list'=>$childCatList];
        return ['countChild'=>$childCatCount];
    }

}
