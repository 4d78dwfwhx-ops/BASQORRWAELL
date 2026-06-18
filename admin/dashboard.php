<?php
require_once '../api/config.php';

// التحقق من صلاحيات المسؤول
if (!isset($_SESSION['user_id']) || $_SESSION['role'] !== 'admin') {
    header('Location: /login');
    exit;
}

// إحصائيات سريعة
$stats = [];

// عدد الطلبات اليوم
$stmt = $pdo->query("SELECT COUNT(*) as count FROM orders WHERE DATE(created_at) = CURDATE()");
$stats['orders_today'] = $stmt->fetch()['count'];

// الإيرادات اليوم
$stmt = $pdo->query("SELECT SUM(total_amount) as total FROM orders WHERE DATE(created_at) = CURDATE()");
$stats['revenue_today'] = $stmt->fetch()['total'] ?? 0;

// عدد المستخدمين
$stmt = $pdo->query("SELECT COUNT(*) as count FROM users");
$stats['total_users'] = $stmt->fetch()['count'];

// عدد المنتجات
$stmt = $pdo->query("SELECT COUNT(*) as count FROM products");
$stats['total_products'] = $stmt->fetch()['count'];

// المنتجات منخفضة المخزون
$stmt = $pdo->query("SELECT * FROM products WHERE stock_quantity < 10 ORDER BY stock_quantity ASC LIMIT 5");
$stats['low_stock'] = $stmt->fetchAll();

// أحدث الطلبات
$stmt = $pdo->query("SELECT o.*, u.first_name, u.last_name FROM orders o JOIN users u ON o.user_id = u.id ORDER BY o.created_at DESC LIMIT 10");
$stats['recent_orders'] = $stmt->fetchAll();
?>

<!DOCTYPE html>
<html lang="ar" dir="rtl">
<head>
    <meta charset="UTF-8">
    <title>لوحة التحكم - باسكور</title>
    <link rel="stylesheet" href="../css/admin.css">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css">
</head>
<body>
    <div class="admin-layout">
        <!-- القائمة الجانبية -->
        <aside class="admin-sidebar">
            <div class="sidebar-header">
                <h2>باسكور</h2>
                <p>لوحة التحكم</p>
            </div>
            <nav class="sidebar-nav">
                <a href="dashboard.php" class="active"><i class="fas fa-home"></i> الرئيسية</a>
                <a href="orders.php"><i class="fas fa-shopping-bag"></i> الطلبات</a>
                <a href="products.php"><i class="fas fa-box"></i> المنتجات</a>
                <a href="categories.php"><i class="fas fa-tags"></i> الأقسام</a>
                <a href="users.php"><i class="fas fa-users"></i> المستخدمون</a>
                <a href="reports.php"><i class="fas fa-chart-line"></i> التقارير</a>
                <a href="settings.php"><i class="fas fa-cog"></i> الإعدادات</a>
            </nav>
        </aside>

        <!-- المحتوى الرئيسي -->
        <main class="admin-main">
            <header class="admin-header">
                <h1>لوحة التحكم</h1>
                <div class="user-menu">
                    <span>مرحباً، <?= $_SESSION['user_name'] ?></span>
                    <a href="/logout"><i class="fas fa-sign-out-alt"></i></a>
                </div>
            </header>

            <!-- بطاقات الإحصائيات -->
            <div class="stats-grid">
                <div class="stat-card">
                    <div class="stat-icon orders"><i class="fas fa-shopping-bag"></i></div>
                    <div class="stat-info">
                        <h3><?= $stats['orders_today'] ?></h3>
                        <p>طلبات اليوم</p>
                    </div>
                </div>
                <div class="stat-card">
                    <div class="stat-icon revenue"><i class="fas fa-dollar-sign"></i></div>
                    <div class="stat-info">
                        <h3><?= number_format($stats['revenue_today'], 2) ?> ر.س</h3>
                        <p>إيرادات اليوم</p>
                    </div>
                </div>
                <div class="stat-card">
                    <div class="stat-icon users"><i class="fas fa-users"></i></div>
                    <div class="stat-info">
                        <h3><?= $stats['total_users'] ?></h3>
                        <p>إجمالي المستخدمين</p>
                    </div>
                </div>
                <div class="stat-card">
                    <div class="stat-icon products"><i class="fas fa-box"></i></div>
                    <div class="stat-info">
                        <h3><?= $stats['total_products'] ?></h3>
                        <p>إجمالي المنتجات</p>
                    </div>
                </div>
            </div>

            <!-- أحدث الطلبات -->
            <div class="admin-section">
                <h2>أحدث الطلبات</h2>
                <table class="admin-table">
                    <thead>
                        <tr>
                            <th>رقم الطلب</th>
                            <th>العميل</th>
                            <th>المبلغ</th>
                            <th>الحالة</th>
                            <th>التاريخ</th>
                            <th>إجراءات</th>
                        </tr>
                    </thead>
                    <tbody>
                        <?php foreach ($stats['recent_orders'] as $order): ?>
                        <tr>
                            <td><?= $order['order_number'] ?></td>
                            <td><?= $order['first_name'] . ' ' . $order['last_name'] ?></td>
                            <td><?= number_format($order['total_amount'], 2) ?> ر.س</td>
                            <td>
                                <span class="status-badge <?= $order['status'] ?>">
                                    <?= $order['status'] ?>
                                </span>
                            </td>
                            <td><?= date('Y-m-d H:i', strtotime($order['created_at'])) ?></td>
                            <td>
                                <a href="order-detail.php?id=<?= $order['id'] ?>" class="btn-view">
                                    <i class="fas fa-eye"></i>
                                </a>
                            </td>
                        </tr>
                        <?php endforeach; ?>
                    </tbody>
                </table>
            </div>

            <!-- المنتجات منخفضة المخزون -->
            <div class="admin-section">
                <h2>تنبيه: منتجات منخفضة المخزون</h2>
                <table class="admin-table">
                    <thead>
                        <tr>
                            <th>المنتج</th>
                            <th>SKU</th>
                            <th>الكمية المتبقية</th>
                            <th>إجراءات</th>
                        </tr>
                    </thead>
                    <tbody>
                        <?php foreach ($stats['low_stock'] as $product): ?>
                        <tr>
                            <td><?= $product['name_ar'] ?></td>
                            <td><?= $product['sku'] ?></td>
                            <td>
                                <span class="stock-warning"><?= $product['stock_quantity'] ?></span>
                            </td>
                            <td>
                                <a href="product-edit.php?id=<?= $product['id'] ?>" class="btn-edit">
                                    <i class="fas fa-edit"></i> تعديل
                                </a>
                            </td>
                        </tr>
                        <?php endforeach; ?>
                    </tbody>
                </table>
            </div>
        </main>
    </div>
</body>
</html>
