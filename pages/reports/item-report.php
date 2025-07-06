<?php
include '../../config/database.php';
include '../../includes/header.php';

$database = new Database();
$db = $database->getConnection();

// Get filter parameters
$category_filter = isset($_GET['category']) ? $_GET['category'] : '';
$subcategory_filter = isset($_GET['subcategory']) ? $_GET['subcategory'] : '';
$stock_status = isset($_GET['stock_status']) ? $_GET['stock_status'] : '';
$search_term = isset($_GET['search']) ? $_GET['search'] : '';
$export = isset($_GET['export']) ? $_GET['export'] : '';

// Build query with filters
$query = "SELECT DISTINCT i.item_name, ic.category, isc.sub_category, i.quantity, i.unit_price
          FROM item i
          LEFT JOIN item_category ic ON i.item_category = ic.id
          LEFT JOIN item_subcategory isc ON i.item_subcategory = isc.id
          WHERE 1=1";

$params = [];

// Add category filter
if (!empty($category_filter)) {
    $query .= " AND i.item_category = :category";
    $params[':category'] = $category_filter;
}

// Add subcategory filter
if (!empty($subcategory_filter)) {
    $query .= " AND i.item_subcategory = :subcategory";
    $params[':subcategory'] = $subcategory_filter;
}

// Add search filter
if (!empty($search_term)) {
    $query .= " AND (i.item_name LIKE :search OR i.item_code LIKE :search)";
    $params[':search'] = '%' . $search_term . '%';
}

$query .= " ORDER BY i.item_name";

$stmt = $db->prepare($query);
$stmt->execute($params);
$items = $stmt->fetchAll(PDO::FETCH_ASSOC);

// Apply stock status filter (after query since it's based on calculated values)
if (!empty($stock_status)) {
    $items = array_filter($items, function($item) use ($stock_status) {
        switch ($stock_status) {
            case 'in_stock':
                return $item['quantity'] > 10;
            case 'low_stock':
                return $item['quantity'] > 0 && $item['quantity'] <= 10;
            case 'out_of_stock':
                return $item['quantity'] <= 0;
            default:
                return true;
        }
    });
}

// Handle CSV export
if ($export === 'csv') {
    header('Content-Type: text/csv');
    header('Content-Disposition: attachment; filename="item_report_' . date('Y-m-d') . '.csv"');
    
    $output = fopen('php://output', 'w');
    fputcsv($output, ['Item Name', 'Category', 'Subcategory', 'Quantity', 'Unit Price', 'Status']);
    
    foreach ($items as $item) {
        $status = $item['quantity'] > 10 ? 'In Stock' : ($item['quantity'] > 0 ? 'Low Stock' : 'Out of Stock');
        fputcsv($output, [
            $item['item_name'],
            $item['category'],
            $item['sub_category'],
            $item['quantity'],
            $item['unit_price'],
            $status
        ]);
    }
    
    fclose($output);
    exit;
}

// Get categories for dropdown
$category_query = "SELECT id, category FROM item_category ORDER BY category";
$category_stmt = $db->prepare($category_query);
$category_stmt->execute();
$categories = $category_stmt->fetchAll(PDO::FETCH_ASSOC);

// Get subcategories for dropdown
$subcategory_query = "SELECT id, sub_category FROM item_subcategory ORDER BY sub_category";
$subcategory_stmt = $db->prepare($subcategory_query);
$subcategory_stmt->execute();
$subcategories = $subcategory_stmt->fetchAll(PDO::FETCH_ASSOC);

// Calculate totals
$total_items = count($items);
$total_quantity = 0;
$total_value = 0;
$in_stock_count = 0;
$low_stock_count = 0;
$out_of_stock_count = 0;

foreach ($items as $item) {
    $total_quantity += $item['quantity'];
    $total_value += ($item['quantity'] * $item['unit_price']);
    
    if ($item['quantity'] > 10) {
        $in_stock_count++;
    } elseif ($item['quantity'] > 0) {
        $low_stock_count++;
    } else {
        $out_of_stock_count++;
    }
}
?>

