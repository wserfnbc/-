<?php
namespace app\CodingPillow\controller;
use think\admin\Controller;
use think\facade\Db;
use PHPMailer\PHPMailer\PHPMailer;
class PillowAPI extends Controller
{
    public function FormValidate()
    {
        if (!request()->isPost()) return;
        
        $data = $this->getFormData();
        $this->validateRequiredFields($data);
        $this->success("Validation completed", '');
    }

    public function FormSubmit()
    {
        if (!request()->isPost()) return;
        
        $data = $this->getFormData();
        $formId = $this->validateAndSanitize($data['form_id'], 'int');
        $formType = $this->validateAndSanitize($data['type'], 'string');
        unset($data['form_id'], $data['type']);
        $formContent = $this->formatFormContent($data);
        $ip = $this->getClientIP();

        $this->handle_email_notification($formId, $data);

        DB::name('DataFormList')->insert([
            'form_id' => $formId,
            'member_IP' => $ip,
            'form_content' => $formContent,
            'type' => $formType,
            'status' => 0,
        ]);

        $this->success("Thank you for your submission! We will contact you and reply as soon as possible.", '');
    }

    private function getFormData()
    {
        $data = $_POST;
        foreach ($data as $key => $value) {
            if ($key === 'information' && is_string($value)) {
                $jsonData = json_decode($value, true);
                if (json_last_error() === JSON_ERROR_NONE && is_array($jsonData)) {
                    $data[$key] = $this->filterPetData($jsonData);
                } else {
                    $data[$key] = $value;
                }
            } elseif (is_string($value)) {
                $data[$key] = $this->filterXSS($value);
            } elseif (is_array($value)) {
                $data[$key] = array_map([$this, 'filterXSS'], $value);
            }
        }
        return $data;
    }

    private function filterPetData($petDataArray)
    {
        $filtered = [];
        foreach ($petDataArray as $pet) {
            if (is_array($pet)) {
                $filteredPet = [];
                foreach ($pet as $k => $v) {
                    $filteredPet[$k] = is_string($v) ? $this->filterXSS($v) : $v;
                }
                $filtered[] = $filteredPet;
            }
        }
        return $filtered;
    }

    private function filterXSS($input)
    {
        $input = strip_tags($input);
        $input = htmlspecialchars($input, ENT_QUOTES, 'UTF-8');
        $input = preg_replace('/<script\b[^<]*(?:(?!<\/script>)<[^<]*)*<\/script>/mi', '', $input);
        $input = preg_replace('/javascript:/i', '', $input);
        $input = preg_replace('/on\w+\s*=/i', '', $input);
        return trim($input);
    }

    private function validateRequiredFields($data)
    {
        $formId = $this->validateAndSanitize($data['form_id'], 'int');
        unset($data['form_id'], $data['type']);

        foreach ($data as $key => $val) {
            $key = $this->validateFieldName($key);
            
            if ($key === 'information' && is_array($val)) {
                $requiredFields = ['petName', 'petBreed', 'gender', 'petWeight', 'petBirthday', 'microchipNumber', 'vaccinationStatus', 'sterilisationStatus', 'parasiteStatus', 'aggressiveBehavior'];
                $fieldLabels = [
                    'petName' => 'Name',
                    'petBreed' => 'Breed',
                    'gender' => 'Gender',
                    'petWeight' => 'Weight',
                    'petBirthday' => 'Birthday',
                    'microchipNumber' => 'Microchip Number',
                    'vaccinationStatus' => 'Vaccination Status',
                    'sterilisationStatus' => 'Sterilisation Status',
                    'parasiteStatus' => 'Parasite Treatment Status',
                    'aggressiveBehavior' => 'Any aggressive/anxious behaviors'
                ];
                
                foreach ($val as $petIndex => $pet) {
                    if (is_array($pet)) {
                        foreach ($requiredFields as $field) {
                            if (!isset($pet[$field]) || (is_string($pet[$field]) && trim($pet[$field]) === '')) {
                                $label = $fieldLabels[$field] ?? $field;
                                $this->error("Pet " . ($petIndex + 1) . ": Please enter " . $label . "!", '');
                            }
                        }
                    }
                }
                continue;
            }
            
            $param = DB::name('DataFormParam')
                ->field("form_label,is_required,type")
                ->where(['form_name' => $key])
                ->find();

            $isEmpty = is_array($val) ? empty($val) : empty($val);
            if ($param && $isEmpty && $param['is_required'] == 1) {
                $this->error(($param['type'] == 1 ? "Please select" : "Please enter") . $param['form_label'] . "!", '');
            }
        }
    }

