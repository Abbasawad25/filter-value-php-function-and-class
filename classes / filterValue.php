<?php
class filterValue{
	private $errors = [];
    private $data = [];
    private $badWords = ['كلمة1','كلمة2','spam','scam','viagra'];
    private $countryCodes = [
        '249' => ['name'=>'Sudan', 'min'=>9, 'max'=>9],
        '966' => ['name'=>'Saudi', 'min'=>9, 'max'=>9],
        '20' => ['name'=>'Egypt', 'min'=>10, 'max'=>10],
        '971' => ['name'=>'UAE', 'min'=>9, 'max'=>9],
        '962' => ['name'=>'Jordan', 'min'=>9, 'max'=>9],
    ];

    public function filterValue($inputs, $rules, $files = []){
        $this->errors = [];
        $this->data = [];

        foreach($rules as $rule){
            $parsed = $this->parseRule($rule);
            $field = $parsed['field'];
            $options = $parsed['options'];

            $value = $inputs[$field]?? ($files[$field]?? null);
            $result = $this->applyRule($value, $options, $field);

            if($result['error']){
                $this->errors[$field] = $result['error'];
            }else{
                $this->data[$field] = $result['value'];
            }
        }
        return ['data' => $this->data, 'errors' => $this->errors];
    }

    private function parseRule($rule){
        // لو مفيش : يعني اسم الحقل بس
        if(strpos($rule, ':') === false){
            return [
                'field' => trim($rule),
                'options' => []
            ];
        }

        // نقسم على : أول مرة بس
        list($field, $rulesString) = explode(':', $rule, 2);
        $field = trim($field);
        
        $options = [];
        if($rulesString !== ''){
            $parts = explode('|', $rulesString);
            foreach($parts as $part){
                $part = trim($part);
                if($part === '') continue;
                
                if(strpos($part, '=')!== false){
                    list($key, $val) = explode('=', $part, 2);
                    $options[trim($key)] = trim($val);
                }else{
                    $options[$part] = true;
                }
            }
        }

        return ['field' => $field, 'options' => $options];
    }

