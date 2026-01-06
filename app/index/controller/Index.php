<?php
namespace app\index\controller;

use app\index\controller\Custom;
use think\admin\Controller;
use think\facade\Db;
use think\facade\Route;
use think\facade\Cache;
use PHPMailer\PHPMailer\PHPMailer;

/**
 * Class Index
 * @package app\index\controller
 */
class Index extends Controller
{
    public function initialize()
    {
        //检测用户登陆状态
        $userID = $this->app->session->get('uid');
        $userInfo = $this->app->db->name('DataUser')->where('id',$userID)->find();
        $this->assign('userInfo',$userInfo);

        /* 定义全局函数 */
        $GLOBALS['function'] = new \app\index\controller\Custom($this->app);
        $this->assign('function',new \app\index\controller\Custom($this->app));

        /* 定义商城全局函数 */
        $GLOBALS['shop'] = new \app\index\controller\Customshop($this->app);
        $this->assign('shop',new \app\index\controller\Customshop($this->app));

        $config = $GLOBALS['function']->getConfig();      //获得网站配置
        $this->assign('config',$config);

        //加载语言包
        $langGet = $this->request->param('lang');
        if (empty($langGet)) {
            $this->app->session->set('lang', 'cn');
        } else {
            $this->app->session->set('lang', $langGet);
        }
        $lang = [];
        $langSession = $this->app->session->get('lang');
        include "public/lang/files/" . $langSession . ".php";
        $this->assign('lang', $lang);
        $this->assign('langSession',$langSession);

        //获取语言包列表
        $files = scandir($_SERVER["DOCUMENT_ROOT"]."/public/lang/files/");
        $LanguageFilesList = [];
        // 读取指定目录下的所有文件
        foreach ($files AS $key => $value){
            if($value == "." || $value == ".."){

            }else{
                $languageName = substr($value,0,strrpos($value,"."));
                $LanguageFilesList[$key] = $languageName;
            }
        }
        $LanguageFilesList = array_values($LanguageFilesList);
        $this->assign('languagesList', $LanguageFilesList);

        //顶部自定义导航
//        $nav = $this->app->db->name('DataNav')->where(['parent_id'=>0,'type'=>'顶部','status'=>1,'is_deleted'=>0])->select()->toArray();
        $nav = $GLOBALS['function']->getAllCat();      //获得网站导航
        $this->assign('nav',$nav);

        /*Banner轮播图*/
        $bannerArr = $GLOBALS['function']->sliderPictures(4);   //sliderPictures()里的数值去查看system_data数据表里name为对应位置，如首页轮播sliderHomePage所在的id
        $this->assign('banner',$bannerArr);

        //获得内容模型列表(用于搜索功能)
        $this->assign('modelList',$GLOBALS['function']->getModelList());

    }

    public function index()
    {


        $product_list = DB::name("DataArticleShowDefault")
            ->where(['status'=>1,'is_best'=>1])->order("sort desc,id desc")->select();
        $this->assign('product_list',$product_list);

        return $this->fetch();
    }

