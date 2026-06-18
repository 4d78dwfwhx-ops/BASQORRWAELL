<?php
// معالج تثبيت المنصة
$step = $_GET['step'] ?? 1;
$error = '';
$success = '';

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    if ($step == 1) {
        // التحقق من قاعدة البيانات
        try {
            $pdo = new PDO(
                "mysql:host={$_POST['db_host']};charset=utf8mb4",
                $_POST['db_user'],
                $_POST['db_pass']
            );
            
            // إنشاء قاعدة البيانات
            $pdo->exec("CREATE DATABASE IF NOT EXISTS {$_POST['db_name']} CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci");
            
            // حفظ الإعدادات
            $config = "<?php
define('DB_HOST', '{$_POST['db_host']}');
define('DB_USER', '{$_POST['db_user']}');
define('DB_PASS', '{$_POST['db_pass']}');
define('DB_NAME', '{$_POST['db_name']}');
?>";
            file_put_contents('api/config.php', $config);
            
            header('Location: install.php?step=2');
            exit;
            
        } catch (Exception $e) {
            $error = 'خطأ في الاتصال: ' . $e->getMessage();
        }
    }
    
    if ($step == 2) {
        // إنشاء حساب المدير
        require_once 'api/config.php';
        
        try {
            // إنشاء الجداول
            $sql = file_get_contents('database/schema.sql');
            $pdo->exec($sql);
            
            // إنشاء حساب المدير
            $passwordHash = password_hash($_POST['admin_pass'], PASSWORD_DEFAULT);
            $stmt = $pdo->prepare("
                INSERT INTO users (email, password_hash, first_name, last_name, role) 
                VALUES (?, ?, ?, ?, 'admin')
            ");
            $stmt->execute([
                $_POST['admin_email'],
                $passwordHash,
                'مدير',
                'النظام'
            ]);
            
            // إضافة بيانات تجريبية
            $sampleData = file_get_contents('database/sample_data.sql');
            $pdo->exec($sampleData);
            
            header('Location: install.php?step=3');
            exit;
            
        } catch (Exception $e) {
            $error = 'خطأ: ' . $e->getMessage();
        }
    }
}
?>

<!DOCTYPE html>
<html lang="ar" dir="rtl">
<head>
    <meta charset="UTF-8">
    <title>تثبيت منصة باسكور</title>
    <style>
        body { font-family: 'Tajawal', Arial; background: #f5f5f5; direction: rtl; }
        .install-box { max-width: 600px; margin: 50px auto; background: white; padding: 40px; border-radius: 10px; box-shadow: 0 2px 20px rgba(0,0,0,0.1); }
        h1 { color: #e74c3c; text-align: center; }
        .steps { display: flex; justify-content: space-between; margin-bottom: 30px; }
        .step { flex: 1; text-align: center; padding: 10px; background: #ecf0f1; }
        .step.active { background: #e74c3c; color: white; }
        .step.done { background: #27ae60; color: white; }
        .form-group { margin-bottom: 20px; }
        label { display: block; margin-bottom: 5px; font-weight: 600; }
        input { width: 100%; padding: 10px; border: 1px solid #ddd; border-radius: 5px; }
        button { width: 100%; padding: 12px; background: #e74c3c; color: white; border: none; border-radius: 5px; font-size: 16px; cursor: pointer; }
        .error { background: #f8d7da; color: #721c24; padding: 10px; border-radius: 5px; margin-bottom: 20px; }
        .success { background: #d4edda; color: #155724; padding: 10px; border-radius: 5px; margin-bottom: 20px; }
    </style>
</head>
<body>
    <div class="install-box">
        <h1>🚗 تثبيت منصة باسكور</h1>
        
        <div class="steps">
            <div class="step <?= $step > 1 ? 'done' : ($step == 1 ? 'active' : '') ?>">1. قاعدة البيانات</div>
            <div class="step <?= $step > 2 ? 'done' : ($step == 2 ? 'active' : '') ?>">2. حساب المدير</div>
            <div class="step <?= $step == 3 ? 'active' : '' ?>">3. الانتهاء</div>
        </div>
        
        <?php if ($error): ?>
            <div class="error"><?= $error ?></div>
        <?php endif; ?>
        
        <?php if ($step == 1): ?>
            <form method="POST">
                <h3>إعدادات قاعدة البيانات</h3>
                <div class="form-group">
                    <label>مضيف قاعدة البيانات</label>
                    <input type="text" name="db_host" value="localhost" required>
                </div>
                <div class="form-group">
                    <label>اسم المستخدم</label>
                    <input type="text" name="db_user" value="root" required>
                </div>
                <div class="form-group">
                    <label>كلمة المرور</label>
                    <input type="password" name="db_pass">
                </div>
                <div class="form-group">
                    <label>اسم قاعدة البيانات</label>
                    <input type="text" name="db_name" value="basqor_db" required>
                </div>
                <button type="submit">التالي</button>
            </form>
            
        <?php elseif ($step == 2): ?>
            <form method="POST">
                <h3>إنشاء حساب المدير</h3>
                <div class="form-group">
                    <label>البريد الإلكتروني</label>
                    <input type="email" name="admin_email" required>
                </div>
                <div class="form-group">
                    <label>كلمة المرور</label>
                    <input type="password" name="admin_pass" required minlength="8">
                </div>
                <button type="submit">إنشاء الحساب وتثبيت المنصة</button>
            </form>
            
        <?php elseif ($step == 3): ?>
            <div class="success">
                <h3>🎉 تم تثبيت المنصة بنجاح!</h3>
                <p>يمكنك الآن:</p>
                <ul>
                    <li><a href="/admin">الدخول إلى لوحة التحكم</a></li>
                    <li><a href="/">زيارة الصفحة الرئيسية</a></li>
                </ul>
                <p><strong>مهم:</strong> احذف ملف install.php من الخادم لأسباب أمنية.</p>
            </div>
        <?php endif; ?>
    </div>
</body>
</html>
