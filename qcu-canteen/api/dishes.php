<?php
// ============================================================
// api/dishes.php
// ============================================================

require_once 'config.php';

// ============================================================
// IMAGE HELPER — center-crop & resize to a fixed square (JPEG)
// ============================================================
define('DISH_IMG_SIZE', 400); // output: 400×400 px

function cropSquare(string $tmpPath, string $destPath, string $ext): bool {
    // Load source image based on extension
    $src = match ($ext) {
        'jpg', 'jpeg' => @imagecreatefromjpeg($tmpPath),
        'png'         => @imagecreatefrompng($tmpPath),
        'gif'         => @imagecreatefromgif($tmpPath),
        'webp'        => @imagecreatefromwebp($tmpPath),
        default       => false,
    };
    if (!$src) return false;

    $srcW = imagesx($src);
    $srcH = imagesy($src);
    $size = DISH_IMG_SIZE;

    // Determine the largest centered square crop from the source
    if ($srcW > $srcH) {
        $cropH = $srcH;
        $cropW = $srcH;
        $cropX = (int)(($srcW - $srcH) / 2);
        $cropY = 0;
    } else {
        $cropW = $srcW;
        $cropH = $srcW;
        $cropX = 0;
        $cropY = (int)(($srcH - $srcW) / 2);
    }

    // Create output canvas
    $dst = imagecreatetruecolor($size, $size);

    // Preserve transparency for PNG/GIF (fill white for JPEG output)
    $white = imagecolorallocate($dst, 255, 255, 255);
    imagefill($dst, 0, 0, $white);

    // Resample the cropped region into the square canvas
    imagecopyresampled($dst, $src, 0, 0, $cropX, $cropY, $size, $size, $cropW, $cropH);

    // Always save as JPEG for consistency and smaller file size
    $result = imagejpeg($dst, $destPath, 88);

    imagedestroy($src);
    imagedestroy($dst);

    return $result;
}

// ============================================================

$method = $_SERVER['REQUEST_METHOD'];
$db     = getDB();

// Ensure image column exists
$check = $db->query("SHOW COLUMNS FROM dishes LIKE 'image'");
if ($check && $check->num_rows === 0) {
    $db->query("ALTER TABLE dishes ADD COLUMN image VARCHAR(255) DEFAULT NULL");
}

// Allow POST tunnelling for PUT (FormData workaround)
if ($method === 'POST' && isset($_POST['_method']) && strtoupper($_POST['_method']) === 'PUT') {
    $method = 'PUT';
}

// ============================================================
// SHARED UPLOAD HANDLER — crops to square, returns relative path
// ============================================================
function handleImageUpload(): ?string {
    if (empty($_FILES['image']) || $_FILES['image']['error'] !== UPLOAD_ERR_OK) {
        return null;
    }

    $uploadDir = __DIR__ . '/../front/uploads/dishes/';
    if (!is_dir($uploadDir)) mkdir($uploadDir, 0755, true);

    $ext     = strtolower(pathinfo($_FILES['image']['name'], PATHINFO_EXTENSION));
    $allowed = ['jpg', 'jpeg', 'png', 'gif', 'webp'];

    if (!in_array($ext, $allowed, true)) return null;

    // Validate it's actually an image
    $info = @getimagesize($_FILES['image']['tmp_name']);
    if (!$info) return null;

    // Always output as .jpg after crop
    $fname    = 'dish_' . time() . '_' . bin2hex(random_bytes(6)) . '.jpg';
    $destPath = $uploadDir . $fname;

    // Use GD crop if available, otherwise save original as-is
    if (extension_loaded('gd')) {
        if (cropSquare($_FILES['image']['tmp_name'], $destPath, $ext)) {
            return 'uploads/dishes/' . $fname;
        }
    } else {
        // GD not available — save original file with original extension
        $fname    = 'dish_' . time() . '_' . bin2hex(random_bytes(6)) . '.' . $ext;
        $destPath = $uploadDir . $fname;
        if (move_uploaded_file($_FILES['image']['tmp_name'], $destPath)) {
            return 'uploads/dishes/' . $fname;
        }
    }

    return null;
}

