<?php
namespace app\data\controller\poster;

use think\admin\Controller;

/**
 * 广告位管理
 * Class GoodsCate
 * @package app\store\controller
 */
class Position extends Controller
{
    /**
     * 绑定数据表
     * @var string
     */
    protected $table = 'DataAdPosition';

    /**
     * 广告位管理
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
        $this->title = '广告位管理';
        $query = $this->_query($this->table)->like('title')->equal('status');
        $query->where(['is_deleted' => '0'])->order('sort asc,id asc')->page(false);
    }

    /**
     * 列表数据处理
     * @param array $data
     * @throws \Exception
     */
    protected function _index_page_filter(&$data)
    {

    }

    /**
     * 添加广告位
     * @auth true
     * @throws \think\Exception
     * @throws \think\db\exception\DataNotFoundException
     * @throws \think\db\exception\ModelNotFoundException
     * @throws \think\exception\DbException
     * @throws \think\exception\PDOException
     */
    public function add()
    {
        $this->title = '添加广告位';

        $this->_form($this->table, 'form');
    }

    /**
     * 编辑广告位
     * @auth true
     * @throws \think\Exception
     * @throws \think\db\exception\DataNotFoundException
     * @throws \think\db\exception\ModelNotFoundException
     * @throws \think\exception\DbException
     * @throws \think\exception\PDOException
     */
    public function edit()
    {
        $this->title = '编辑广告位';

        $this->_form($this->table, 'form');
    }

    /**
     * 禁用广告位
     * @auth true
     * @throws \think\Exception
     * @throws \think\exception\PDOException
     */
    public function forbid()
    {
        $this->_save($this->table, ['status' => '0']);
    }

    /**
     * 启用广告位
     * @auth true
     * @throws \think\Exception
     * @throws \think\exception\PDOException
     */
    public function resume()
    {
        $this->_save($this->table, ['status' => '1']);
    }

    /**
     * 删除广告位
     * @auth true
     * @throws \think\Exception
     * @throws \think\exception\PDOException
     */
    public function remove()
    {
        $this->_delete($this->table);
    }


}
