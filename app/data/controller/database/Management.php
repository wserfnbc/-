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

namespace app\data\controller\database;

use think\admin\Controller;
use think\facade\Db;
use think\facade\View;
use phpspirit\databackup\BackupFactory;
use phpspirit\databackup\RecoveryFactory;

/**
 * 数据库管理
 * Class GoodsCate
 * @package app\store\controller
 */
class Management extends Controller
{
    /**
     * 数据库表管理
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
        $this->title = '数据库表管理';
        $this->list = Db::query('SHOW TABLE STATUS');

        return $this->fetch();
    }

    /**
     * 备份数据
     * @auth true
     * @throws \think\Exception
     * @throws \think\db\exception\DataNotFoundException
     * @throws \think\db\exception\ModelNotFoundException
     * @throws \think\exception\DbException
     * @throws \think\exception\PDOException
     */
    public function backup()
    {
        if($this->request->isPost()){
            //获得数据库配置
            $getConfig = Db::getConfig();
            $dbConfig['type'] = $getConfig['connections']['mysql']['type'];
            $dbConfig['hostname'] = $getConfig['connections']['mysql']['hostname'];
            $dbConfig['database'] = $getConfig['connections']['mysql']['database'];
            $dbConfig['username'] = $getConfig['connections']['mysql']['username'];
            $dbConfig['password'] = $getConfig['connections']['mysql']['password'];
            $dbConfig['hostport'] = $getConfig['connections']['mysql']['hostport'];

            error_reporting(-1);
            ini_set('display_errors', 1);

            include $_SERVER["DOCUMENT_ROOT"]."/public/tools/buckup/BackupFactory.php";
            include $_SERVER["DOCUMENT_ROOT"]."/public/tools/buckup/IBackup.php";
            include $_SERVER["DOCUMENT_ROOT"]."/public/tools/buckup/mysql/backup.php";

//            dump($_SERVER["DOCUMENT_ROOT"]. DIRECTORY_SEPARATOR ."/databaseBakcup/". DIRECTORY_SEPARATOR .date('Ymdhis'));die();
            //生成备份文件夹
            $backupdir = '';
            if (isset($_POST['backdir']) && $_POST['backdir'] != '') {
                $backupdir = $_POST['backdir'];
            } else {
                $backupdir = $_SERVER["DOCUMENT_ROOT"]. DIRECTORY_SEPARATOR ."/public/databaseBakcup/". DIRECTORY_SEPARATOR .date('Ymdhis');
            }
            if (!is_dir($backupdir)) {
                mkdir($backupdir, 0777, true);
            }
            $backup = BackupFactory::instance($dbConfig['type'], $dbConfig['hostname'].":".$dbConfig['hostport'], $dbConfig['database'], $dbConfig['username'], $dbConfig['password']);
            $result = $backup->setbackdir($backupdir)
                ->setvolsize(0.2)
//    ->setonlystructure(false) //设置是否只备份目录结构
//    ->settablelist(['md_menu', 'md_api_group','cmseasy_b_category']) //设置要备份的表， 默认全部表
//    ->setstructuretable(['md_api_group']) //设置哪些表只备份结构不备份数据
                ->ajaxbackup($_POST);

            echo json_encode($result);

        }else{
            $this->title = '备份数据';

            return $this->fetch();
        }
    }