    private function formatFormContent($data)
    {
        $list = [];
        foreach ($data as $key => $val) {
            $key = $this->validateFieldName($key);
            
            $param = DB::name('DataFormParam')
                ->field("form_label")
                ->where(['form_name' => $key])
                ->find();
            
            if ($param && isset($param['form_label'])) {
                if ($key === 'information' && is_array($val)) {
                    $list[$param['form_label']] = json_encode($val, JSON_UNESCAPED_UNICODE);
                } else {
                    $list[$param['form_label']] = $val;
                }
            }
        }
        return json_encode($list, JSON_UNESCAPED_UNICODE);
    }

    private function getClientIP()
    {
        $ip = $_SERVER['REMOTE_ADDR'];
        
        if (isset($_SERVER['HTTP_CLIENT_IP']) && preg_match('/^([0-9]{1,3}\.){3}[0-9]{1,3}$/', $_SERVER['HTTP_CLIENT_IP'])) {
            $ip = $_SERVER['HTTP_CLIENT_IP'];
        } elseif (isset($_SERVER['HTTP_X_FORWARDED_FOR'])) {
            foreach (explode(',', $_SERVER['HTTP_X_FORWARDED_FOR']) as $xip) {
                if (preg_match('/^([0-9]{1,3}\.){3}[0-9]{1,3}$/', $xip) && !preg_match('#^(10|172\.16|192\.168)\.#', $xip)) {
                    $ip = $xip;
                    break;
                }
            }
        }
        
        return $ip;
    }

    private function isDuplicateSubmission($formId, $formContent)
    {
        return DB::name("DataFormList")
            ->where(['form_id' => $formId, 'status' => 0])
            ->whereLike('form_content', '%' . $formContent . '%')
            ->count("id") > 0;
    }

    private function validateAndSanitize($input, $type = 'string')
    {
        switch ($type) {
            case 'int':
                return filter_var($input, FILTER_VALIDATE_INT) ?: 0;
            case 'email':
                return filter_var($input, FILTER_VALIDATE_EMAIL) ?: '';
            case 'url':
                return filter_var($input, FILTER_VALIDATE_URL) ?: '';
            case 'string':
            default:
                $input = preg_replace('/[\'";\\\]/', '', $input);
                $input = preg_replace('/--|\/\*|\*\//', '', $input);
                $input = preg_replace('/\b(union|select|insert|update|delete|drop|create|alter|exec|execute)\b/i', '', $input);
                return trim($input);
        }
    }

    private function validateFieldName($fieldName)
    {
        $fieldName = preg_replace('/[^a-zA-Z0-9_]/', '', $fieldName);
        return substr($fieldName, 0, 50);
    }

