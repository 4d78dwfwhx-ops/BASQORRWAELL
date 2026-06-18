<?php
require_once 'config.php';

$action = $_POST['action'] ?? $_GET['action'] ?? 'view';

// تهيئة السلة في الجلسة
if (!isset($_SESSION['cart'])) {
    $_SESSION['cart'] = [];
}

switch ($action) {
    case 'view':
        $cart = $_SESSION['cart'];
        $total = 0;
        $items = [];
        
        foreach ($cart as $productId => $quantity) {
            $stmt = $pdo->prepare("SELECT * FROM products WHERE id = ?");
            $stmt->execute([$productId]);
            $product = $stmt->fetch();
            
            if ($product) {
                $price = $product['discount_price'] ?: $product['price'];
                $subtotal = $price * $quantity;
                $total += $subtotal;
                
                $items[] = [
                    'id' => $product['id'],
                    'name' => $product['name_ar'],
                    'image' => $product['image_url'],
                    'price' => $price,
                    'quantity' => $quantity,
                    'subtotal' => $subtotal
                ];
            }
        }
        
        $tax = $total * 0.15;
        $grandTotal = $total + $tax;
        
        jsonResponse([
            'success' => true,
            'items' => $items,
            'subtotal' => round($total, 2),
            'tax' => round($tax, 2),
            'total' => round($grandTotal, 2),
            'count' => array_sum($cart)
        ]);
        break;
        
    case 'add':
        $productId = (int)($_POST['product_id'] ?? 0);
        $quantity = (int)($_POST['quantity'] ?? 1);
        
        if ($productId <= 0 || $quantity <= 0) {
            jsonResponse(['success' => false, 'message' => 'بيانات غير صحيحة'], 400);
        }
        
        // التحقق من توفر المنتج
        $stmt = $pdo->prepare("SELECT stock_quantity FROM products WHERE id = ?");
        $stmt->execute([$productId]);
        $product = $stmt->fetch();
        
        if (!$product) {
            jsonResponse(['success' => false, 'message' => 'المنتج غير موجود'], 404);
        }
        
        $currentQty = $_SESSION['cart'][$productId] ?? 0;
        $newQty = $currentQty + $quantity;
        
        if ($newQty > $product['stock_quantity']) {
            jsonResponse(['success' => false, 'message' => 'الكمية المطلوبة غير متوفرة'], 400);
        }
        
        $_SESSION['cart'][$productId] = $newQty;
        
        jsonResponse([
            'success' => true,
            'message' => 'تمت إضافة المنتج إلى السلة',
            'cart_count' => array_sum($_SESSION['cart'])
        ]);
        break;
        
    case 'update':
        $productId = (int)($_POST['product_id'] ?? 0);
        $quantity = (int)($_POST['quantity'] ?? 0);
        
        if (isset($_SESSION['cart'][$productId])) {
            if ($quantity <= 0) {
                unset($_SESSION['cart'][$productId]);
            } else {
                $_SESSION['cart'][$productId] = $quantity;
            }
        }
        
        jsonResponse(['success' => true, 'cart_count' => array_sum($_SESSION['cart'])]);
        break;
        
    case 'remove':
        $productId = (int)($_POST['product_id'] ?? 0);
        unset($_SESSION['cart'][$productId]);
        jsonResponse(['success' => true, 'cart_count' => array_sum($_SESSION['cart'])]);
        break;
        
    case 'clear':
        $_SESSION['cart'] = [];
        jsonResponse(['success' => true, 'message' => 'تم إفراغ السلة']);
        break;
        
    default:
        jsonResponse(['success' => false, 'message' => 'عملية غير صالحة'], 400);
}
?>