    /**
     * 提交表单处理
     */
    public function form_handle()
    {
        //接收表单提交
        if (request()->isPost()) {
            $data = $_POST;

            $formId = $data['form_id'];     //表单ID
            $formType = $data['type'];     //表单类型
            unset($data['form_id'],$data['type']);

            $list = array();
            foreach ($data AS $key => $val){
                //取出数据库中对应的每一个表单参数中文名称和是否必填
                $param = DB::name('DataFormParam')->field("form_label,is_required,type")->where(['form_name'=>$key])->find();

                //如果前台用户提交的表单有空值并且是必填项
                if(empty($val) && $param['is_required'] == 1){
                    //判断表单参数是下拉菜单还是文本框
                    if($param['type'] == 1){
                        $this->error("请选择".$param['form_label']."！", '');
                    }else{
                        $this->error("请填写".$param['form_label']."！", '');
                    }

                }
                $list[$param['form_label']] = $val;
            }
            //将用户提交的表单信息数组转化成json格式，以便下边入库
            $formContent = json_encode($list);

            //获得访问用户的IP
            $ip = $_SERVER['REMOTE_ADDR'];
            if (isset($_SERVER['HTTP_CLIENT_IP']) && preg_match('/^([0-9]{1,3}\.){3}[0-9]{1,3}$/', $_SERVER['HTTP_CLIENT_IP'])) {
                $ip = $_SERVER['HTTP_CLIENT_IP'];
            } elseif(isset($_SERVER['HTTP_X_FORWARDED_FOR']) AND preg_match_all('#\d{1,3}\.\d{1,3}\.\d{1,3}\.\d{1,3}#s', $_SERVER['HTTP_X_FORWARDED_FOR'], $matches)) {
                foreach ($matches[0] AS $xip) {
                    if (!preg_match('#^(10|172\.16|192\.168)\.#', $xip)) {
                        $ip = $xip;
                        break;
                    }
                }
            }

            $member_id = $this->app->session->get('user_id') ?? 0;        //如果用户登陆，则获得用户ID



            $ifThere = DB::name("DataFormList")->where(['form_id' => $formId,'member_id'=>$member_id,'status'=>0])->whereLike('form_content','%'.$formContent.'%')->count("id");
            if($ifThere > 0){
                $this->error("您已提交过申请，请不要重复提交！", '');
            }else{
                //检查此表单是否需要发送邮件
                $isEmail = DB::name("DataForm")->where(['id'=>$formId])->value('is_email');
                if(!empty($isEmail)){
                    //获取网站配置取网站名称
                    $config = $GLOBALS['function']->getConfig();
                    
                    //处理表单内容为邮件内容
                    if(!empty($config['email'])){
                        
                        $html = "";
                        foreach ($data AS $k => $v){
                            $form_label = DB::name('DataFormParam')->where(['form_name'=>$k,'form_id'=>$formId])->value('form_label');
                            
                            $html .= "<p>".$form_label."：".$v."</p>";
                        }
          
                        $this->send_mail($config['email'],$data['name'],'['.$config['name'].']客户提交信息',$html);
                    }
                }

                DB::name('DataFormList')->insert(['form_id' => $formId,
                    'member_id' => $member_id,
                    'member_IP' => $ip,
                    'form_content' => $formContent,
                    'type' => $formType,
                    'status' => 0,
                    'is_deleted' => 0
                ]);
                $this->success("提交成功！请耐心等待后台审核！", '');
            }
        }

    }

   /**
     * 发送邮件
     * $toemail 收件人邮箱
     * $name 收件人名称
     */
    public static function send_mail($toemail, $name, $title,$content = null)
    {
        //获得网站邮箱配置
        $mailConfig = DB::name('SystemMailConfig')->where(['id'=>'1'])->find();

        // 收件人的邮箱
//        $toemail = '9482181@qq.com';
        $mail = new PHPMailer();
        // 使用SMTP服务
        $mail->isSMTP();
        // 编码格式为utf8，不设置编码的话，中文会出现乱码
        $mail->CharSet = "utf8";
        // 发送人的SMTP服务器地址（QQ邮箱就是“smtp.qq.com”）
        $mail->Host = $mailConfig['smtp'];
        // 是否使用身份验证
        $mail->SMTPAuth = true;
        // 发送人的邮箱用户名，就是你自己的SMTP服务使用的邮箱
        $mail->Username = $mailConfig['send_from'];
        // 发送方的邮箱密码，注意这里填写的是“客户端授权密码”而不是邮箱的登录密码！
        $mail->Password = $mailConfig['mail_password'];
        // 使用ssl协议方式
        $mail->SMTPSecure = "ssl";
        //ssl协议方式端口号是465
        $mail->Port = $mailConfig['port'];

        // 设置发件人信息，如邮件格式说明中的发件人，这里会显示为  Mailer(xxx@qq.com）
        $mail->setFrom($mailConfig['send_from'], $mailConfig['sender_name']);
        // 设置收件人信息，如邮件格式说明中的收件人，这里会显示为Liang(yyyy@163.com)
        $mail->addAddress($toemail, $name);
        // 设置回复人信息，指的是收件人收到邮件后，如果要回复，回复邮件将发送到的邮箱地址
        $mail->addReplyTo($mailConfig['send_from'], $mailConfig['sender_name']);

        //$mail->addCC("xxx@163.com");// 设置邮件抄送人，可以只写地址，上述的设置也可以只写地址(这个人也能收到邮件)
        //$mail->addBCC("xxx@163.com");// 设置秘密抄送人(这个人也能收到邮件)
        //$mail->addAttachment("bug0.jpg");// 添加附件

        // 邮件标题
        $mail->Subject = $title;
        $mail->IsHTML(true); //支持html格式内容
        // 邮件正文
        $mailContent = "<p>Hi ".$name.",</p><p></p><p>Thanks for reaching out to us at Unicarbon.This is an automated message to confirm that we have received your request.</p>".
            "<p></p><p>We will get back to you as soon as possible.</p><p></p>".
            "<p>Thankyou!</p><p>GuangzhouUnicarbon</p>";
		if($content){
			$mailContent = $content;
		}
        $mail->Body = $mailContent;
        //$mail->AltBody = "This is the plain text纯文本";// 这个是设置纯文本方式显示的正文内容，如果不支持Html方式，就会用到这个，基本无用
        if (!$mail->send()) {
            // 发送邮件
            return ['code'=>0, 'info'=>"发送错误：". $mail->ErrorInfo];
        } else {
            return ['code'=>1, 'info'=>"邮件发送成功!"];
        }

    }
    
    
    
