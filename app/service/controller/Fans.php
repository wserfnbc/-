<?php

// +----------------------------------------------------------------------
// | ThinkOpen
// +----------------------------------------------------------------------
// | 版权所有 2014~2020 广州楚才信息科技有限公司 [ http://www.cuci.cc ]
// +----------------------------------------------------------------------
// | 官方网站: https://gitee.com/zoujingli/ThinkOpen
// +----------------------------------------------------------------------
// | gitee 代码仓库：https://gitee.com/zoujingli/ThinkOpen
// | github 代码仓库：https://github.com/zoujingli/ThinkOpen
// +----------------------------------------------------------------------

namespace app\service\controller;

use app\service\service\WechatService;
use think\admin\Controller;
use think\exception\HttpResponseException;

/**
 * 微信粉丝管理
 * Class Fans
 * @package app\service\controller
 */
class Fans extends Controller
{
    /**
     * 绑定数据表
     * @var string
     */
    protected $table = 'WechatFans';

    /**
     * 初始化函数
     */
    protected function initialize()
    {
        $this->appid = input('appid', $this->app->session->get('current_appid'));
        if (empty($this->appid)) {
            $this->where = ['status' => '1', 'service_type' => '服务号', 'is_deleted' => '0', 'service_verify' => '已认证'];
            $this->appid = $this->app->db->name('WechatConfig')->where($this->where)->value('authorizer_appid');
        }
        if (empty($this->appid)) {
            $this->fetch('/not-auth');
        } else {
            $this->app->session->set('current_appid', $this->appid);
        }
        if ($this->request->isGet()) {
            $this->where = ['status' => '1', 'service_type' => '服务号', 'is_deleted' => '0', 'service_verify' => '已认证'];
            $this->wechats = $this->app->db->name('WechatConfig')->where($this->where)->order('id desc')->column('user_nickname', 'authorizer_appid');
        }
    }

    /**
     * 微信粉丝管理
     * @auth true
     * @menu true
     */
    public function index()
    {
        $this->title = '微信粉丝管理';
        $query = $this->_query($this->table)->like('nickname')->equal('subscribe,is_black');
        $query->dateBetween('subscribe_at')->where(['appid' => $this->appid])->order('subscribe_time desc')->page();
    }

    /**
     * 列表数据处理
     * @param array $data
     */
    protected function _index_page_filter(array &$data)
    {
        $tags = $this->app->db->name('WechatFansTags')->column('name', 'id');
        foreach ($data as &$user) {
            $user['tags'] = [];
            foreach (explode(',', $user['tagid_list']) as $tagid) {
                if (isset($tags[$tagid])) $user['tags'][] = $tags[$tagid];
            }
        }
    }

    /**
     * 删除粉丝信息
     * @auth true
     * @throws \think\db\exception\DbException
     */
    public function remove()
    {
        $this->_applyFormToken();
        $this->_delete($this->table);
    }

    /**
     * 用户拉入黑名单
     * @auth true
     */
    public function black_add()
    {
        try {
            $this->_applyFormToken();
            foreach (array_chunk(explode(',', $this->request->post('openid')), 20) as $openids) {
                WechatService::WeChatUser($this->appid)->batchBlackList($openids);
                $where = [['appid', '=', $this->appid], ['openid', 'in', $openids]];
                $this->app->db->name('WechatFans')->where($where)->update(['is_black' => '1']);
            }
            $this->success('拉黑粉丝信息成功！');
        } catch (HttpResponseException $exception) {
            throw  $exception;
        } catch (\Exception $e) {
            $this->error("拉黑粉丝信息失败，请稍候再试！{$e->getMessage()}");
        }
    }

    /**
     * 用户移出黑名单
     * @auth true
     */
    public function black_del()
    {
        try {
            $this->_applyFormToken();
            foreach (array_chunk(explode(',', $this->request->post('openid')), 20) as $openids) {
                WechatService::WeChatUser($this->appid)->batchUnblackList($openids);
                $where = [['appid', '=', $this->appid], ['openid', 'in', $openids]];
                $this->app->db->name('WechatFans')->where($where)->update(['is_black' => '0']);
            }
            $this->success('取消拉黑粉丝信息成功！');
        } catch (HttpResponseException $exception) {
            throw  $exception;
        } catch (\Exception $e) {
            $this->error("取消拉黑粉丝信息失败，请稍候再试！{$e->getMessage()}");
        }
    }

    /**
     * 同步用户数据
     * @auth true
     */
    public function sync()
    {
        $this->_queue("同步公众号 {$this->appid} 粉丝数据", "xsync:fansall {$this->appid} -", 0, [], 0);
    }

}