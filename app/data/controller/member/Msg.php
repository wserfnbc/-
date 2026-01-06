<?php

// +----------------------------------------------------------------------
// | ThinkAdmin
// +----------------------------------------------------------------------
// | 版权所有 2014~2019 广州楚才信息科技有限公司 [ http://www.cuci.cc ]
// +----------------------------------------------------------------------
// | 官方网站: http://demo.thinkadmin.top
// +----------------------------------------------------------------------
// | 开源协议 ( https://mit-license.org )
// +----------------------------------------------------------------------
// | gitee 代码仓库：https://gitee.com/zoujingli/ThinkAdmin
// | github 代码仓库：https://github.com/zoujingli/ThinkAdmin
// +----------------------------------------------------------------------
namespace app\data\controller\member;

use think\admin\Controller;
use app\data\model\DataMsg;


/**
 * 会员短消息管理
 * Class Member
 * @package app\store\controller
 */
class Msg extends Controller
{

    /**
     * 会员短消息管理
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
        $this->title = '短消息管理';

        $query = DataMsg::mQuery()->alias("m")->field("m.*,r.username,r.nickname")
            ->join("data_user r","r.id = m.receiver_id","LEFT")
            ->like('r.username#username,r.nickname#nickname,m.title#title')->where(['m.is_deleted'=>0])->equal('m.status');
        $query->dateBetween('m.create_at')->order('m.id desc')->page();

    }

    /**
     * 修改短消息管理
     * @auth true
     */
    public function state()
    {
        DataMsg::mSave($this->_vali([
            'status.in:0,1'  => '状态值范围异常！',
            'status.require' => '状态值不能为空！',
        ]));
    }

    /**
     * 查看短消息内容
     * @auth true
     */
    public function content_info()
    {
        $id = $_GET['id'];
        $thisInfo = $this->app->db->name("DataMsg")->alias("m")->field("m.*,r.username,r.nickname")
            ->join("data_user r","r.id = m.receiver_id","LEFT")
            ->where(['m.id'=>$id])->find();
        $this->info = $thisInfo;

        $this->fetch();
    }

    /**
     * 删除短消息管理
     * @auth true
     * @throws \think\Exception
     * @throws \think\exception\PDOException
     */
    public function remove()
    {
        DataMsg::mDelete();
    }

}
