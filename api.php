<?php
declare(strict_types=1);
require_once __DIR__ . '/config.php';
header('Content-Type: application/json; charset=utf-8');
header('Cache-Control: no-store');

function respond(mixed $data, int $status = 200): never
{
    http_response_code($status);
    echo json_encode($data, JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES);
    exit;
}
function fail(string $message, int $status = 400): never
{
    respond(['error' => $message], $status);
}
function body(): array
{
    $raw = file_get_contents('php://input');
    $data = json_decode($raw ?: '{}', true);
    if (!is_array($data))
        fail('Invalid JSON request body.');
    return $data;
}

function productRow(array $r): array
{
    return [
        'id' => (int) $r['id'],
        'name' => $r['name'],
        'category' => $r['category'],
        'size' => $r['size'],
        'color' => $r['color'],
        'price' => (float) $r['price'],
        'stock' => (int) $r['stock'],
        'createdAt' => date(DATE_ATOM, strtotime($r['created_at']))
    ];
}

function getOrder(PDO $pdo, int $id): array
{
    $q = $pdo->prepare('SELECT * FROM orders WHERE id = ?');
    $q->execute([$id]);
    $o = $q->fetch();
    if (!$o)
        fail('Order not found.', 404);
    $q = $pdo->prepare('SELECT * FROM order_items WHERE order_id = ? ORDER BY id');
    $q->execute([$id]);
    $items = [];
    foreach ($q->fetchAll() as $i)
        $items[] = ['id' => (int) $i['id'], 'productId' => (int) $i['product_id'], 'productName' => $i['product_name'], 'size' => $i['size'], 'color' => $i['color'],'quantity' => (int) $i['quantity'], 'unitPrice' => (float) $i['unit_price'], 'lineTotal' => (float) $i['line_total']];
    return ['id' => (int) $o['id'], 'invoiceNumber' => $o['invoice_number'] ?: ('SF-' . str_pad((string) $o['id'], 6, '0', STR_PAD_LEFT)), 'customerName' => $o['customer_name'], 'customerEmail' => $o['customer_email'], 'total' => (float) $o['total'], 'createdAt' => date(DATE_ATOM, strtotime($o['created_at'])), 'items' => $items];
}
try {
    $pdo = db();
    $action = $_GET['action'] ?? '';
    $method = $_SERVER['REQUEST_METHOD'] ?? 'GET';

    if ($action === 'products' && $method === 'GET') {
        $rows = $pdo->query('SELECT * FROM products ORDER BY name, FIELD(size,"XS","S","M","L","XL","XXL","FREE")')->fetchAll();
        respond(array_map('productRow', $rows));
    }
    
if ($action === 'products' && $method === 'POST') {
    $d = body();

    $name = trim((string) ($d['name'] ?? ''));
    $category = trim((string) ($d['category'] ?? ''));
    $variants = $d['variants'] ?? [];

    if (
        $name === '' ||
        mb_strlen($name) > 100 ||
        $category === '' ||
        mb_strlen($category) > 50
    ) {
        fail('Enter a valid product name and category.');
    }

    if (!is_array($variants) || count($variants) < 1 || count($variants) > 49) {
        fail('Choose valid size and colour combinations.');
    }

    $allowedSizes = ['XS', 'S', 'M', 'L', 'XL', 'XXL', 'FREE'];
    $allowedColors = ['Black', 'White', 'Red', 'Blue', 'Green', 'Pink'];
    $cleanVariants = [];
    $seen = [];

    foreach ($variants as $variant) {
        if (!is_array($variant)) {
            fail('Invalid product variant.');
        }

        $size = strtoupper(trim((string) ($variant['size'] ?? '')));
        $color = trim((string) ($variant['color'] ?? ''));
        $price = filter_var($variant['price'] ?? null, FILTER_VALIDATE_FLOAT);
        $stock = filter_var($variant['stock'] ?? null, FILTER_VALIDATE_INT);

        if (!in_array($size, $allowedSizes, true)) {
            fail('Choose a valid size.');
        }

        if (!in_array($color, $allowedColors, true)) {
            fail('Choose a valid colour.');
        }

        if ($price === false || $price <= 0 || $price > 99999999.99) {
            fail('Price must be greater than zero.');
        }

        if ($stock === false || $stock < 0 || $stock > 1000000) {
            fail('Stock must be a whole number from 0 to 1,000,000.');
        }

        $key = $size . '|' . $color;

        if (isset($seen[$key])) {
            fail('Duplicate size and colour combination.');
        }

        $seen[$key] = true;

        $cleanVariants[] = [
            'size' => $size,
            'color' => $color,
            'price' => $price,
            'stock' => $stock
        ];
    }

    try {
        $pdo->beginTransaction();

        $q = $pdo->prepare(
            'INSERT INTO products (name, category, size, color, price, stock)
             VALUES (?, ?, ?, ?, ?, ?)'
        );

        foreach ($cleanVariants as $variant) {
            $q->execute([
                $name,
                $category,
                $variant['size'],
                $variant['color'],
                $variant['price'],
                $variant['stock']
            ]);
        }

        $pdo->commit();
    } catch (PDOException $e) {
        if ($pdo->inTransaction()) {
            $pdo->rollBack();
        }

        if ($e->getCode() === '23000') {
            fail('A product with this size and colour already exists.', 409);
        }

        throw $e;
    }

    respond([
        'success' => true,
        'created' => count($cleanVariants)
    ], 201);
}

    if ($action === 'restock' && $method === 'POST') {
        $id = filter_var($_GET['id'] ?? null, FILTER_VALIDATE_INT);
        $qty = filter_var(body()['quantity'] ?? null, FILTER_VALIDATE_INT);
        if (!$id || !$qty || $qty < 1 || $qty > 1000000)
            fail('Enter a whole number greater than zero.');
        $q = $pdo->prepare('UPDATE products SET stock = stock + ? WHERE id = ? AND stock <= 1000000 - ?');
        $q->execute([$qty, $id, $qty]);
        if (!$q->rowCount()) {
            $q = $pdo->prepare('SELECT id FROM products WHERE id=?');
            $q->execute([$id]);
            if (!$q->fetch())
                fail('Product not found.', 404);
            fail('Stock limit would be exceeded.');
        }
        respond(['success' => true]);
    }
    if ($action === 'dashboard' && $method === 'GET') {
        $s = $pdo->query('SELECT COUNT(*) variants, COALESCE(SUM(stock),0) units, COALESCE(SUM(stock <= 5),0) low FROM products')->fetch();
        $o = $pdo->query('SELECT COUNT(*) orders, COALESCE(SUM(total),0) sales FROM orders')->fetch();
        respond(['productVariants' => (int) $s['variants'], 'unitsInStock' => (int) $s['units'], 'lowStockVariants' => (int) $s['low'], 'orderCount' => (int) $o['orders'], 'totalSales' => (float) $o['sales']]);
    }
    if ($action === 'orders' && $method === 'GET') {
        $ids = $pdo->query('SELECT id FROM orders ORDER BY created_at DESC, id DESC')->fetchAll(PDO::FETCH_COLUMN);
        respond(array_map(fn($id) => getOrder($pdo, (int) $id), $ids));
    }
    if ($action === 'order' && $method === 'GET') {
        $id = filter_var($_GET['id'] ?? null, FILTER_VALIDATE_INT);
        if (!$id)
            fail('Valid order ID required.');
        respond(getOrder($pdo, $id));
    }
    if ($action === 'orders' && $method === 'POST') {
        $d = body();
        $customer = trim((string) ($d['customerName'] ?? ''));
        $email = trim((string) ($d['customerEmail'] ?? ''));
        $items = $d['items'] ?? null;
        if ($customer === '' || mb_strlen($customer) > 100)
            fail('Enter a valid customer name.');
        if (!filter_var($email, FILTER_VALIDATE_EMAIL) || mb_strlen($email) > 150)
            fail('Enter a valid customer email.');
        if (!is_array($items) || count($items) < 1 || count($items) > 50)
            fail('An order must contain 1 to 50 item lines.');
        $quantities = [];
        foreach ($items as $item) {
            $pid = filter_var($item['productId'] ?? null, FILTER_VALIDATE_INT);
            $qty = filter_var($item['quantity'] ?? null, FILTER_VALIDATE_INT);
            if (!$pid || !$qty || $qty < 1 || $qty > 10000)
                fail('Every item needs a valid product and quantity.');
            $quantities[$pid] = ($quantities[$pid] ?? 0) + $qty;
            if ($quantities[$pid] > 10000)
                fail('Combined quantity for a product is too high.');
        }
        $pdo->beginTransaction();
        try {
            $q = $pdo->prepare('INSERT INTO orders(customer_name,customer_email,total) VALUES(?,?,0)');
            $q->execute([$customer, $email]);
            $orderId = (int) $pdo->lastInsertId();
            $total = 0.0;
            foreach ($quantities as $pid => $qty) 
                {
                $q = $pdo->prepare('SELECT * FROM products WHERE id=? FOR UPDATE');
                $q->execute([$pid]);
                $p = $q->fetch();
                if (!$p)
                    throw new RuntimeException('Product not found. Refresh inventory and try again.');
                if ((int) $p['stock'] < $qty)
                    throw new RuntimeException(
    'Not enough stock for ' . $p['name'] .
    ' (' . $p['color'] . ', ' . $p['size'] .
    '). Available: ' . $p['stock']
);
                $unit = (float) $p['price'];
                $line = round($unit * $qty, 2);
                $total = round($total + $line, 2);
                $q = $pdo->prepare('UPDATE products SET stock=stock-? WHERE id=?');
                $q->execute([$qty, $pid]);
                
$q = $pdo->prepare(
    'INSERT INTO order_items
    (order_id, product_id, product_name, size, color, quantity, unit_price, line_total)
    VALUES (?, ?, ?, ?, ?, ?, ?, ?)'
);

                $q->execute([$orderId, $pid, $p['name'], $p['size'], $p['color'], $qty, $unit, $line]);
            }
            $invoice = 'SF-' . str_pad((string) $orderId, 6, '0', STR_PAD_LEFT);
            $q = $pdo->prepare('UPDATE orders SET invoice_number=?, total=? WHERE id=?');
            $q->execute([$invoice, $total, $orderId]);
            $pdo->commit();
            respond(getOrder($pdo, $orderId), 201);
        } catch (Throwable $e) {
            if ($pdo->inTransaction())
                $pdo->rollBack();
            if ($e instanceof RuntimeException)
                fail($e->getMessage(), 400);
            throw $e;
        }
    }
    fail('Unknown endpoint.', 404);
} catch (PDOException $e) {
    error_log((string) $e);
    if (str_contains($e->getMessage(), 'Unknown database'))
        fail('Database shopflow not found. Import database/schema.sql in phpMyAdmin first.', 500);
    fail('Database error. Check MySQL is running and config.php credentials are correct.', 500);
} catch (Throwable $e) {
    error_log((string) $e);
    fail('Unexpected server error. Check the Apache PHP error log.', 500);
}
