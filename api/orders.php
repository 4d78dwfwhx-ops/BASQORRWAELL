<?php
require_once 'config.php';

$action = $_POST['action'] ?? $_GET['action'] ?? 'create';

switch ($action) {
    case 'create':
        // التحقق من تسجيل الدخول
        if (!isset($_SESSION['user_id'])) {
            jsonResponse(['success' => false, 'message' => 'يجب تسجيل الدخول'], 401);
        }
        
        $cart = $_SESSION['cart'] ?? [];
        if (empty($cart)) {
            jsonResponse(['success' => false, 'message' => 'السلة فارغة'], 400);
        }
        
        // حساب الإجمالي
        $total = 0;
        $items = [];
        
        foreach ($cart as $productId => $quantity) {
            $stmt = $pdo->prepare("SELECT * FROM products WHERE id = ?");
            $stmt->execute([$productId]);
            $product = $stmt->fetch();
            
            if (!$product || $product['stock_quantity'] < $quantity) {
                jsonResponse([
                    'success' => false, 
                    'message' => "المنتج {$product['name_ar']} غير متوفر بالكمية المطلوبة"
                ], 400);
            }
            
            $price = $product['discount_price'] ?: $product['price'];
            $total += $price * $quantity;
            $items[] = [
                'product_id' => $productId,
                'quantity' => $quantity,
                'price' => $price
            ];
        }
        
        $tax = $total * 0.15;
        $grandTotal = $total + $tax;
        
        try {
            $pdo->beginTransaction();
            
            // إنشاء الطلب
            $orderNumber = 'BSQ-' . date('Ymd') . '-' . rand(1000, 9999);
            $stmt = $pdo->prepare("
                INSERT INTO orders (user_id, order_number, total_amount, shipping_address, status) 
                VALUES (?, ?, ?, ?, 'pending')
            ");
            $stmt->execute([
                $_SESSION['user_id'],
                $orderNumber,
                $grandTotal,
                $_POST['shipping_address'] ?? ''
            ]);
            $orderId = $pdo->lastInsertId();
            
            // إضافة عناصر الطلب
            $stmt = $pdo->prepare("
                INSERT INTO order_items (order_id, product_id, quantity, unit_price) 
                VALUES (?, ?, ?, ?)
            ");
            
            foreach ($items as $item) {
                $stmt->execute([
                    $orderId,
                    $item['product_id'],
                    $item['quantity'],
                    $item['price']
                ]);
                
                // تحديث المخزون
                $pdo->prepare("UPDATE products SET stock_quantity = stock_quantity - ? WHERE id = ?")
                    ->execute([$item['quantity'], $item['product_id']]);
            }
            
            $pdo->commit();
            
            // إفراغ السلة
            $_SESSION['cart'] = [];
            
            jsonResponse([
                'success' => true,
                'message' => 'تم إنشاء الطلب بنجاح',
                'order_number' => $orderNumber,
                'total' => round($grandTotal, 2)
            ]);
            
        } catch (Exception $e) {
            $pdo->rollBack();
            jsonResponse(['success' => false, 'message' => 'حدث خطأ: ' . $e->getMessage()], 500);
        }
        break;
        
    case 'track':
        $orderNumber = $_GET['order_number'] ?? '';
        $stmt = $pdo->prepare("SELECT * FROM orders WHERE order_number = ?");
        $stmt->execute([$orderNumber]);
        $order = $stmt->fetch();
        
        if (!$order) {
            jsonResponse(['success' => false, 'message' => 'الطلب غير موجود'], 404);
        }
        
        // جلب عناصر الطلب
        $stmt = $pdo->prepare("
            SELECT oi.*, p.name_ar, p.image_url 
            FROM order_items oi 
            JOIN products p ON oi.product_id = p.id 
            WHERE oi.order_id = ?
        ");
        $stmt->execute([$order['id']]);
        $order['items'] = $stmt->fetchAll();
        
        jsonResponse(['success' => true, 'order' => $order]);
        break;
        
    case 'history':
        if (!isset($_SESSION['user_id'])) {
            jsonResponse(['success' => false, 'message' => 'يجب تسجيل الدخول'], 401);
        }
        
        $stmt = $pdo->prepare("
            SELECT * FROM orders 
            WHERE user_id = ? 
            ORDER BY created_at DESC
        ");
        $stmt->execute([$_SESSION['user_id']]);
        $orders = $stmt->fetchAll();
        
        jsonResponse(['success' => true, 'orders' => $orders]);
        break;
}
?>