switch ($method) {

    // ========================================================
    // GET DISHES
    // ========================================================
    case 'GET':
        $where  = [];
        $params = [];
        $types  = '';

        if (!empty($_GET['store'])) {
            $where[]  = 'store = ?';
            $params[] = $_GET['store'];
            $types   .= 's';
        }
        if (!empty($_GET['day'])) {
            $where[]  = 'day = ?';
            $params[] = $_GET['day'];
            $types   .= 's';
        }

        $sql = 'SELECT * FROM dishes';
        if ($where) $sql .= ' WHERE ' . implode(' AND ', $where);
        $sql .= ' ORDER BY day, store, id ASC';

        $stmt = $db->prepare($sql);
        if ($params) $stmt->bind_param($types, ...$params);
        $stmt->execute();
        $rows = $stmt->get_result()->fetch_all(MYSQLI_ASSOC);
        $stmt->close();
        $db->close();

        respond([
            'success' => true,
            'dishes'  => array_map(fn($r) => [
                'id'        => (int)$r['id'],
                'store'     => $r['store'],
                'storeName' => $r['store_name'],
                'name'      => $r['dish_name'],
                'category'  => $r['category'],
                'price'     => (float)$r['price'],
                'day'       => $r['day'],
                'desc'      => $r['description'] ?? '',
                'image'     => $r['image'] ?? '',
            ], $rows),
        ]);
        break;

    // ========================================================
    // ADD DISH
    // ========================================================
    case 'POST':
        if (!isAdmin()) respond(['error' => 'Unauthorized'], 403);

        $body = (!empty($_POST)) ? $_POST : getBody();

        $store     = trim($body['store']     ?? '');
        $storeName = trim($body['storeName'] ?? '');
        $name      = trim($body['name']      ?? '');
        $category  = trim($body['category']  ?? 'Meal');
        $price     = (float)($body['price']  ?? 0);
        $day       = trim($body['day']       ?? '');
        $desc      = trim($body['desc']      ?? '');

        if (!$store || !$storeName || !$name || $price <= 0 || !$day) {
            respond(['error' => 'All required fields must be filled properly'], 400);
        }

        $imagePath = handleImageUpload();

        $stmt = $db->prepare(
            "INSERT INTO dishes (store, store_name, dish_name, category, price, day, description, image)
             VALUES (?, ?, ?, ?, ?, ?, ?, ?)"
        );
        $stmt->bind_param('ssssdsss', $store, $storeName, $name, $category, $price, $day, $desc, $imagePath);

        if ($stmt->execute()) {
            $id = $db->insert_id;
            $stmt->close();
            $db->close();
            respond(['success' => true, 'id' => $id, 'message' => 'Dish added successfully']);
        }
        respond(['error' => 'Failed to add dish'], 500);
        break;

    // ========================================================
    // UPDATE DISH
    // ========================================================
    case 'PUT':
        if (!isAdmin()) respond(['error' => 'Unauthorized'], 403);

        $id = (int)($_GET['id'] ?? 0);
        if ($id <= 0) respond(['error' => 'Valid dish ID required'], 400);

        $body = (!empty($_POST)) ? $_POST : getBody();

        $store     = trim($body['store']     ?? '');
        $storeName = trim($body['storeName'] ?? '');
        $name      = trim($body['name']      ?? '');
        $category  = trim($body['category']  ?? 'Meal');
        $price     = (float)($body['price']  ?? 0);
        $day       = trim($body['day']       ?? '');
        $desc      = trim($body['desc']      ?? '');

        if (!$store || !$storeName || !$name || $price <= 0 || !$day) {
            respond(['error' => 'All required fields must be filled properly'], 400);
        }

        $imagePath = handleImageUpload();

        if ($imagePath !== null) {
            // New image uploaded — delete old file
            $old = $db->query("SELECT image FROM dishes WHERE id = $id")->fetch_assoc();
            if (!empty($old['image'])) {
                $oldFile = __DIR__ . '/../front/' . $old['image'];
                if (file_exists($oldFile)) @unlink($oldFile);
            }
            $stmt = $db->prepare(
                "UPDATE dishes SET store=?, store_name=?, dish_name=?, category=?, price=?,
                 day=?, description=?, image=? WHERE id=?"
            );
            $stmt->bind_param('ssssdsssi', $store, $storeName, $name, $category, $price, $day, $desc, $imagePath, $id);
        } else {
            $stmt = $db->prepare(
                "UPDATE dishes SET store=?, store_name=?, dish_name=?, category=?, price=?,
                 day=?, description=? WHERE id=?"
            );
            $stmt->bind_param('ssssdssi', $store, $storeName, $name, $category, $price, $day, $desc, $id);
        }

        if ($stmt->execute()) {
            $stmt->close();
            $db->close();
            respond(['success' => true, 'message' => 'Dish updated']);
        }
        respond(['error' => 'Failed to update dish'], 500);
        break;

    // ========================================================
    // DELETE DISH
    // ========================================================
    case 'DELETE':
        if (!isAdmin()) respond(['error' => 'Unauthorized'], 403);

        $id = (int)($_GET['id'] ?? 0);
        if ($id <= 0) respond(['error' => 'Valid dish ID required'], 400);

        // Delete image file too
        $old = $db->query("SELECT image FROM dishes WHERE id = $id")->fetch_assoc();
        if (!empty($old['image'])) {
            $oldFile = __DIR__ . '/../front/' . $old['image'];
            if (file_exists($oldFile)) @unlink($oldFile);
        }

        $stmt = $db->prepare("DELETE FROM dishes WHERE id=?");
        $stmt->bind_param('i', $id);

        if ($stmt->execute()) {
            $stmt->close();
            $db->close();
            respond(['success' => true, 'message' => 'Dish deleted']);
        }
        respond(['error' => 'Failed to delete dish'], 500);
        break;

    default:
        respond(['error' => 'Method not allowed'], 405);
}

