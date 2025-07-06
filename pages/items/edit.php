<?php
include '../../config/database.php';
include '../../includes/header.php';

$database = new Database();
$db = $database->getConnection();

$errors = [];
$success = false;

// Get item ID
if (!isset($_GET['id'])) {
    header('Location: index.php');
    exit;
}

$item_id = $_GET['id'];

// Get item data
$query = "SELECT * FROM item WHERE id = :id";
$stmt = $db->prepare($query);
$stmt->bindParam(':id', $item_id);
$stmt->execute();
$item = $stmt->fetch(PDO::FETCH_ASSOC);

if (!$item) {
    header('Location: index.php');
    exit;
}

if ($_SERVER['REQUEST_METHOD'] == 'POST') {
    $item_code = trim($_POST['item_code']);
    $item_name = trim($_POST['item_name']);
    $item_category = $_POST['item_category'];
    $item_subcategory = $_POST['item_subcategory'];
    $quantity = trim($_POST['quantity']);
    $unit_price = trim($_POST['unit_price']);
    
    // Validation
    if (empty($item_code)) $errors['item_code'] = 'Item code is required';
    if (empty($item_name)) $errors['item_name'] = 'Item name is required';
    if (empty($item_category)) $errors['item_category'] = 'Item category is required';
    if (empty($item_subcategory)) $errors['item_subcategory'] = 'Item subcategory is required';
    if (empty($quantity) || !is_numeric($quantity)) $errors['quantity'] = 'Valid quantity is required';
    if (empty($unit_price) || !is_numeric($unit_price)) $errors['unit_price'] = 'Valid unit price is required';
    
    // Check if item code already exists (excluding current item)
    if (!empty($item_code)) {
        $query = "SELECT id FROM item WHERE item_code = :item_code AND id != :id";
        $stmt = $db->prepare($query);
        $stmt->bindParam(':item_code', $item_code);
        $stmt->bindParam(':id', $item_id);
        $stmt->execute();
        if ($stmt->rowCount() > 0) {
            $errors['item_code'] = 'Item code already exists';
        }
    }
    
    if (empty($errors)) {
        $query = "UPDATE item SET item_code = :item_code, item_name = :item_name, 
                  item_category = :item_category, item_subcategory = :item_subcategory, 
                  quantity = :quantity, unit_price = :unit_price WHERE id = :id";
        $stmt = $db->prepare($query);
        
        if ($stmt->execute([
            ':item_code' => $item_code,
            ':item_name' => $item_name,
            ':item_category' => $item_category,
            ':item_subcategory' => $item_subcategory,
            ':quantity' => $quantity,
            ':unit_price' => $unit_price,
            ':id' => $item_id
        ])) {
            $success = true;
            // Refresh item data
            $query = "SELECT * FROM item WHERE id = :id";
            $stmt = $db->prepare($query);
            $stmt->bindParam(':id', $item_id);
            $stmt->execute();
            $item = $stmt->fetch(PDO::FETCH_ASSOC);
        } else {
            $errors['general'] = 'Error updating item';
        }
    }
}

// Get categories for dropdown
$query = "SELECT * FROM item_category ORDER BY category";
$stmt = $db->prepare($query);
$stmt->execute();
$categories = $stmt->fetchAll(PDO::FETCH_ASSOC);

// Get subcategories for dropdown
$query = "SELECT * FROM item_subcategory ORDER BY sub_category";
$stmt = $db->prepare($query);
$stmt->execute();
$subcategories = $stmt->fetchAll(PDO::FETCH_ASSOC);
?>