    private function applyRule($value, $opt, $field){
        $required = isset($opt['required']);
        $type = $opt['type']?? 'text'; // النوع الافتراضي

        // للملفات نتخطى التنظيف النصي
        if($type !== 'file' && is_string($value)){
            $value = trim($value);
            $value = str_replace(chr(0), '', $value);
        }

        // نتحقق لو فاضي بطريقة آمنة
        $isEmptyFile = $type === 'file' && (($value['error']?? UPLOAD_ERR_NO_FILE) === UPLOAD_ERR_NO_FILE);
        $isEmpty = $value === '' || $value === null || $isEmptyFile;

        if($required && $isEmpty){
            return ['value'=>null, 'error'=>'الحقل '.$field.' مطلوب'];
        }

        if(!$required && $isEmpty){
            return ['value'=>null, 'error'=>null];
        }

        // لو حددت type في القواعد
        if(isset($opt['type'])){
            $type = $opt['type'];
        }else{
            // لو ماحددتش type استخدم اسم الحقل كـ type
            $type = $field;
        }

        switch($type){
        	case "username":
                $value = strip_tags($value);
                $min = $opt['min'] ?? 5;
                $max = $opt['max'] ?? 53;

                if(!preg_match("/^[a-zA-Z0-9]+$/", $value)){
                    return ['value'=>null, 'error'=>'اسم المستخدم بالانجليزية فقط'];
                }
                if(mb_strlen($value) < $min || mb_strlen($value) > $max){
                    return ['value'=>null, 'error'=>"اسم المستخدم يجب أن يكون بين $min و $max حرف"];
                }
                $value = str_replace(" ","",$value);
               $value = strtolower($value);
                return ['value'=>$value, 'error'=>null];
               break;
           case "fullname":
                $value = strip_tags($value);
                $value = preg_replace('/[^\p{L}\s]/u', '', $value);
                $value = preg_replace('/\s+/', ' ', trim($value));
                $words = explode(' ', $value);

                if(count($words) < 2){
                    
                    return ['value'=>null, 'error'=>'يجب ان يكون الاسم كامل'];
                }
                if(mb_strlen($words[0]) < 2 || mb_strlen($words[1]) < 2){
                    
                    return ['value'=>null, 'error'=>'Each word must be at least 2 characters'];
                }
                $value = implode(" ",$words);
                return ['value'=>$value, 'error'=>null];
               break;
            case "email":
                $allowed_domains = $opt['allowed_domains'] ?? [];
                $blocked_domains = $opt['blocked_domains'] ?? ['mailinator.com','scam','10minutemail.com'];
                
                $value = strip_tags($value);
                $value = filter_var($value, FILTER_SANITIZE_EMAIL);
                
                if(!filter_var($value, FILTER_VALIDATE_EMAIL)){
                    return ['value'=>null, 'error'=>'البريد الإلكتروني غير صحيح'];
                }
                if(preg_match('/[\r\n]/', $value)){
                    return ['value'=>null, 'error'=>'البريد يحتوي على محارف غير صالحة'];
                }
                
                $domain = substr(strrchr($value, "@"), 1);
                
                if (!empty($allowed_domains) && !in_array($domain, $allowed_domains)) {
                    return ['value' => null, 'error' => "نطاق البريد الإلكتروني غير مسموح به"];
                }
                if (in_array($domain, $blocked_domains)) {
                    return ['value' => null, 'error' => "هذا النطاق محظور - بريد مؤقت"];
                }
                if (!checkdnsrr($domain, "MX")) {
                    return ['value' => null, 'error' => "نطاق البريد الإلكتروني غير صالح أو غير موجود"];
                }
                
                return ['value'=>$value, 'error'=>null];
               break;
            case "password":
     $value = strip_tags($value);
      $min = $opt['min'] ?? 8;
      $max = $opt['max'] ?? 14;
      if(strlen($value) < $min || strlen($value) > $max){
      	return ['value'=>null, 'error'=>"يحب ان تكون كلمة بين $min و $max"];
      	}
      if(!preg_match("/[A-Z]/",$value)){
      	return ['value'=>null, 'error'=>'حزف كبير'];
      	}
      if(!preg_match("/[a-z]/",$value)){
      	return ['value'=>null, 'error'=>'حرف صغير'];
      return null;
      	}
      if(!preg_match("/[0-9]/",$value)){
      	return ['value'=>null, 'error'=>'رقم علي الاقل'];
      	}
      if(!preg_match("/[^a-zA-Z0-9]/",$value)){
      	return ['value'=>null, 'error'=>'رمز'];
      	return null;
      	}
      
      $value = password_hash($value, PASSWORD_DEFAULT);
     return ['value'=>$value, 'error'=>null];
            case "text":
           $min = $options['min'] ?? 0;
            $max = $options['max'] ?? 1000;
            $value = htmlspecialchars($value, ENT_QUOTES | ENT_HTML5, 'UTF-8');  
            if(mb_strlen($value) < $min || mb_strlen($value) > $max){
              return ['value'=>null, 'error'=>'يجب ان يكون النص اكثر'];
            }
            return ['value'=>$value, 'error'=>null];
            break
     case "subject":
            $allowed = $options['allowed'] ?? '<p><br><b><strong><i><em><u><h1><h2><h3><ul><ol><li><a><img><blockquote><code><pre>';
            $value = strip_tags($value, $allowed);
            $value = preg_replace('/on\w+\s*=\s*"[^"]*"/i', '', $value);
            $value = preg_replace('/href\s*=\s*["\']javascript:[^"\']*["\']/i', 'href="#"', $value);
            $value = preg_replace('/<\?php.*?\?>/is', '', $value);
            $value = preg_replace('/<script\b[^>]*>(.*?)<\/script>/is', '', $value);
            return ['value'=>$value, 'error'=>null];
     case 'slug':
            $value = preg_replace('/[^\p{Arabic}\p{L}\p{N}\s\-]/u', '', $value);
            $value = preg_replace('/\s+/', '-', trim($value));
            $value = strtolower($value);
            if(empty($value)){
                return ['value'=>null, 'error'=>'غير صالح'];
            }
            return ['value'=>$value, 'error'=>null];
            case 'ip':
                $allow_private = isset($opt['allow_private']);
                $flag = $allow_private? 0 : FILTER_FLAG_NO_PRIV_RANGE | FILTER_FLAG_NO_RES_RANGE;

                $value = filter_var($value, FILTER_VALIDATE_IP, $flag);
                if($value === false){
                    return ['value'=>null,'error'=>'عنوان IP غير صحيح'];
                }
                return ['value'=>$value,'error'=>null];
                break;
            case 'phone':
                $value = preg_replace('/[^0-9+]/', '', $value);
                if(!preg_match('/^\+?\d+$/', $value)){
                    return ['value'=>null,'error'=>'رقم الهاتف يجب أن يحتوي على أرقام فقط'];
                }

                $country = null;
                $number = $value;
                if(strpos($value, '+') === 0){
                    foreach($this->countryCodes as $code => $info){
                        if(strpos($value, '+'.$code) === 0){
                            $country = $code;
                            $number = substr($value, strlen($code) + 1);
                            break;
                        }
                    }
                    if(!$country) return ['value'=>null,'error'=>'كود الدولة غير مدعوم'];
                }else{
                    $country = $opt['default_country']?? '249';
                }

                $info = $this->countryCodes[$country];
                if(strlen($number) < $info['min'] || strlen($number) > $info['max']){
                    return ['value'=>null,'error'=>"رقم {$info['name']} يجب أن يكون {$info['min']} أرقام"];
                }
                return ['value'=>'+'.$country.$number,'error'=>null];
             break;
             case 'number':
            $min = $options['min'] ?? null;
            $max = $options['max'] ?? null;
            
            if(!is_numeric($value)){
               return ['value'=>null,'error'=>'يجب ادخال رقم'];
            }
            
            $value = $value + 0;
            
            if($min !== null && $value < $min){
                return ['value'=>null,'error'=>"يجب ادخل من $min و $max"];
            }
            if($max !== null && $value > $max){
                return ['value'=>null,'error'=>'رقم كبير غير مسموح به'];
            }
            return ['value'=>$value,'error'=>null];
            break;
            case 'date':
            $format = $options['format'] ?? 'Y-m-d';
            $d = DateTime::createFromFormat($format, $value);
            if(!$d || $d->format($format) !== $value){
                return ['value'=>null,'error'=>"صيغة التاريخ ليست صحيحة يجب ان تكون $format"];
            }
            // تحقق من التاريخ الأدنى والأقصى
            if(isset($options['min'])){
                $min_date = DateTime::createFromFormat($format, $options['min']);
                if($d < $min_date){
                    return ['value'=>null,'error'=>"التاريخ يجب ان بعد {$options['min']}"];
                }
            }
            if(isset($options['max'])){
                $max_date = DateTime::createFromFormat($format, $options['max']);
                if($d > $max_date){
                    return ['value'=>null,'error'=>"التاريخ يجب ان يكون قبل {$options['max']}"];
                }
            }
            $value = $d->format($format);
            return ['value'=>$value,'error'=>null];
          break;
        case 'boolean':
            // يقبل: true, false, 1, 0, "1", "0", "on", "yes", "no"
            $value = filter_var($value, FILTER_VALIDATE_BOOLEAN, FILTER_NULL_ON_FAILURE);
            if($value === null){
                return ['value'=>null,'error'=>"يجب ادخال قيمة صحيحة صفر او واحد"];
            }
            return ['value'=>$value,'error'=>null];
          break;           
        case 'json':
            json_decode($value);
            if(json_last_error() !== JSON_ERROR_NONE){
               return ['value'=>null,'error'=>'تنسيق غير صحيح'];
            }
            return ['value'=>$value,'error'=>null];
            case 'url':
            $value = filter_var($value, FILTER_SANITIZE_URL);
            if(!filter_var($value, FILTER_VALIDATE_URL)){
                return ['value'=>null,'error'=>'رابط ليس صحيح'];
            }
            if(preg_match('/^(javascript|data|vbscript):/i', $value)){
                return ['value'=>null,'error'=>'غير مسوح به'];
            }
            if(!preg_match('/^https?:\/\//i', $value)){
                $value = 'https://'. $value;
            }
            return ['value'=>$value,'error'=>null];
            case "file":
                if($isEmptyFile){
                    return ['value'=>null, 'error'=>'الحقل '.$field.' مطلوب'];
                }
                return $this->validateFile($value, $opt);
            default:
                return ['value'=>htmlspecialchars($value, ENT_QUOTES), 'error'=>null];
        }
    }
    $options = $opt;
    function uploadFileSecure($file, $options = []) {
    $defaults = [
        'required' => false,
        'type' => 'file',
        'path' => 'uploads/',
        'min_size' => 1,
        'max_size' => 5 * 1024 * 1024,
        'format_date' => true,
        'scan_file' => true
    ];
    $opt = array_merge($defaults, $options);

    // 1. التحقق لو الحقل مطلوب والملف فاضي
    if ($opt['required'] && (!isset($file) || $file['error'] === UPLOAD_ERR_NO_FILE)) {
        return ['success' => false, 'file' => null, 'error' => 'الملف مطلوب'];
    }

    // لو مش مطلوب ومافي ملف مرفوع
    if (!$opt['required'] && (!isset($file) || $file['error'] === UPLOAD_ERR_NO_FILE)) {
        return ['success' => true, 'file' => null, 'error' => null];
    }

    // 2. التحقق من أخطاء الرفع
    if ($file['error']!== UPLOAD_ERR_OK) {
        $uploadErrors = [
            UPLOAD_ERR_INI_SIZE => 'حجم الملف أكبر من المسموح في php.ini',
            UPLOAD_ERR_FORM_SIZE => 'حجم الملف أكبر من المسموح في الفورم',
            UPLOAD_ERR_PARTIAL => 'تم رفع جزء من الملف فقط',
            UPLOAD_ERR_NO_TMP_DIR => 'مجلد tmp غير موجود',
            UPLOAD_ERR_CANT_WRITE => 'فشل الكتابة على القرص',
            UPLOAD_ERR_EXTENSION => 'تم إيقاف الرفع بواسطة إضافة'
        ];
        $msg = $uploadErrors[$file['error']]?? 'خطأ غير معروف في رفع الملف';
        return ['success' => false, 'file' => null, 'error' => $msg];
    }

    // 3. التحقق من الحجم
    if ($file['size'] < $opt['min_size']) {
        return ['success' => false, 'file' => null, 'error' => 'حجم الملف صغير جداً. الحد الأدنى '. formatBytes($opt['min_size'])];
    }
    if ($file['size'] > $opt['max_size']) {
        return ['success' => false, 'file' => null, 'error' => 'حجم الملف كبير جداً. الحد الأقصى '. formatBytes($opt['max_size'])];
    }

    // 4. التحقق من النوع الحقيقي للملف MIME
    $finfo = finfo_open(FILEINFO_MIME_TYPE);
    $realMime = finfo_file($finfo, $file['tmp_name']);
    finfo_close($finfo);

    $allowedMimes = [
        'image' => ['image/jpeg', 'image/png', 'image/gif', 'image/webp', 'image/bmp'],
        'video' => ['video/mp4', 'video/webm', 'video/ogg', 'video/quicktime'],
        'audio' => ['audio/mpeg', 'audio/wav', 'audio/ogg', 'audio/mp3'],
        'file' => ['application/pdf', 'application/zip', 'text/plain',
                   'application/msword',
                   'application/vnd.openxmlformats-officedocument.wordprocessingml.document',
                   'application/vnd.ms-excel',
                   'application/vnd.openxmlformats-officedocument.spreadsheetml.sheet']
    ];

    if (!isset($allowedMimes[$opt['type']])) {
        return ['success' => false, 'file' => null, 'error' => 'نوع الملف المحدد غير مدعوم'];
    }

    if (!in_array($realMime, $allowedMimes[$opt['type']])) {
        return ['success' => false, 'file' => null, 'error' => "نوع الملف $realMime غير مسموح. المسموح: {$opt['type']}"];
    }

    // 5. تحقق إضافي للصور: هل هي صورة فعلاً + الأبعاد
    if ($opt['type'] === 'image') {
        $imgInfo = @getimagesize($file['tmp_name']);
        if ($imgInfo === false) {
            return ['success' => false, 'file' => null, 'error' => 'الملف ليس صورة صالحة'];
        }

        $width = $imgInfo[0];
        $height = $imgInfo[1];

        if (isset($opt['min_width']) && $width < $opt['min_width']) {
            return ['success' => false, 'file' => null, 'error' => "عرض الصورة صغير. الحد الأدنى {$opt['min_width']}px"];
        }
        if (isset($opt['max_width']) && $width > $opt['max_width']) {
            return ['success' => false, 'file' => null, 'error' => "عرض الصورة كبير. الحد الأقصى {$opt['max_width']}px"];
        }
    }

    // 6. فحص الثغرات والفيروسات
    if ($opt['scan_file']) {
        // 6.1 فحص PHP/Shell في الصور
        $content = file_get_contents($file['tmp_name']);
        if (preg_match('/<\?php|<\?=|<\?|<script|eval\(|base64_decode|shell_exec|system\(/i', $content)) {
            return ['success' => false, 'file' => null, 'error' => 'الملف يحتوي على كود مشبوه'];
        }

        // 6.2 فحص Double Extension: file.jpg.php
        $originalName = basename($file['name']);
        if (preg_match('/\.(php|phtml|php3|php4|php5|phar|pht)$/i', $originalName)) {
            return ['success' => false, 'file' => null, 'error' => 'امتداد الملف خطر'];
        }

        // 6.3 فحص Null Byte
        if (strpos($originalName, chr(0))!== false) {
            return ['success' => false, 'file' => null, 'error' => 'اسم الملف يحتوي على حروف غير صالحة'];
        }
    }

    // 7. تجهيز مسار الحفظ بالتاريخ
    $savePath = rtrim($opt['path'], '/'). '/';

    if ($opt['format_date']) {
        $year = date('Y');
        $month = date('m');
        $day = date('d');
        $savePath.= "$year/$month/$day/";
    }

    // 8. إنشاء المجلد لو مش موجود
    if (!is_dir($savePath)) {
        if (!mkdir($savePath, 0755, true)) {
            return ['success' => false, 'file' => null, 'error' => 'فشل إنشاء مجلد الحفظ'];
        }
    }

    // 9. توليد اسم آمن للملف
    $ext = strtolower(pathinfo($file['name'], PATHINFO_EXTENSION));
    $safeExt = preg_replace('/[^a-z0-9]/', '', $ext); // نشيل أي حاجة غير حروف وأرقام
    $newName = bin2hex(random_bytes(16)). '.'. $safeExt;
    $destination =  $savePath.  $newName;
    $namefile =  $newName;
    $pathsave = $savePath;
    

    // 10. نقل الملف
    if (!move_uploaded_file($file['tmp_name'], $destination)) {
        return ['success' => false, 'file' => null, 'error' => 'فشل حفظ الملف على السيرفر'];
    }

    // 11. تغيير صلاحيات الملف عشان ما يتنفذ
    chmod($destination, 0644);
 
    return ['success' => true, 'file' => $namefile,'path' => $savePath, 'error' => null];
}

function formatBytes($bytes) {
    if ($bytes >= 1048576) return round($bytes / 1048576, 2). ' MB';
    if ($bytes >= 1024) return round($bytes / 1024, 2). ' KB';
    return $bytes. ' B';
}
}