// Ensure dish image field is available.
$check = $db->query("SHOW COLUMNS FROM dishes LIKE 'image'");
if ($check && $check->num_rows === 0) {
    $db->query("ALTER TABLE dishes ADD COLUMN image VARCHAR(255) DEFAULT NULL");
}

if ($method === 'POST' && isset($_POST['_method']) && strtoupper($_POST['_method']) === 'PUT') {
    $method = 'PUT';
}

switch ($method) {

    // ========================================================
    // GET DISHES
    // ========================================================
    case 'GET':

        $where = [];
        $params = [];
        $types = '';

        if (!empty($_GET['store'])) {
            $where[] = 'store = ?';
            $params[] = $_GET['store'];
            $types .= 's';
        }

        if (!empty($_GET['day'])) {
            $where[] = 'day = ?';
            $params[] = $_GET['day'];
            $types .= 's';
        }

        $sql = 'SELECT * FROM dishes';

        if (!empty($where)) {
            $sql .= ' WHERE ' . implode(' AND ', $where);
        }

        $sql .= ' ORDER BY day, store, id ASC';

        $stmt = $db->prepare($sql);

        if (!empty($params)) {
            $stmt->bind_param($types, ...$params);
        }

        $stmt->execute();
        $rows = $stmt->get_result()->fetch_all(MYSQLI_ASSOC);

        $stmt->close();
        $db->close();

        respond([
            'success' => true,
            'dishes' => array_map(function ($r) {
                return [
                    'id'        => (int)$r['id'],
                    'store'     => $r['store'],
                    'storeName' => $r['store_name'],
                    'name'      => $r['dish_name'],
                    'category'  => $r['category'],
                    'price'     => (float)$r['price'],
                    'day'       => $r['day'],
                    'desc'      => $r['description'] ?? '',
                    'image'     => $r['image'] ?? '',
                ];
            }, $rows)
        ]);
        break;

    // ========================================================
    // ADD DISH
    // ========================================================
    case 'POST':

        if (!isAdmin()) {
            respond(['error' => 'Unauthorized'], 403);
        }

        $body = getBody();
        if (empty($body) && !empty($_POST)) {
            $body = $_POST;
        }

        $store     = trim($body['store'] ?? '');
        $storeName = trim($body['storeName'] ?? '');
        $name      = trim($body['name'] ?? '');
        $category  = trim($body['category'] ?? 'Meal');
        $price     = isset($body['price']) ? (float)$body['price'] : 0;
        $day       = trim($body['day'] ?? '');
        $desc      = trim($body['desc'] ?? '');
        $imagePath = null;

        if ($store === '' || $storeName === '' || $name === '' || $price <= 0 || $day === '') {
            respond(['error' => 'All required fields must be filled properly'], 400);
        }

        if (!empty($_FILES['image']) && $_FILES['image']['error'] === UPLOAD_ERR_OK) {
            $uploadDir = __DIR__ . '/../front/uploads/dishes/';
            if (!is_dir($uploadDir)) mkdir($uploadDir, 0755, true);

            $ext     = strtolower(pathinfo($_FILES['image']['name'], PATHINFO_EXTENSION));
            $allowed = ['jpg','jpeg','png','gif','webp'];

            if (in_array($ext, $allowed, true)) {
                $fname = 'dish_' . time() . '_' . bin2hex(random_bytes(6)) . '.' . $ext;
                if (move_uploaded_file($_FILES['image']['tmp_name'], $uploadDir . $fname)) {
                    $imagePath = 'uploads/dishes/' . $fname;
                }
            }
        }

        $stmt = $db->prepare(
            "INSERT INTO dishes (store, store_name, dish_name, category, price, day, description, image)
             VALUES (?, ?, ?, ?, ?, ?, ?, ?)"
        );

        $stmt->bind_param('ssssdsss', $store, $storeName, $name, $category, $price, $day, $desc, $imagePath);

        if ($stmt->execute()) {
            $id = $db->insert_id;

            $stmt->close();
            $db->close();

            respond([
                'success' => true,
                'id' => $id,
                'message' => 'Dish added successfully'
            ]);
        }

        respond(['error' => 'Failed to add dish'], 500);
        break;

    // ========================================================
    // UPDATE DISH
    // ========================================================
    case 'PUT':

        if (!isAdmin()) {
            respond(['error' => 'Unauthorized'], 403);
        }

        $id = isset($_GET['id']) ? (int)$_GET['id'] : 0;

        if ($id <= 0) {
            respond(['error' => 'Valid dish ID required'], 400);
        }

        $body = getBody();
        if (empty($body) && !empty($_POST)) {
            $body = $_POST;
        }

        $store     = trim($body['store'] ?? '');
        $storeName = trim($body['storeName'] ?? '');
        $name      = trim($body['name'] ?? '');
        $category  = trim($body['category'] ?? 'Meal');
        $price     = isset($body['price']) ? (float)$body['price'] : 0;
        $day       = trim($body['day'] ?? '');
        $desc      = trim($body['desc'] ?? '');
        $imagePath = null;

        if ($store === '' || $storeName === '' || $name === '' || $price <= 0 || $day === '') {
            respond(['error' => 'All required fields must be filled properly'], 400);
        }

        if (!empty($_FILES['image']) && $_FILES['image']['error'] === UPLOAD_ERR_OK) {
            $uploadDir = __DIR__ . '/../front/uploads/dishes/';
            if (!is_dir($uploadDir)) mkdir($uploadDir, 0755, true);

            $ext     = strtolower(pathinfo($_FILES['image']['name'], PATHINFO_EXTENSION));
            $allowed = ['jpg','jpeg','png','gif','webp'];

            if (in_array($ext, $allowed, true)) {
                $fname = 'dish_' . time() . '_' . bin2hex(random_bytes(6)) . '.' . $ext;
                if (move_uploaded_file($_FILES['image']['tmp_name'], $uploadDir . $fname)) {
                    $imagePath = 'uploads/dishes/' . $fname;
                }
            }
        }

        if ($imagePath !== null) {
            $stmt = $db->prepare(
                "UPDATE dishes 
                 SET store=?, store_name=?, dish_name=?, category=?, price=?, day=?, description=?, image=? 
                 WHERE id=?"
            );
            $stmt->bind_param('ssssdsssi', $store, $storeName, $name, $category, $price, $day, $desc, $imagePath, $id);
        } else {
            $stmt = $db->prepare(
                "UPDATE dishes 
                 SET store=?, store_name=?, dish_name=?, category=?, price=?, day=?, description=? 
                 WHERE id=?"
            );
            $stmt->bind_param('ssssdssi', $store, $storeName, $name, $category, $price, $day, $desc, $id);
        }

        if ($stmt->execute()) {
            $stmt->close();
            $db->close();

            respond(['success' => true, 'message' => 'Dish updated']);
        }

        respond(['error' => 'Failed to update dish'], 500);
        break;

    // ========================================================
    // DELETE DISH
    // ========================================================
    case 'DELETE':

        if (!isAdmin()) {
            respond(['error' => 'Unauthorized'], 403);
        }

        $id = isset($_GET['id']) ? (int)$_GET['id'] : 0;

        if ($id <= 0) {
            respond(['error' => 'Valid dish ID required'], 400);
        }

        $stmt = $db->prepare("DELETE FROM dishes WHERE id=?");
        $stmt->bind_param('i', $id);

        if ($stmt->execute()) {
            $stmt->close();
            $db->close();

            respond(['success' => true, 'message' => 'Dish deleted']);
        }

        respond(['error' => 'Failed to delete dish'], 500);
        break;

    default:
        respond(['error' => 'Method not allowed'], 405);
}