    private function handle_email_notification($formId, $data)
    {
        $recipientEmail = DB::name('SystemConfig')->where(['name' => 'contact_email', 'type' => 'base'])->value('value');
        
        if (!empty($recipientEmail)) {
            $html = '
            <div style="background-color: #f5f5f5; padding: 40px 0;">
                <div style="max-width: 600px; margin: 0 auto; background-color: #ffffff; border-radius: 8px; box-shadow: 0 2px 4px rgba(0,0,0,0.1); overflow: hidden;">
                    <div style="background-color: #000000; padding: 25px 30px;">
                        <h1 style="color: #ffffff; margin: 0; font-size: 20px; font-weight: 100;">新客户信息提交</h1>
                    </div>
                    <div style="padding: 30px; color: #333333; font-family: \'Segoe UI\', Arial, sans-serif; font-size: 14px; line-height: 1.6;">
                        <div style="margin-bottom: 25px; color: #666666;">
                            我们收到了新的客户信息提交。详细信息如下：
                        </div>
                        <div style="background: #fafafa; border-radius: 4px; padding: 25px; margin-bottom: 25px; display: grid ;gap: 10px;">
                        ';
                        foreach ($data as $k => $v) {
                            $param = DB::name('DataFormParam')
                                ->field('form_label')
                                ->where(['form_name' => $k, 'form_id' => $formId])
                                ->find();
                            
                            if ($k === 'information' && is_array($v)) {
                                $displayValue = '';
                                foreach ($v as $index => $pet) {
                                    if (is_array($pet)) {
                                        $displayValue .= '<div style="margin-top: 10px; padding-left: 20px; border-left: 2px solid #ddd;">';
                                        $displayValue .= '<strong>宠物 ' . ($index + 1) . '：</strong><br>';
                                        foreach ($pet as $field => $value) {
                                            $fieldLabel = $this->getPetFieldLabel($field);
                                            if ($field === 'vaccinationRecord' && !empty($value)) {
                                                $fileUrl = $value;
                                                if (strpos($fileUrl, 'http') !== 0) {
                                                    $domain = request()->domain();
                                                    $fileUrl = rtrim($domain, '/') . '/' . ltrim($value, '/');
                                                }
                                                $displayValue .= $fieldLabel . '：<a href="' . htmlspecialchars($fileUrl, ENT_QUOTES, 'UTF-8') . '" target="_blank"><img src="' . htmlspecialchars($fileUrl, ENT_QUOTES, 'UTF-8') . '" style="width: 150px; height: auto;" /></a><br>';
                                            } else {
                                                $displayValue .= $fieldLabel . '：' . htmlspecialchars($value, ENT_QUOTES, 'UTF-8') . '<br>';
                                            }
                                        }
                                        $displayValue .= '</div>';
                                    }
                                }
                            } else {
                                $displayValue = is_array($v) ? implode(', ', $v) : htmlspecialchars($v, ENT_QUOTES, 'UTF-8');
                            }
                            
                            if ($param && isset($param['form_label'])) {
                                $html .= "<div>
                                            <div style='width: 100%; color: #666666;'>{$param['form_label']}：<span style='color: #000000;'>{$displayValue}</span></div>
                                        </div>";
                            } else {
                                $html .= "<div>
                                            <div style='width: 100%; color: #666666;'>{$k}：<span style='color: #000000;'>{$displayValue}</span></div>
                                        </div>";
                            }
                        }
                        $html .= '
                        </div>
                        <div style="background-color: #fafafa; border-left: 3px solid #000000; padding: 15px 20px; font-size: 14px; color: #666666;">
                            <p style="margin: 0;">提交时间：' . date('Y-m-d H:i:s') . '</p>
                        </div>
                    </div>
                    <div style="padding: 20px 30px; background-color: #fafafa; border-top: 1px solid #eeeeee; text-align: center; font-size: 12px; color: #999999;">
                        此邮件由系统自动发送，请勿直接回复
                    </div>
                </div>
            </div>';
            
            $formInfo = DB::name('DataForm')->where(['id' => $formId])->find();
            $recipientName = $formInfo['form_name'] ?? '客户';
            
            $this->send_mail($recipientEmail, $recipientName, '新客户信息', $html);
        }
    }

    private function getPetFieldLabel($field)
    {
        $labels = [
            'petName' => 'Name',
            'petBreed' => 'Breed',
            'gender' => 'Gender',
            'petWeight' => 'Weight',
            'petBirthday' => 'Birthday',
            'microchipNumber' => 'Microchip Number',
            'vaccinationStatus' => 'Vaccination Status',
            'vaccinationRecord' => 'Vaccination Record',
            'sterilisationStatus' => 'Sterilisation Status',
            'parasiteStatus' => 'Parasite Treatment Status',
            'aggressiveBehavior' => 'Any aggressive/anxious behaviors'
        ];
        return $labels[$field] ?? $field;
    }