    public static function to_upload_files($file, $mime, $maxsize, $path)
    {
        // 判断文件上传错误
        switch ($file['error']) {
            case 1:
                return 1001; // '文件超过php.ini限制';
                exit();
            case 2:
                return 1002; // '文件超过html限制';
                exit();
            case 3:
                return 1003; // '文件上传不完整';
                exit();
            case 4:
                return 1004; // '没有选择文件';
                exit();
            case 6:
                return 1006; // '服务器内部错误';
                exit();
            case 7:
                return 1007; // '服务器内部错误';
                exit();
        }
        if ($file['size'] > $maxsize) {
            return 1008; // 文件太大;
            exit();
        }
        // 判断用户上传的文件类型是否合法
        if (! in_array($file['type'], $mime)) {
            return 1009; // 文件类型不合法;
            exit();
        }
        $tmp = $file['tmp_name'];
        $fileName = Self::getRandName();
        // 获取文件的扩展名
        $ext = pathinfo($file['name'], PATHINFO_EXTENSION);
        // 拼接文件名
        $basename = $fileName . '.' . $ext;
        // 拼接路径
        $dest = $path . $basename;
        
        // 将临时文件夹中的文件，移动到目标位置
        if (move_uploaded_file($tmp, $dest)) {
            return $basename;
        } else {
            return 1010;
        }
    }
    
    // 随机文件的格式
    public static function getRandName()
    {
        $string = date('YmdHis');
        for ($i = 0; $i < 6; $i ++) {
            switch (mt_rand(0, 2)) {
                case 0:
                    $string .= chr(mt_rand(97, 122)); // 小a
                    break;
                case 1:
                    $string .= chr(mt_rand(65, 90)); // 大A
                    break;
                case 2:
                    $string .= mt_rand(0, 9); // 获取随机数
                    break;
            }
        }
        return $string;
    }
    /**
     * Ajax上传文件处理
     */
    public function upload_file()
    {
    
        //定义错误编码与错误信息的对应关系
        $errorInfo=[
            '1001'=>'文件超过php.ini限',
            '1002'=>'文件超过html限制',
            '1003'=>'文件上传不完整',
            '1004'=>'没有选择文件',
            '1006'=>'服务器内部错误',
            '1007'=>'服务器内部错误',
            '1008'=>'文件太大',
            '1009'=>'文件类型不合法',
            '1010'=>'文件移动失败'
        ];
        //上传文件处理
        $dir = date("Y",time())."/".date("md",time())."/";
        $path = "public/upload/files/".$dir ;
        if(!file_exists($path))//判断文件夹是否存在
        {
            mkdir($path,0777,true);
        }
        
        $maxsize = 1024*1024*900;
        $mime = ['application/vnd.ms-excel','application/vnd.openxmlformats-officedocument.spreadsheetml.sheet',
                 'application/msword','application/vnd.openxmlformats-officedocument.wordprocessingml.document',
                 'image/gif', 'image/png', 'image/jpeg', 'image/svg+xml', 'image/bmp', 'image/webp', 'image/x-icon', 'image/vnd.microsoft.icon',
                 'application/zip','application/x-zip-compressed','application/x-gzip','application/x-rar-compressed'];
        $file = $_FILES['file'];
    
        $return = $this->to_upload_files($file,$mime,$maxsize,$path);
    
        if($return == 1001 || $return == 1002 || $return == 1003 || $return == 1004 || $return == 1006 || $return == 1007 || $return == 1008 || $return == 1009 || $return == 1010){
            $this->error("上传文件错误：".$errorInfo[$return]);
        }else{
            $file_url = "/".$path.$return;     //上传的文件地址及文件名
            $this->success("文件上传成功", $file_url);
        }
    }

}