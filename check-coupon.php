<?php
header('Content-Type: application/json');

try {
    require_once 'includes/config.php';
    require_once 'includes/db.php';
    require_once 'includes/auth.php';
    require_once 'includes/functions.php';

    $code = isset($_GET['code']) ? strtoupper(sanitizeInput($_GET['code'])) : '';
    $companyId = isset($_GET['company_id']) ? (int)$_GET['company_id'] : 0;

    if (!$code) {
        echo json_encode([
            'valid' => false,
            'message' => 'Kupon kodu gerekli!'
        ]);
        exit;
    }

    $coupon = validateCoupon($db, $code, $companyId);

    if ($coupon) {
        echo json_encode([
            'valid' => true,
            'discount' => (float)$coupon['discount_percentage'],
            'message' => 'Kupon geçerli!'
        ]);
    } else {
        echo json_encode([
            'valid' => false,
            'message' => 'Geçersiz veya süresi dolmuş kupon kodu!'
        ]);
    }
} catch (Exception $e) {
    echo json_encode([
        'valid' => false,
        'message' => 'Sistem hatası: ' . $e->getMessage()
    ]);
}
?>