<div class="row">
    <div class="col-md-8 mx-auto">
        <div class="card">
            <div class="card-header">
                <h5 class="mb-0">
                    <i class="fas fa-edit"></i> Edit Item
                </h5>
            </div>
            <div class="card-body">
                <?php if ($success): ?>
                    <div class="alert alert-success">
                        Item updated successfully! <a href="index.php">View all items</a>
                    </div>
                <?php endif; ?>
                
                <?php if (isset($errors['general'])): ?>
                    <div class="alert alert-danger"><?php echo $errors['general']; ?></div>
                <?php endif; ?>
                
                <form method="POST" class="needs-validation" novalidate>
                    <div class="row">
                        <div class="col-md-6 mb-3">
                            <label for="item_code" class="form-label">Item Code *</label>
                            <input type="text" class="form-control" id="item_code" name="item_code" 
                                   value="<?php echo htmlspecialchars($item['item_code']); ?>" required>
                            <?php if (isset($errors['item_code'])): ?>
                                <div class="error"><?php echo $errors['item_code']; ?></div>
                            <?php endif; ?>
                        </div>
                        
                        <div class="col-md-6 mb-3">
                            <label for="item_name" class="form-label">Item Name *</label>
                            <input type="text" class="form-control" id="item_name" name="item_name" 
                                   value="<?php echo htmlspecialchars($item['item_name']); ?>" required>
                            <?php if (isset($errors['item_name'])): ?>
                                <div class="error"><?php echo $errors['item_name']; ?></div>
                            <?php endif; ?>
                        </div>
                    </div>
                    
                    <div class="row">
                        <div class="col-md-6 mb-3">
                            <label for="item_category" class="form-label">Item Category *</label>
                            <select class="form-control" id="item_category" name="item_category" required>
                                <option value="">Select Category</option>
                                <?php foreach ($categories as $category): ?>
                                    <option value="<?php echo $category['id']; ?>" 
                                            <?php echo ($item['item_category'] == $category['id']) ? 'selected' : ''; ?>>
                                        <?php echo $category['category']; ?>
                                    </option>
                                <?php endforeach; ?>
                            </select>
                            <?php if (isset($errors['item_category'])): ?>
                                <div class="error"><?php echo $errors['item_category']; ?></div>
                            <?php endif; ?>
                        </div>
                        
                        <div class="col-md-6 mb-3">
                            <label for="item_subcategory" class="form-label">Item Subcategory *</label>
                            <select class="form-control" id="item_subcategory" name="item_subcategory" required>
                                <option value="">Select Subcategory</option>
                                <?php foreach ($subcategories as $subcategory): ?>
                                    <option value="<?php echo $subcategory['id']; ?>" 
                                            <?php echo ($item['item_subcategory'] == $subcategory['id']) ? 'selected' : ''; ?>>
                                        <?php echo $subcategory['sub_category']; ?>
                                    </option>
                                <?php endforeach; ?>
                            </select>
                            <?php if (isset($errors['item_subcategory'])): ?>
                                <div class="error"><?php echo $errors['item_subcategory']; ?></div>
                            <?php endif; ?>
                        </div>
                    </div>
                    
                    <div class="row">
                        <div class="col-md-6 mb-3">
                            <label for="quantity" class="form-label">Quantity *</label>
                            <input type="number" class="form-control" id="quantity" name="quantity" 
                                   value="<?php echo htmlspecialchars($item['quantity']); ?>" 
                                   min="0" required>
                            <?php if (isset($errors['quantity'])): ?>
                                <div class="error"><?php echo $errors['quantity']; ?></div>
                            <?php endif; ?>
                        </div>
                        
                        <div class="col-md-6 mb-3">
                            <label for="unit_price" class="form-label">Unit Price *</label>
                            <input type="number" class="form-control" id="unit_price" name="unit_price" 
                                   value="<?php echo htmlspecialchars($item['unit_price']); ?>" 
                                   min="0" step="0.01" required>
                            <?php if (isset($errors['unit_price'])): ?>
                                <div class="error"><?php echo $errors['unit_price']; ?></div>
                            <?php endif; ?>
                        </div>
                    </div>
                    
                    <div class="row">
                        <div class="col-12">
                            <button type="submit" class="btn btn-primary">
                                <i class="fas fa-save"></i> Update Item
                            </button>
                            <a href="index.php" class="btn btn-secondary">
                                <i class="fas fa-times"></i> Cancel
                            </a>
                        </div>
                    </div>
                </form>
            </div>
        </div>
    </div>
</div>

<?php include '../../includes/footer.php'; ?>