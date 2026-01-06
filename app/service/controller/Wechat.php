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
 * 公众号授权管理
 * Class Wechat
 * @package app\service\controller
 */
class Wechat extends Controller
{
    /**
     * 绑定数据表
     * @var string
     */
    protected $table = 'WechatConfig';

    /**
     * 公众号授权管理
     * @auth true
     * @menu true
     * @throws \think\db\exception\DataNotFoundException
     * @throws \think\db\exception\DbException
     * @throws \think\db\exception\ModelNotFoundException
     */
    public function index()
    {
        $this->title = '公众号授权管理';
        $query = $this->_query($this->table);
        // 列表搜索筛选
        $query->like('authorizer_appid,nick_name,principal_name');
        $query->equal('service_type,service_verify')->timeBetween('auth_time#create_at');
        // 数据类型筛选
        $this->type = $this->request->get('type', 'all');
        $this->type === 'all' ? $query->where(['status' => '1']) : $query->where(['status' => '0']);
        // 列表分页处理
        $query->where(['is_deleted' => '0'])->order('auth_time desc')->page();
    }

    /**
     * 列表数据处理
     * @param array $data
     */
    protected function _page_filter(&$data)
    {
        $times = [date('YmdHi', strtotime('-1 minute')), date('YmdHi', strtotime('-2 minute')), date('YmdHi', strtotime('-3 minute'))];
        foreach ($data as &$vo) {
            $vo['times'] = 0;
            foreach ($times as $time) {
                $vo['times'] += $this->app->cache->get("{$vo['authorizer_appid']}_{$time}", 0);
            }
            $vo['times'] = ceil($vo['times'] / 3);
        }
    }

    /**
     * 修改公众号状态
     * @auth true
     * @throws \think\db\exception\DbException
     */
    public function state()
    {
        $this->_applyFormToken();
        $this->_save($this->table, ['status' => input('status')]);
    }

    /**
     * 同步公众号授权信息
     * @auth true
     */
    public function sync()
    {
        try {
            $appid = $this->request->post('appid');
            $where = ['authorizer_appid' => $appid, 'is_deleted' => '0'];
            $author = $this->app->db->name($this->table)->where($where)->find();
            if (empty($author)) $this->error('无效的授权公众号，请重新绑定授权！');
            $info = WechatService::WeOpenService()->getAuthorizerInfo($appid);
            $data = WechatService::buildAuthData(array_merge($info, ['authorizer_appid' => $appid]));
            $where = ['authorizer_appid' => $data['authorizer_appid']];
            $appkey = $this->app->db->name($this->table)->where($where)->value('appkey');
            if (empty($appkey)) $data['appkey'] = md5(uniqid('', true));
            if (data_save($this->table, $data, 'authorizer_appid')) {
                $this->success('更新公众号授权成功！', '');
            }
        } catch (HttpResponseException $exception) {
            throw $exception;
        } catch (\Exception $e) {
            $this->error("获取授权信息失败，请稍候再试！<br>{$e->getMessage()}");
        }
    }

    /**
     * 同步所有授权公众号
     * @auth true
     */
    public function syncall()
    {
        $this->_queue("同步所有授权公众号数据", 'xsync:wechat', 0, [], 0);
    }

    /**
     * 重置公众号调用次数
     * @auth true
     */
    public function clear_quota()
    {
        try {
            $appid = $this->request->post('appid');
            $result = WechatService::WeChatLimit($appid)->clearQuota();
            if (empty($result['errcode']) && $result['errmsg'] === 'ok') {
                $this->success('接口调用次数清零成功！');
            } elseif (isset($result['errmsg'])) {
                $this->error("接口调用次数清零失败！<br>{$result['errmsg']}");
            } else {
                $this->error('接口调用次数清零失败，请稍候再试！');
            }
        } catch (HttpResponseException $exception) {
            throw $exception;
        } catch (\Exception $exception) {
            $this->error("接口调用次数清零失败！<br>{$exception->getMessage()}");
        }
    }
}