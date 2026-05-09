<?php
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
require_once '../classes/filterValue.php';

session_start();

// Generate or validate CSRF token
if (empty($_SESSION['csrf_token'])) {
    $_SESSION['csrf_token'] = bin2hex(random_bytes(32));
}


if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    // Validate CSRF token
    if (empty($_POST['csrf_token']) || $_POST['csrf_token'] !== $_SESSION['csrf_token']) {
        $error = 'Security validation failed. Please try again.';
    } else {
        $rules = [
         'phone'=>'phone:required',
         'password'=>'password',
         'fullname'=>'fullname:required',
         'email'=>'email',
         'url'=>'url:required',
         'phone'=>'phone',
         'subject'=>'subject:required',
         'text'=>'text'
         
    ];
    $filter = new filterValue();
    $result = $filter->filterValue($_POST[],$rules);
        if(count($result['errors']) > 0){
        foreach($result['errors'] as  $error){
            $errors[] = $error; // ← array_push($errors, $error)
    echo $error ;
        }
    }else{
    	$success = "ok";
    	if($result){
    	
    	}
    	}
        }
    }
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>User  Information Fetcher</title>
    <style>
        * {
            margin: 0;
            padding: 0;
            box-sizing: border-box;
        }

        body {
            font-family: 'Segoe UI', Tahoma, Geneva, Verdana, sans-serif;
            background: linear-gradient(135deg, #667eea 0%, #764ba2 100%);
            min-height: 100vh;
            display: flex;
            justify-content: center;
            align-items: center;
            padding: 20px;
        }

        .container {
            background: white;
            border-radius: 15px;
            box-shadow: 0 20px 60px rgba(0, 0, 0, 0.3);
            max-width: 800px;
            width: 100%;
            padding: 40px;
        }

        h1 {
            color: #333;
            margin-bottom: 10px;
            font-size: 28px;
        }

        .subtitle {
            color: #666;
            margin-bottom: 30px;
            font-size: 14px;
        }

        .form-group {
            margin-bottom: 20px;
        }

        label {
            display: block;
            margin-bottom: 8px;
            color: #333;
            font-weight: 600;
            font-size: 14px;
        }

        input[type="text"],
        input[type="password"] {
            width: 100%;
            padding: 12px;
            border: 2px solid #e0e0e0;
            border-radius: 8px;
            font-size: 14px;
            transition: border-color 0.3s;
            font-family: inherit;
        }

        input[type="text"]:focus,
        input[type="password"]:focus {
            outline: none;
            border-color: #667eea;
        }

        .help-text {
            font-size: 12px;
            color: #999;
            margin-top: 5px;
        }

        button {
            width: 100%;
            padding: 12px;
            background: linear-gradient(135deg, #667eea 0%, #764ba2 100%);
            color: white;
            border: none;
            border-radius: 8px;
            font-size: 16px;
            font-weight: 600;
            cursor: pointer;
            transition: transform 0.2s, box-shadow 0.2s;
        }

        button:hover {
            transform: translateY(-2px);
            box-shadow: 0 10px 20px rgba(102, 126, 234, 0.3);
        }

        button:active {
            transform: translateY(0);
        }

        .alert {
            padding: 15px;
            border-radius: 8px;
            margin-bottom: 20px;
            font-size: 14px;
            display: flex;
            align-items: center;
            gap: 10px;
        }

        .alert-error {
            background-color: #fee;
            color: #c33;
            border-left: 4px solid #c33;
        }

        .alert-success {
            background-color: #efe;
            color: #3c3;
            border-left: 4px solid #3c3;
        }

        .video-result {
            margin-top: 30px;
            padding-top: 30px;
            border-top: 2px solid #e0e0e0;
        }

        .video-thumbnail {
            width: 100%;
            border-radius: 8px;
            margin-bottom: 20px;
            box-shadow: 0 5px 15px rgba(0, 0, 0, 0.1);
        }

        .video-title {
            font-size: 24px;
            color: #333;
            margin-bottom: 15px;
            line-height: 1.4;
        }

        .video-stats {
            display: grid;
            grid-template-columns: repeat(auto-fit, minmax(150px, 1fr));
            gap: 15px;
            margin-bottom: 20px;
        }

        .stat-item {
            background: #f5f5f5;
            padding: 15px;
            border-radius: 8px;
            text-align: center;
        }

        .stat-label {
            font-size: 12px;
            color: #999;
            text-transform: uppercase;
            margin-bottom: 5px;
        }

        .stat-value {
            font-size: 18px;
            color: #333;
            font-weight: 600;
        }

        .video-description {
            background: #f5f5f5;
            padding: 15px;
            border-radius: 8px;
            margin-bottom: 20px;
            line-height: 1.6;
            color: #555;
            max-height: 200px;
            overflow-y: auto;
        }

        .video-keywords {
            display: flex;
            flex-wrap: wrap;
            gap: 10px;
            margin-top: 15px;
        }

        .keyword-tag {
            background: linear-gradient(135deg, #667eea 0%, #764ba2 100%);
            color: white;
            padding: 6px 12px;
            border-radius: 20px;
            font-size: 12px;
        }

        .channel-info {
            background: #f5f5f5;
            padding: 15px;
            border-radius: 8px;
            margin-top: 15px;
        }

        .channel-label {
            font-size: 12px;
            color: #999;
            text-transform: uppercase;
            margin-bottom: 5px;
        }

        .channel-name {
            font-size: 16px;
            color: #333;
            font-weight: 600;
        }

        @media (max-width: 600px) {
            .container {
                padding: 20px;
            }

            h1 {
                font-size: 22px;
            }

            .video-title {
                font-size: 20px;
            }

            .video-stats {
                grid-template-columns: repeat(2, 1fr);
            }
        }
    </style>
</head>
<body>
    <div class="container">
        <h1>user Information</h1>
        <p class="subtitle">Get detailed information about any YouTube video</p>

        <?php if ($error): ?>
            <div class="alert alert-error">
                <span>X</span>
                <span><?php echo htmlspecialchars($error); ?></span>
            </div>
        <?php endif; ?>

        <?php if ($success): ?>
            <div class="alert alert-success">
                <span>✓</span>
                <span>Video information loaded successfully</span>
            </div>
        <?php endif; ?>

        <form method="POST">
            <input type="hidden" name="csrf_token" value="<?php echo htmlspecialchars($_SESSION['csrf_token']); ?>">
            <div class="form-group">
                <label for="video_url">full name </label>
                <input 
                    type="text" 
                    id="l" 
                    name="fullname" 
                    placeholder="fullname"
                    value="<?php echo htmlspecialchars($_POST['fullname'] ?? ''); ?>"
                >
                <div class="help-text">information name</div>
            </div>
            <div class="form-group">
                <label for="username">username</label>
                <input 
                    type="text" 
                    id="api_key" 
                    name="api_key" 
                    placeholder="Enter username"
                    value="<?php echo htmlspecialchars($_POST['username'] ?? ''); ?>"
                >
                <div class="help-text">username</div>
            </div>

            <div class="form-group">
                <label for="video_url">email </label>
                <input 
                    type="text" 
                    id="l" 
                    name="email" 
                    placeholder="email"
                    value="<?php echo htmlspecialchars($_POST['email'] ?? ''); ?>"
                >
                <div class="help-text">information </div>
            </div>
            <div class="form-group">
                <label for="video_url">phone </label>
                <input 
                    type="number" 
                    id="l" 
                    name="phone" 
                    placeholder="phone"
                    value="<?php echo htmlspecialchars($_POST['phone'] ?? ''); ?>"
                >
                <div class="help-text">phone </div>
            </div>
            <div class="form-group">
                <label for="video_url">email </label>
                <input 
                    type="text" 
                    id="l" 
                    name="subject" 
                    placeholder="subject"
                    value="<?php echo htmlspecialchars($_POST['subjectSystem.out.println(x)'] ?? ''); ?>"
                >
                <div class="help-text">information </div>
            </div>
            <div class="form-group">
                <label for="video_url">text</label>
                <input 
                    type="text" 
                    id="l" 
                    name="text" 
                    placeholder="text"
                    value="<?php echo htmlspecialchars($_POST['text'] ?? ''); ?>"
                >
                <div class="help-text">information </div>
            </div>
            <div class="form-group">
                <label for="video_url">url</label>
                <input 
                    type="url" 
                    id="l" 
                    name="url" 
                    placeholder="url"
                    value="<?php echo htmlspecialchars($_POST['url'] ?? ''); ?>"
                >
                <div class="help-text">information url</div>
            </div>
            <div class="form-group">
                <label for="video_url">password  </label>
                <input 
                    type="text" 
                    id="l" 
                    name="password" 
                    placeholder="password"
                    value="<?php echo htmlspecialchars($_POST['password'] ?? ''); ?>"
                >
                <div class="help-text">information </div>
            </div>
            <div class="form-group">
                <label for="video_url">image </label>
                <input 
                    type="file" 
                    id="l" 
                    name="image" 
                    placeholder="image"
                    value="<?php echo htmlspecialchars($_POST['imagr'] ?? ''); ?>"
                >
                <div class="help-text">information </div>
            </div>

            <button type="submit">Fetch users Information</button>
        </form>

        <?php if ($result): ?>
            <div class="video-result">
                <img src="" alt="Thumbnail" class="video-thumbnail">

                <h2 class="video-title"><?php echo $result['data']['fullname']; ?></h2>

                <div class="video-stats">
                    <div class="stat-item">
                        <div class="stat-label">Views</div>
                        <div class="stat-value"><?php echo  $result['data']['password']; ?></div>
                    </div>
                    <div class="stat-item">
                        <div class="stat-label">Likes</div>
                        <div class="stat-value"><?php echo $result['data']['phone']; ?></div>
                    </div>
                    <div class="stat-item">
                        <div class="stat-label">Duration</div>
                        <div class="stat-value"><?php echo $result['data']['email'];?></div>
                    </div>
                </div>

                <div class="channel-info">
                    <div class="channel-label">Published on</div>
                    <div class="channel-name"><?php echo ?></div>
                </div>

                <div class="channel-info">
                    
                    
                </div>

          

                <div class="channel-info" style="margin-top: 20px;">
                    <div class="channel-label">Video ID</div>
                    <div class="channel-name" style="font-family: monospace; font-size: 14px;"><?php echo $videoData['video_id']; ?></div>
                </div>
            </div>
        <?php endif; ?>
    </div>
</body>
</html>
