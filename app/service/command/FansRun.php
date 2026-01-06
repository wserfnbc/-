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

namespace app\service\command;

use app\service\service\FansService;
use app\service\service\WechatService;
use think\admin\Command;
use think\console\Input;
use think\console\input\Argument;
use think\console\Output;

/**
 * Class FansRun
 * @package app\service\command
 */
class FansRun extends Command
{
    /**
     * 操作APPID
     * @var string
     */
    protected $appid;

    protected function configure()
    {
        $this->setName('xsync:fansall')->setDescription('Synchronize specified wechat user data');
        $this->addArgument('appid', Argument::OPTIONAL, 'APPID');
        $this->addArgument('split', Argument::OPTIONAL, 'Terminator');
    }

    /**
     * @param Input $input
     * @param Output $output
     * @throws \WeChat\Exceptions\InvalidResponseException
     * @throws \WeChat\Exceptions\LocalCacheException
     * @throws \think\Exception
     * @throws \think\admin\Exception
     * @throws \think\db\exception\DataNotFoundException
     * @throws \think\db\exception\DbException
     * @throws \think\db\exception\ModelNotFoundException
     */
    protected function execute(Input $input, Output $output)
    {
        set_time_limit(0);
        $this->appid = $input->getArgument('appid');
        if (empty($this->appid)) {
            $this->output->error('公众号APPID参数不能为空！');
            throw new \think\Exception('公众号APPID参数不能为空！');
        }
        $wechat = WechatService::WeChatUser($this->appid);
        // 获取远程粉丝
        [$next, $done] = ['', 0];
        $this->output->comment('--> Start to synchronize wechat user data');
        while (!is_null($next) && is_array($result = $wechat->getUserList($next)) && !empty($result['data']['openid'])) {
            foreach (array_chunk($result['data']['openid'], 100) as $chunk) {
                if (is_array($list = $wechat->getBatchUserInfo($chunk)) && !empty($list['user_info_list'])) {
                    foreach ($list['user_info_list'] as $user) {
                        $string = str_pad(++$done, strlen($result['total']), '0', STR_PAD_LEFT);
                        $message = "({$string}/{$result['total']}) {$user['openid']} {$user['nickname']}";
                        $this->setQueueProgress($message, $done * 100 / $result['total']);
                        FansService::instance()->set($user, $this->appid);
                    }
                }
            }
            $next = $result['total'] > $done ? $result['next_openid'] : null;
        }
        $this->output->comment('--> Wechat user data synchronization completed');
        $this->output->newLine();
        $message = "同步{$done}个用户数据";
        // 同步粉丝黑名单
        [$next, $done] = ['', 0];
        $this->output->comment('--> Start to synchronize wechat blacklist data');
        while (!is_null($next) && is_array($result = $wechat->getBlackList($next)) && !empty($result['data']['openid'])) {
            $done += $result['count'];
            foreach (array_chunk($result['data']['openid'], 100) as $chunk) {
                $where = [['is_black', '=', '0'], ['openid', 'in', $chunk]];
                $this->app->db->name('WechatFans')->where($where)->update(['is_black' => '1']);
            }
            $next = $result['total'] > $done ? $result['next_openid'] : null;
        }
        $this->setQueueProgress("共计同步微信黑名单{$done}人");
        $this->output->comment('--> Wechat blacklist data synchronization completed');
        $this->output->newLine();
        $message .= empty($result['total']) ? '，其中黑名单0人' : "，其中黑名单{$result['total']}人";
        // 同步粉丝标签列表
        $index = 0;
        $this->output->comment('--> Start to synchronize wechat tag data');
        if (is_array($list = WechatService::WeChatTags($this->appid)->getTags()) && !empty($list['tags'])) {
            $count = count($list['tags']);
            foreach ($list['tags'] as &$tag) {
                $tag['appid'] = $this->appid;
                $indexString = str_pad(++$index, strlen($count), '0', STR_PAD_LEFT);
                $this->output->writeln("({$indexString}/{$count}) -> {$tag['name']}");
            }
            $this->app->db->name('WechatFansTags')->where('1=1')->delete();
            $this->app->db->name('WechatFansTags')->insertAll($list['tags']);
        }
        $this->output->comment('--> Wechat tag data synchronization completed');
        $this->output->newLine();
        // 输出执行结果
        $this->setQueueSuccess("{$message}，同步{$index}个标签。");
    }

}