    private function send_mail($toemail, $name, $title, $html)
    {
        $mailConfig = config('mail');
        
        if (!$mailConfig) {
            return ['code' => 0, 'info' => "Email configuration error: configuration file not found"];
        }
        
        $mail = new PHPMailer();
        $mail->isSMTP();
        $mail->CharSet = "utf8";
        $mail->Host = $mailConfig['smtp_host'] ?? '';
        $mail->SMTPAuth = true;
        $mail->Username = $mailConfig['smtp_username'] ?? '';
        $mail->Password = $mailConfig['smtp_password'] ?? '';
        $mail->SMTPSecure = $mailConfig['smtp_secure'] ?? '';
        $mail->Port = $mailConfig['smtp_port'] ?? 465;
        $mail->setFrom($mailConfig['from_email'] ?? '', $mailConfig['from_name'] ?? '客户提交信息');
        $mail->addAddress($toemail, $name);
        $mail->Subject = $title;
        $mail->IsHTML(true);
        $mail->Body = $html;
        if (!$mail->send()) {
            return ['code' => 0, 'info' => "发送错误：" . $mail->ErrorInfo];
        } else {
            return ['code' => 1, 'info' => "邮件发送成功！"];
        }
    }

    public function upload_file()
    {
        $errorInfo = [
            1001 => '文件超过php.ini限制',
            1002 => '文件超过html限制',
            1003 => '文件上传不完整',
            1004 => '没有选择文件',
            1006 => '服务器内部错误',
            1007 => '服务器内部错误',
            1008 => '文件太大',
            1009 => '文件类型不合法',
            1010 => '文件移动失败'
        ];
        $dir = date("Y", time()) . "/" . date("md", time()) . "/";
        $path = "public/upload/files/" . $dir;
        if (!file_exists($path)) {
            mkdir($path, 0777, true);
        }
        $maxsize = 1024 * 1024 * 900;

        $mime = [
            'image/gif', 'image/png', 'image/jpeg', 'image/bmp', 'image/webp', 'image/x-icon', 'image/vnd.microsoft.icon',
            'application/vnd.ms-excel', 'application/vnd.openxmlformats-officedocument.spreadsheetml.sheet',
            'application/msword', 'application/vnd.openxmlformats-officedocument.wordprocessingml.document',
            'application/vnd.ms-powerpoint', 'application/vnd.openxmlformats-officedocument.presentationml.presentation',
            'application/pdf',
            'application/zip', 'application/x-zip-compressed', 'application/x-gzip', 'application/x-rar-compressed',
            'text/plain', 'text/csv', 'text/html',
            'application/dxf', 'application/dwg',
            'application/json', 'application/xml'
        ];

        $file = $_FILES['file'];
        
        if ($file['error'] > 0) {
            return json(['code' => 0, 'msg' => $errorInfo[$file['error']] ?? 'Unknown error']);
        }

        if ($file['size'] > $maxsize) {
            return json(['code' => 0, 'msg' => 'File size exceeds limit']);
        }

        if (!in_array($file['type'], $mime)) {
            return json(['code' => 0, 'msg' => 'Unsupported file type']);
        }

        $tmp = $file['tmp_name'];
        $fileName = date('YmdHis') . substr(str_shuffle('abcdefghijklmnopqrstuvwxyzABCDEFGHIJKLMNOPQRSTUVWXYZ0123456789'), 0, 6);
        $ext = pathinfo($file['name'], PATHINFO_EXTENSION);
        $basename = $fileName . '.' . $ext;
        $dest = $path . $basename;

        if (move_uploaded_file($tmp, $dest)) {
            $file_url = "/" . $path . $basename;
            return json(['code' => 1, 'msg' => 'File uploaded successfully', 'data' => $file_url]);
        } else {
            return json(['code' => 0, 'msg' => 'File upload failed: ' . $errorInfo[1010]]);
        }
    }

}

