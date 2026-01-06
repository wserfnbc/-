<?php
namespace app\index\controller;

use think\admin\Controller;
use think\facade\Db;

/**
 * //自定义标签/函数库
 * @package app\index\controller
 */
class Customshop extends Controller
{
    /**
     * 获得指定商品分类信息
     * @param string $catId 栏目ID
     */
    public function goodsCat($catId)
    {
        return $this->app->db->name('StoreGoodsCate')
            ->where(['id'=>$catId,'status'=>1,'is_deleted'=>0])
            ->find();
    }
    
    /**
     * 获得指定商品分类的所有次级分类，不要在底层分类里使用该方法
     * @param string $catId 栏目ID
     * @param int $num 数量
     *  @param int $is_hot 0为全部，1为热门
     */
    public function goodsCatChild($catId=0,$num=0,$is_hot=0)
    {
        $whereHot = [];
        if($is_hot == 1){
            $whereHot['is_hot'] = 1;
        }
        
        if($num==0){
            $catList = Db::name('StoreGoodsCate')
                ->where(['parent_id'=>$catId,'status'=>1,'is_deleted'=>0])->where($whereHot)->order("sort desc,id asc")
                ->select()->toArray();
        }else{
            $catList = Db::name('StoreGoodsCate')
                ->where(['parent_id'=>$catId,'status'=>1,'is_deleted'=>0])->where($whereHot)->order("sort desc,id asc")->limit($num)
                ->select()->toArray();
        }
        
        return $catList;
    }
    
    /**
     * 获得指定分类下的商品列表
     * @param string $catId 栏目ID
     * @param string $num 查询条数
     * @param string $pageSize 每页数量
     * @param string $sort 排序方式
     * @param string $is_hot 0为全部，1为热门
     */
    public function goodsList($catId=0, $num=0, $pageSize=0, $sort="DESC",  $is_hot=0)
    {
        $where = [];
        if($is_hot == 1){
            $where['is_hot'] = 1;
        }
        if(!empty($catId)){
            $where['cate_id'] = $catId;
        }
    
        if(!empty($num)){
            //如果限制数量
            $list = DB::name("StoreGoods")->where(["status"=>1])
                ->where($where)
                ->limit($num)
                ->order("sort ".$sort.",id ".$sort)->select()->toArray();
            $page = [];
        }else{
            
            if(empty($pageSize)){
                //如果不限制数量，也不限制每页显示数量，则查询全部
                $list = DB::name("StoreGoods")->where(["status"=>1])
                    ->where($where)
                    ->order("sort ".$sort.",id ".$sort)
                    ->select()->toArray();
                $page = [];
            }else{
                //如果不限制数量，设置每页显示数量，则做分页查询
                $pageNum = $pageSize;
                $list = DB::name("StoreGoods")->where(["status"=>1])
                    ->where($where)
                    ->order("sort ".$sort.",id ".$sort)
                    ->paginate($pageNum,false,['query' => request()->param()])->each(function($item, $key){
                        //循环操作
                        return $item;
                    });
                $page = $list->render();
            }
        }
    
        return ['list'=>$list, 'page'=>$page];
    }
    
    /**
     * 获得指定商品信息
     * @param string $goods_id 商品ID
     */
    public function goodsInfo($goods_id)
    {
        return $this->app->db->name('StoreGoods')
            ->where(['id'=>$goods_id,'status'=>1])
            ->find();
    }

}