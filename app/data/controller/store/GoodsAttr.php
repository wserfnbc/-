<?php
namespace app\data\controller\store;

use think\admin\Controller;
use think\Collection;
use think\db\exception\DbException;
use think\facade\Db;

/**
 * 商品属性规格细分管理
 * Class GoodsCate
 * @package app\store\controller
 */
class GoodsAttr extends Controller
{
    /**
     * 绑定数据表
     * @var string
     */
    protected $table = 'StoreGoodsAttr';

    /**
     * 商品属性细分管理
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
        $this->title = '商品属性细分管理';
        $this->goods_id = $_GET['goods_id'];

        $query = $this->_query($this->table)->like('attr_value_name')->equal('attr_value_type,status');
        $query->where(['goods_id'=>$_GET['goods_id'],'is_deleted' => '0'])->order('sort asc,id asc')->page();
    }

    /**
     * 列表数据处理
     * @param array $data
     * @throws \Exception
     */
    protected function _index_page_filter(&$data)
    {
        foreach ($data as &$vo) {
            $attr_value = DB::name('StoreAttrValue')->field('id,attr_value_name')
                ->where(['id'=>$vo['attr_value_id'],'status'=>1,'is_deleted'=>0])->order('sort asc,id asc')->find();
            $vo['attr_value_name'] = $attr_value['attr_value_name'];
        }
//        dump($data);die();
    }

    /**
     * 添加商品属性细分
     * @auth true
     * @throws \think\Exception
     * @throws \think\db\exception\DataNotFoundException
     * @throws \think\db\exception\ModelNotFoundException
     * @throws \think\exception\DbException
     * @throws \think\exception\PDOException
     */
    public function add()
    {
        $this->title = '添加商品属性';
        $this->isAddMode = '1';

        $this->_form($this->table, 'form');
    }

    /**
     * 编辑商品属性细分
     * @auth true
     * @throws \think\Exception
     * @throws \think\db\exception\DataNotFoundException
     * @throws \think\db\exception\ModelNotFoundException
     * @throws \think\exception\DbException
     * @throws \think\exception\PDOException
     */
    public function edit()
    {
        $this->title = '编辑商品属性';
        $this->isAddMode = '0';

        $this->_form($this->table, 'form');
    }

    /**
     * 添加商品属性细分表单处理
     * @param array $data
     * @throws \think\db\exception\DataNotFoundException
     * @throws \think\db\exception\ModelNotFoundException
     * @throws \think\exception\DbException
     */
    protected function _form_filter(array &$data)
    {

        if ($this->request->isGet()) {
            $this->goods_id = $_GET['goods_id'];
            //属性分类列表
            $cates = DB::name('StoreAttr')->field('id,attr_name AS name')->where(['status'=>1,'is_deleted'=>0])->order('sort asc,id asc')->select()->toArray();
            foreach ($cates AS $key => $value){
                $cates[$key]['children'] = DB::name('StoreAttrValue')->field('id,attr_value_name AS name,attr_value_type AS type')
                    ->where(['attr_id'=>$value['id'],'status'=>1,'is_deleted'=>0])->order('sort asc,id asc')->select()->toArray();
                foreach ($cates[$key]['children'] AS $k => $v){
                    if($v['type'] == 1){
                        $cates[$key]['children'][$k]['name'] = $v['name']." - 文本类型";
                        unset($cates[$key]['children'][$k]['type']);
                    }
                }
//                dump($cates[$key]['children']);
            }
//            die();
            $this->cates = json_encode($cates);
        } else {

            $data = json_decode($data['data'],true);
            foreach ($data AS $key => $val){
                if($val['is_addMode'] == 1){
                    $isAddMode = $val['is_addMode'];
                    unset($data[$key]['is_addMode']);   //释放数组中的is_addMode对应的键名键值
                }else{
                    $isAddMode = $val['is_addMode'];
                    unset($data[$key]['is_addMode']);   //释放数组中的is_addMode对应的键名键值
                    $goods_attr_value_id = $data[0]['goods_attr_value_id'];
                    unset($data[$key]['goods_attr_value_id']);  //释放数组中的goods_attr_value_id对应的键名键值
                    $goods_id = $data[0]['goods_id'];
                    unset($data[$key]['goods_id']); //释放数组中的goods_id对应的键名键值
                }
            }

            if($isAddMode == 1){
                $result = DB::name($this->table)->insertAll($data);
                if(empty($result)){
                    $this->error('商品属性数据保存失败！');
                }else{
                    $this->success('商品属性数据保存成功！', 'javascript:history.back()');
                }
            }else{

                $this->app->db->name($this->table)->where(["id" => $goods_attr_value_id, "goods_id" => $goods_id])->update($data[0]);
                $this->success('商品属性数据保存成功！', 'javascript:history.back()');
            }
        }
    }


    /**
     * 禁用商品属性细分
     * @auth true
     * @throws \think\Exception
     * @throws \think\exception\PDOException
     */
    public function forbid()
    {
        $this->_save($this->table, ['status' => '0']);
    }

    /**
     * 启用商品属性细分
     * @auth true
     * @throws \think\Exception
     * @throws \think\exception\PDOException
     */
    public function resume()
    {
        $this->_save($this->table, ['status' => '1']);
    }

    /**
     * 删除商品属性细分
     * @auth true
     * @throws \think\Exception
     * @throws \think\exception\PDOException
     */
    public function remove()
    {
        $this->_delete($this->table);
    }


}