<div class="row">
    <div class="col-12">
        <div class="card">
            <div class="card-header d-flex justify-content-between align-items-center">
                <h5 class="mb-0">
                    <i class="fas fa-boxes"></i> Item Report
                </h5>
                <div>
                    <!-- Stock Status Info -->
                    <small class="text-white me-3">
                        <i class="fas fa-info-circle"></i> 
                        In Stock: >10 | Low Stock: 1-10 | Out of Stock: 0
                    </small>
                    <!-- Export Button -->
                    <a href="?<?php echo http_build_query(array_merge($_GET, ['export' => 'csv'])); ?>" 
                       class="btn btn-success btn-sm">
                        <i class="fas fa-download"></i> Export CSV
                    </a>
                </div>
            </div>
            <div class="card-body">
                <!-- Enhanced Filters -->
                <div class="report-filters border p-3 rounded mb-4">
                    <form method="GET" class="row g-3">
                        <div class="col-md-3">
                            <label for="search" class="form-label">Search Items</label>
                            <input type="text" class="form-control" id="search" name="search" 
                                   value="<?php echo $search_term; ?>" placeholder="Search by name or code...">
                        </div>
                        <div class="col-md-3">
                            <label for="category" class="form-label">Category</label>
                            <select class="form-control" id="category" name="category">
                                <option value="">All Categories</option>
                                <?php foreach ($categories as $category): ?>
                                    <option value="<?php echo $category['id']; ?>" 
                                            <?php echo ($category_filter == $category['id']) ? 'selected' : ''; ?>>
                                        <?php echo $category['category']; ?>
                                    </option>
                                <?php endforeach; ?>
                            </select>
                        </div>
                        <div class="col-md-3">
                            <label for="subcategory" class="form-label">Subcategory</label>
                            <select class="form-control" id="subcategory" name="subcategory">
                                <option value="">All Subcategories</option>
                                <?php foreach ($subcategories as $subcategory): ?>
                                    <option value="<?php echo $subcategory['id']; ?>" 
                                            <?php echo ($subcategory_filter == $subcategory['id']) ? 'selected' : ''; ?>>
                                        <?php echo $subcategory['sub_category']; ?>
                                    </option>
                                <?php endforeach; ?>
                            </select>
                        </div>
                        <div class="col-md-3">
                            <label for="stock_status" class="form-label">Stock Status</label>
                            <select class="form-control" id="stock_status" name="stock_status">
                                <option value="">All Status</option>
                                <option value="in_stock" <?php echo ($stock_status == 'in_stock') ? 'selected' : ''; ?>>In Stock (>10)</option>
                                <option value="low_stock" <?php echo ($stock_status == 'low_stock') ? 'selected' : ''; ?>>Low Stock (1-10)</option>
                                <option value="out_of_stock" <?php echo ($stock_status == 'out_of_stock') ? 'selected' : ''; ?>>Out of Stock (0)</option>
                            </select>
                        </div>
                        <div class="col-12">
                            <button type="submit" class="btn btn-primary">
                                <i class="fas fa-search"></i> Search
                            </button>
                            <a href="item-report.php" class="btn btn-secondary">
                                <i class="fas fa-redo"></i> Reset
                            </a>
                        </div>
                    </form>
                </div>

                <!-- Active Filters Display -->
                <?php if (!empty($search_term) || !empty($category_filter) || !empty($subcategory_filter) || !empty($stock_status)): ?>
                    <div class="alert alert-info">
                        <strong>Active Filters:</strong>
                        <?php if (!empty($search_term)): ?>
                            <span class="badge bg-primary me-2">Search: <?php echo $search_term; ?></span>
                        <?php endif; ?>
                        <?php if (!empty($category_filter)): ?>
                            <span class="badge bg-success me-2">Category: <?php 
                                $selected_category = array_filter($categories, function($c) use ($category_filter) {
                                    return $c['id'] == $category_filter;
                                });
                                echo reset($selected_category)['category'];
                            ?></span>
                        <?php endif; ?>
                        <?php if (!empty($subcategory_filter)): ?>
                            <span class="badge bg-info me-2">Subcategory: <?php 
                                $selected_subcategory = array_filter($subcategories, function($s) use ($subcategory_filter) {
                                    return $s['id'] == $subcategory_filter;
                                });
                                echo reset($selected_subcategory)['sub_category'];
                            ?></span>
                        <?php endif; ?>
                        <?php if (!empty($stock_status)): ?>
                            <span class="badge bg-warning me-2">Status: <?php 
                                echo ucfirst(str_replace('_', ' ', $stock_status));
                            ?></span>
                        <?php endif; ?>
                    </div>
                <?php endif; ?>

                <!-- Summary Cards -->
                <div class="row mb-4">
                    <div class="col-md-3">
                        <div class="card text-center">
                            <div class="card-body">
                                <h5 class="card-title">Total Items</h5>
                                <h3 class="text-primary"><?php echo $total_items; ?></h3>
                            </div>
                        </div>
                    </div>
                    <div class="col-md-3">
                        <div class="card text-center">
                            <div class="card-body">
                                <h5 class="card-title">Total Quantity</h5>
                                <h3 class="text-success"><?php echo $total_quantity; ?></h3>
                            </div>
                        </div>
                    </div>
                    <div class="col-md-3">
                        <div class="card text-center">
                            <div class="card-body">
                                <h5 class="card-title">Total Value</h5>
                                <h3 class="text-warning">Rs. <?php echo number_format($total_value, 2); ?></h3>
                            </div>
                        </div>
                    </div>
                    <div class="col-md-3">
                        <div class="card text-center">
                            <div class="card-body">
                                <h5 class="card-title">Stock Status</h5>
                                <div class="d-flex justify-content-around">
                                    <div class="text-center">
                                        <small class="text-success">In Stock</small>
                                        <div class="fw-bold"><?php echo $in_stock_count; ?></div>
                                    </div>
                                    <div class="text-center">
                                        <small class="text-warning">Low Stock</small>
                                        <div class="fw-bold"><?php echo $low_stock_count; ?></div>
                                    </div>
                                    <div class="text-center">
                                        <small class="text-danger">Out of Stock</small>
                                        <div class="fw-bold"><?php echo $out_of_stock_count; ?></div>
                                    </div>
                                </div>
                            </div>
                        </div>
                    </div>
                </div>

                <!-- Results count -->
                <p class="text-muted mb-3">
                    Showing <?php echo count($items); ?> item<?php echo count($items) !== 1 ? 's' : ''; ?>
                </p>

                <!-- Items Table -->
                <div class="table-responsive">
                    <table class="table table-striped">
                        <thead>
                            <tr>
                                <th>Item Name</th>
                                <th>Category</th>
                                <th>Subcategory</th>
                                <th>Quantity</th>
                                <th>Unit Price</th>
                                <th>Total Value</th>
                                <th>Status</th>
                            </tr>
                        </thead>
                        <tbody>
                            <?php if (empty($items)): ?>
                                <tr>
                                    <td colspan="7" class="text-center">No items found matching your criteria</td>
                                </tr>
                            <?php else: ?>
                                <?php foreach ($items as $item): ?>
                                <tr>
                                    <td><?php echo $item['item_name']; ?></td>
                                    <td><?php echo $item['category']; ?></td>
                                    <td><?php echo $item['sub_category']; ?></td>
                                    <td><?php echo $item['quantity']; ?></td>
                                    <td>Rs. <?php echo number_format($item['unit_price'], 2); ?></td>
                                    <td>Rs. <?php echo number_format($item['quantity'] * $item['unit_price'], 2); ?></td>
                                    <td>
                                        <?php if ($item['quantity'] > 10): ?>
                                            <span class="badge bg-success">In Stock</span>
                                        <?php elseif ($item['quantity'] > 0): ?>
                                            <span class="badge bg-warning">Low Stock</span>
                                        <?php else: ?>
                                            <span class="badge bg-danger">Out of Stock</span>
                                        <?php endif; ?>
                                    </td>
                                </tr>
                                <?php endforeach; ?>
                            <?php endif; ?>
                        </tbody>
                    </table>
                </div>
            </div>
        </div>
    </div>
</div>

<?php include '../../includes/footer.php'; ?>