    /**
     * 恢复数据
     * @auth true
     * @throws \think\Exception
     * @throws \think\db\exception\DataNotFoundException
     * @throws \think\db\exception\ModelNotFoundException
     * @throws \think\exception\DbException
     * @throws \think\exception\PDOException
     */
    public function recovery()
    {
        if($this->request->isPost()){
            //获得数据库配置
            $getConfig = Db::getConfig();
            $dbConfig['type'] = $getConfig['connections']['mysql']['type'];
            $dbConfig['hostname'] = $getConfig['connections']['mysql']['hostname'];
            $dbConfig['database'] = $getConfig['connections']['mysql']['database'];
            $dbConfig['username'] = $getConfig['connections']['mysql']['username'];
            $dbConfig['password'] = $getConfig['connections']['mysql']['password'];
            $dbConfig['hostport'] = $getConfig['connections']['mysql']['hostport'];

            $_POST['predate'] = isset($_POST['predate'])?$_POST['predate']:[];

            include $_SERVER["DOCUMENT_ROOT"]."/public/tools/buckup/RecoveryFactory.php";
            include $_SERVER["DOCUMENT_ROOT"]."/public/tools/buckup/IRecovery.php";
            include $_SERVER["DOCUMENT_ROOT"]."/public/tools/buckup/mysql/Recovery.php";

            $recovery = RecoveryFactory::instance($dbConfig['type'], $dbConfig['hostname'].":".$dbConfig['hostport'], $dbConfig['database'], $dbConfig['username'], $dbConfig['password']);
            $result = $recovery->setSqlfiledir($_SERVER["DOCUMENT_ROOT"]. DIRECTORY_SEPARATOR ."/public/databaseBakcup/". DIRECTORY_SEPARATOR .$_POST['dir'])
                ->ajaxrecovery($_POST['predate']);

            echo json_encode($result);

        }else{
            $this->title = '恢复数据';

            //获得备份数据(文件夹)列表
            $dir = $_SERVER["DOCUMENT_ROOT"]. DIRECTORY_SEPARATOR ."/public/databaseBakcup/". DIRECTORY_SEPARATOR;
            $dirArray[]=NULL;
            if (false != ($handle = opendir ( $dir ))) {
                $i=0;
                while ( false !== ($file = readdir ( $handle )) ) {
                    //去掉”“.”、“..”以及带“.xxx”后缀的文件
                    if ($file != "." && $file != ".."&&!strpos($file,".")) {
                        $dirArray[$i]=$file;
                        $i++;
                    }
                }
                //关闭句柄
                closedir ( $handle );
            }
            $dirList = [];
            if($dirArray[0]){
                $dirArray = array_reverse($dirArray);
                foreach ($dirArray AS $key => $value){
                    $dirList[$key]['dir_name'] = $value;
                    $dirList[$key]['create_date'] = date("Y年m月d日 H:i:s", strtotime($value));
                }
            }

            $this->backupDir = $dirList;

            return $this->fetch();
        }
    }

    /**
     * 删除指定备份
     * @auth true
     * @throws \think\Exception
     * @throws \think\exception\PDOException
     */
    public function remove()
    {
        $dir = isset($_POST['dir'])?$_POST['dir']:'';

        $headimg_old = $_SERVER["DOCUMENT_ROOT"]."/public/databaseBakcup/".$dir;
        //扫描一个目录内的所有目录和文件并返回数组
        $dirs = scandir($headimg_old);

        foreach ($dirs as $dir) {
            //排除目录中的当前目录(.)和上一级目录(..)
            if ($dir != '.' && $dir != '..') {
                $sonDir = $headimg_old.'/'.$dir;
                //判断是否文件夹
                if (!is_dir($sonDir)) {
                    //如果是文件直接删除
                    @unlink($sonDir);
                }
            }
        }
        @rmdir($headimg_old);

        $this->success("删除成功!");
    }

    /**
     * 优化表
     * @auth true
     * @throws \think\Exception
     * @throws \think\exception\PDOException
     */
    public function optimize()
    {
        $table = isset($_POST['table'])?$_POST['table']:'';

        $return = Db::query('OPTIMIZE TABLE '.$table);

        $this->success("优化完成!");
    }

    /**
     * 修复表
     * @auth true
     * @throws \think\Exception
     * @throws \think\exception\PDOException
     */
    public function repair()
    {
        $table = isset($_POST['table'])?$_POST['table']:'';

        $return = Db::query('REPAIR TABLE '.$table);

        $this->success("修复完成!");
    }


}


