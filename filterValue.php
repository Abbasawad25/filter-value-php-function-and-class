<?php
 function filterValue($value,$type,$options = []){
 	$required = $options['required']  ?? false;
    if($required && (empty($value) || ($type==='file' && $value['error']===UPLOAD_ERR_NO_FILE))){
        $errors[] = "الحقل $field_name مطلوب";
        return null;
    }
    
    if(!$required && (empty($value) || ($type==='file' && $value['error']===UPLOAD_ERR_NO_FILE))){
        return null;
    }
    if(!$type == "file" || is_string($value)){
    	$value = trim($value);
        $value = str_replace(chr(0),"",$value);
       
    	}
    
                
                
 	switch($type){
 	case "fullname":
      $value = strip_tags($value);
      $value = preg_replace('/[^\p{L}\s]/u', '', $value);
                $value = preg_replace('/\s+/', ' ', trim($value));
                $words = explode(' ', $value);

                if(count($words) < 2){
                    echo 'Full name must contain at least 2 words';
                    return null;
                }
                if(mb_strlen($words[0]) < 2 || mb_strlen($words[1]) < 2){
                    echo  'Each word must be at least 2 characters';
                    return null;
                }
                $value = implode(" ",$words);

                return $value;
     case "username":
     $value = strip_tags($value);
     
      $min  = $opt['min']  ?? 3;
      $max  = $opt['max']  ?? 23;
      if(!preg_match("/^[a-zA-Z0-9]+/",$value)){
      	echo "English ";
        return null;
      $value = str_replace(" ","",$value);
      $value = strtolower($value);
      	}
      if(mb_strlen($value) < $min || mb_strlen($value) > $max){
                echo   "اسم المستخدم يجب أن يكون بين $min و $max حرف";
                return null;
            }
      return $value;
      brake;
     case "email":
     $allowed_domains = $options['allowed_domains'] ?? []; // القائمة البيضاء
     $blocked_domains = $options['blocked_domains'] ?? ['mailinator.com', '10minutemail.com'];
     $value = strip_tags($value);
      $value = filter_var($value, FILTER_SANITIZE_EMAIL);
                if(!filter_var($value, FILTER_VALIDATE_EMAIL)){
                	 echo "غير ";
                    return null;
                }
                if(preg_match('/[\r\n]/', $value)){
                    echo "Email contains invalid characters";
                    return null;
                }
                $domain = substr(strrchr($value, "@"), 1);
                if (!empty($allowed_domains) && !in_array($domain, $allowed_domains)) {
                return ['value' => null, 'error' => "نطاق البريد الإلكتروني غير مسموح به"];
            }
             if (in_array($domain, $blocked_domains)) {
                return ['value' => null, 'error' => "هذا النطاق محظور (بريد مؤقت)"];
            }
            if (!checkdnsrr($domain, "MX")) {
                return ['value' => null, 'error' => "نطاق البريد الإلكتروني غير صالح أو غير موجود"];
            }
                
                
                
       return $value;
     case "password":
     $value = strip_tags($value);
      $min = $opt['min'] ?? 8;
      $max = $opt['max'] ?? 14;
      if(strlen($value) < $min || strlen($value) > $max){
      	echo "يجب ان تكون كلمة بين $min $max ";
      return null;
      	}
      if(!preg_match("/[A-Z]/",$value)){
      	echo "حرف كبير علي الاقل";
      return null;
      	}
      if(!preg_match("/[a-z]/",$value)){
      	echo "حرف صغير";
      return null;
      	}
      if(!preg_match("/[0-9]/",$value)){
      	echo "رقم";
      return null;
      	}
      if(!preg_match("/[^a-zA-Z0-9]/",$value)){
      	echo "رمز";
      	return null;
      	}
      
      $value = md5($value);
      return $value;
      brake;
     case "text":
           $min = $options['min'] ?? 0;
            $max = $options['max'] ?? 1000;
            $value = htmlspecialchars($value, ENT_QUOTES | ENT_HTML5, 'UTF-8');  
            if(mb_strlen($value) < $min || mb_strlen($value) > $max){
                $errors[] = "النص يجب أن يكون بين $min و $max حرف";
                return null;
            }
            return $value;
     case "subject":
            $allowed = $options['allowed'] ?? '<p><br><b><strong><i><em><u><h1><h2><h3><ul><ol><li><a><img><blockquote><code><pre>';
            $value = strip_tags($value, $allowed);
            $value = preg_replace('/on\w+\s*=\s*"[^"]*"/i', '', $value);
            $value = preg_replace('/href\s*=\s*["\']javascript:[^"\']*["\']/i', 'href="#"', $value);
            $value = preg_replace('/<\?php.*?\?>/is', '', $value);
            $value = preg_replace('/<script\b[^>]*>(.*?)<\/script>/is', '', $value);
            return $value;
     case 'slug':
            $value = preg_replace('/[^\p{Arabic}\p{L}\p{N}\s\-]/u', '', $value);
            $value = preg_replace('/\s+/', '-', trim($value));
            $value = strtolower($value);
            if(empty($value)){
                $errors[] = "الـ slug غير صالح";
                return null;
            }
            return $value;
     
     
     default:
                return ['value'=>htmlspecialchars($value,ENT_QUOTES),'error'=>null];
     
 	}
 	
 	}
 
/*
 * رفع ملف بشكل آمن مع كل الخيارات
 *
 * @param array $file $_FILES['input_name']
 * @param array $options [
 * 'required' => true/false, // هل الحقل إجباري
 * 'type' => 'image|video|audio|file', // نوع الملف المسموح
 * 'path' => 'uploads/', // مسار الحفظ الأساسي
 * 'min_size' => 1024, // الحد الأدنى بالبايت
 * 'max_size' => 5242880, // الحد الأقصى 5MB
 * 'format_date' => true, // يعمل مجلدات 2026/05/03
 * 'min_width' => 100, // للصور فقط
 * 'max_width' => 4000, // للصور فقط
 * 'scan_file' => true // فحص الملف من الثغرات
 * ]
 * @return array ['success'=>bool, 'file'=>string|null, 'error'=>string|null]
 */
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

 

?>