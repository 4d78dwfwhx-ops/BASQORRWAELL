<?php
require_once 'config.php';

header('Content-Type: application/json; charset=utf-8');

$action = $_GET['action'] ?? 'list';

switch ($action) {
    case 'list':
        // جلب قائمة المنتجات
        $category = $_GET['category'] ?? null;
        $brand = $_GET['brand'] ?? null;
        $search = $_GET['search'] ?? null;
        $page = (int)($_GET['page'] ?? 1);
        $limit = 20;
        $offset = ($page - 1) * $limit;
        
        $sql = "SELECT p.*, c.name_ar as category_name 
                FROM products p 
                LEFT JOIN categories c ON p.category_id = c.id 
                WHERE 1=1";
        $params = [];
        
        if ($category) {
            $sql .= " AND p.category_id = ?";
            $params[] = $category;
        }
        
        if ($brand) {
            $sql .= " AND p.brand_name = ?";
            $params[] = $brand;
        }
        
        if ($search) {
            $sql .= " AND (p.name_ar LIKE ? OR p.sku LIKE ?)";
            $params[] = "%$search%";
            $params[] = "%$search%";
        }
        
        $sql .= " ORDER BY p.created_at DESC LIMIT ? OFFSET ?";
        $params[] = $limit;
        $params[] = $offset;
        
        $stmt = $pdo->prepare($sql);
        $stmt->execute($params);
        $products = $stmt->fetchAll();
        
        // جلب العدد الإجمالي
        $countSql = "SELECT COUNT(*) as total FROM products p WHERE 1=1";
        $countParams = [];
        if ($category) { $countSql .= " AND p.category_id = ?"; $countParams[] = $category; }
        if ($brand) { $countSql .= " AND p.brand_name = ?"; $countParams[] = $brand; }
        if ($search) { $countSql .= " AND (p.name_ar LIKE ? OR p.sku LIKE ?)"; $countParams[] = "%$search%"; $countParams[] = "%$search%"; }
        
        $countStmt = $pdo->prepare($countSql);
        $countStmt->execute($countParams);
        $total = $countStmt->fetch()['total'];
        
        jsonResponse([
            'success' => true,
            'products' => $products,
            'pagination' => [
                'page' => $page,
                'limit' => $limit,
                'total' => $total,
                'pages' => ceil($total / $limit)
            ]
        ]);
        break;
        
    case 'detail':
        // جلب تفاصيل منتج
        $id = (int)($_GET['id'] ?? 0);
        $stmt = $pdo->prepare("SELECT * FROM products WHERE id = ?");
        $stmt->execute([$id]);
        $product = $stmt->fetch();
        
        if (!$product) {
            jsonResponse(['success' => false, 'message' => 'المنتج غير موجود'], 404);
        }
        
        // جلب السيارات المتوافقة
        $compatStmt = $pdo->prepare("
            SELECT b.name_ar as brand, m.name_ar as model, e.name as engine
            FROM product_compatibility pc
            JOIN brands b ON pc.brand_id = b.id
            JOIN models m ON pc.model_id = m.id
            LEFT JOIN engines e ON pc.engine_id = e.id
            WHERE pc.product_id = ?
        ");
        $compatStmt->execute([$id]);
        $product['compatibility'] = $compatStmt->fetchAll();
        
        // جلب التقييمات
        $reviewStmt = $pdo->prepare("
            SELECT r.*, u.first_name 
            FROM reviews r 
            JOIN users u ON r.user_id = u.id 
            WHERE r.product_id = ? 
            ORDER BY r.created_at DESC 
            LIMIT 10
        ");
        $reviewStmt->execute([$id]);
        $product['reviews'] = $reviewStmt->fetchAll();
        
        jsonResponse(['success' => true, 'product' => $product]);
        break;
        
    case 'search_by_vehicle':
        // البحث حسب السيارة
        $brandId = (int)($_GET['brand_id'] ?? 0);
        $modelId = (int)($_GET['model_id'] ?? 0);
        $engineId = (int)($_GET['engine_id'] ?? 0);
        
        $sql = "SELECT DISTINCT p.* FROM products p 
                JOIN product_compatibility pc ON p.id = pc.product_id 
                WHERE pc.brand_id = ?";
        $params = [$brandId];
        
        if ($modelId) {
            $sql .= " AND pc.model_id = ?";
            $params[] = $modelId;
        }
        
        if ($engineId) {
            $sql .= " AND pc.engine_id = ?";
            $params[] = $engineId;
        }
        
        $stmt = $pdo->prepare($sql);
        $stmt->execute($params);
        $products = $stmt->fetchAll();
        
        jsonResponse(['success' => true, 'products' => $products]);
        break;
        
    default:
        jsonResponse(['success' => false, 'message' => 'عملية غير صالحة'], 400);
}
?>
