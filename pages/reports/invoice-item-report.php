<?php
include '../../config/database.php';
include '../../includes/header.php';

$database = new Database();
$db = $database->getConnection();

// Get filter parameters
$from_date = isset($_GET['from_date']) ? $_GET['from_date'] : '';
$to_date = isset($_GET['to_date']) ? $_GET['to_date'] : '';
$customer_filter = isset($_GET['customer']) ? $_GET['customer'] : '';
$category_filter = isset($_GET['category']) ? $_GET['category'] : '';
$min_amount = isset($_GET['min_amount']) ? $_GET['min_amount'] : '';
$max_amount = isset($_GET['max_amount']) ? $_GET['max_amount'] : '';
$export = isset($_GET['export']) ? $_GET['export'] : '';

// Build query with filters
$query = "SELECT im.invoice_no, i.date as invoice_date,
                 CONCAT(c.first_name, ' ', c.middle_name, ' ', c.last_name) as customer_name,
                 it.item_name, it.item_code, ic.category as item_category,
                 im.quantity, im.unit_price, im.amount
          FROM invoice_master im
          LEFT JOIN invoice i ON im.invoice_no = i.invoice_no
          LEFT JOIN customer c ON i.customer = c.id
          LEFT JOIN item it ON im.item_id = it.id
          LEFT JOIN item_category ic ON it.item_category = ic.id
          WHERE 1=1";

$params = [];

// Add date range filter
if (!empty($from_date) && !empty($to_date)) {
    $query .= " AND i.date BETWEEN :from_date AND :to_date";
    $params[':from_date'] = $from_date;
    $params[':to_date'] = $to_date;
}

// Add customer filter
if (!empty($customer_filter)) {
    $query .= " AND i.customer = :customer";
    $params[':customer'] = $customer_filter;
}

// Add category filter
if (!empty($category_filter)) {
    $query .= " AND it.item_category = :category";
    $params[':category'] = $category_filter;
}

// Add amount range filter
if (!empty($min_amount)) {
    $query .= " AND im.amount >= :min_amount";
    $params[':min_amount'] = $min_amount;
}
if (!empty($max_amount)) {
    $query .= " AND im.amount <= :max_amount";
    $params[':max_amount'] = $max_amount;
}

$query .= " ORDER BY i.date DESC, im.invoice_no DESC";

$stmt = $db->prepare($query);
$stmt->execute($params);
$invoice_items = $stmt->fetchAll(PDO::FETCH_ASSOC);

// Handle CSV export
if ($export === 'csv') {
    header('Content-Type: text/csv');
    header('Content-Disposition: attachment; filename="invoice_item_report_' . date('Y-m-d') . '.csv"');
    
    $output = fopen('php://output', 'w');
    fputcsv($output, ['Invoice No', 'Date', 'Customer', 'Item Name', 'Item Code', 'Category', 'Quantity', 'Unit Price', 'Amount']);
    
    foreach ($invoice_items as $item) {
        fputcsv($output, [
            $item['invoice_no'],
            $item['invoice_date'],
            $item['customer_name'],
            $item['item_name'],
            $item['item_code'],
            $item['item_category'],
            $item['quantity'],
            $item['unit_price'],
            $item['amount']
        ]);
    }
    
    fclose($output);
    exit;
}

// Get customers for dropdown
$customer_query = "SELECT id, CONCAT(first_name, ' ', last_name) as name FROM customer ORDER BY first_name";
$customer_stmt = $db->prepare($customer_query);
$customer_stmt->execute();
$customers = $customer_stmt->fetchAll(PDO::FETCH_ASSOC);

// Get categories for dropdown
$category_query = "SELECT id, category FROM item_category ORDER BY category";
$category_stmt = $db->prepare($category_query);
$category_stmt->execute();
$categories = $category_stmt->fetchAll(PDO::FETCH_ASSOC);

// Calculate totals
$total_amount = 0;
$total_quantity = 0;
foreach ($invoice_items as $item) {
    $total_amount += $item['amount'];
    $total_quantity += $item['quantity'];
}
?>

<div class="row">
    <div class="col-12">
        <div class="card">
            <div class="card-header d-flex justify-content-between align-items-center">
                <h5 class="mb-0">
                    <i class="fas fa-list"></i> Invoice Item Report
                </h5>
                <!-- Export Button -->
                <a href="?<?php echo http_build_query(array_merge($_GET, ['export' => 'csv'])); ?>" 
                   class="btn btn-success btn-sm">
                    <i class="fas fa-download"></i> Export CSV
                </a>
            </div>
            <div class="card-body">
                <!-- Enhanced Filters -->
                <div class="report-filters border p-3 rounded mb-4">
                    <form method="GET" class="row g-3">
                        <div class="col-md-3">
                            <label for="from_date" class="form-label">From Date</label>
                            <input type="date" class="form-control" id="from_date" name="from_date" value="<?php echo $from_date; ?>">
                        </div>
                        <div class="col-md-3">
                            <label for="to_date" class="form-label">To Date</label>
                            <input type="date" class="form-control" id="to_date" name="to_date" value="<?php echo $to_date; ?>">
                        </div>
                        <div class="col-md-3">
                            <label for="customer" class="form-label">Customer</label>
                            <select class="form-control" id="customer" name="customer">
                                <option value="">All Customers</option>
                                <?php foreach ($customers as $customer): ?>
                                    <option value="<?php echo $customer['id']; ?>" 
                                            <?php echo ($customer_filter == $customer['id']) ? 'selected' : ''; ?>>
                                        <?php echo $customer['name']; ?>
                                    </option>
                                <?php endforeach; ?>
                            </select>
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
                            <label for="min_amount" class="form-label">Min Amount</label>
                            <input type="number" class="form-control" id="min_amount" name="min_amount" 
                                   value="<?php echo $min_amount; ?>" min="0" step="0.01" placeholder="0.00">
                        </div>
                        <div class="col-md-3">
                            <label for="max_amount" class="form-label">Max Amount</label>
                            <input type="number" class="form-control" id="max_amount" name="max_amount" 
                                   value="<?php echo $max_amount; ?>" min="0" step="0.01" placeholder="0.00">
                        </div>
                        <div class="col-md-6">
                            <label class="form-label">&nbsp;</label>
                            <div>
                                <button type="submit" class="btn btn-primary">
                                    <i class="fas fa-search"></i> Search
                                </button>
                                <a href="invoice-item-report.php" class="btn btn-secondary">
                                    <i class="fas fa-redo"></i> Reset
                                </a>
                            </div>
                        </div>
                    </form>
                </div>

                <!-- Active Filters Display -->
                <?php if (!empty($from_date) || !empty($to_date) || !empty($customer_filter) || !empty($category_filter) || !empty($min_amount) || !empty($max_amount)): ?>
                    <div class="alert alert-info">
                        <strong>Active Filters:</strong>
                        <?php if (!empty($from_date) && !empty($to_date)): ?>
                            <span class="badge bg-primary me-2">Date: <?php echo $from_date; ?> to <?php echo $to_date; ?></span>
                        <?php endif; ?>
                        <?php if (!empty($customer_filter)): ?>
                            <span class="badge bg-success me-2">Customer: <?php 
                                $selected_customer = array_filter($customers, function($c) use ($customer_filter) {
                                    return $c['id'] == $customer_filter;
                                });
                                echo reset($selected_customer)['name'];
                            ?></span>
                        <?php endif; ?>
                        <?php if (!empty($category_filter)): ?>
                            <span class="badge bg-info me-2">Category: <?php 
                                $selected_category = array_filter($categories, function($c) use ($category_filter) {
                                    return $c['id'] == $category_filter;
                                });
                                echo reset($selected_category)['category'];
                            ?></span>
                        <?php endif; ?>
                        <?php if (!empty($min_amount)): ?>
                            <span class="badge bg-warning me-2">Min Amount: Rs. <?php echo $min_amount; ?></span>
                        <?php endif; ?>
                        <?php if (!empty($max_amount)): ?>
                            <span class="badge bg-warning me-2">Max Amount: Rs. <?php echo $max_amount; ?></span>
                        <?php endif; ?>
                    </div>
                <?php endif; ?>

                <!-- Summary Cards -->
                <div class="row mb-4">
                    <div class="col-md-4">
                        <div class="card text-center">
                            <div class="card-body">
                                <h5 class="card-title">Total Items</h5>
                                <h3 class="text-primary"><?php echo count($invoice_items); ?></h3>
                            </div>
                        </div>
                    </div>
                    <div class="col-md-4">
                        <div class="card text-center">
                            <div class="card-body">
                                <h5 class="card-title">Total Quantity</h5>
                                <h3 class="text-success"><?php echo $total_quantity; ?></h3>
                            </div>
                        </div>
                    </div>
                    <div class="col-md-4">
                        <div class="card text-center">
                            <div class="card-body">
                                <h5 class="card-title">Total Amount</h5>
                                <h3 class="text-warning">Rs. <?php echo number_format($total_amount, 2); ?></h3>
                            </div>
                        </div>
                    </div>
                </div>

                <!-- Results count -->
                <p class="text-muted mb-3">
                    Showing <?php echo count($invoice_items); ?> item<?php echo count($invoice_items) !== 1 ? 's' : ''; ?>
                </p>

                <!-- Invoice Items Table -->
                <div class="table-responsive">
                    <table class="table table-striped">
                        <thead>
                            <tr>
                                <th>Invoice No</th>
                                <th>Date</th>
                                <th>Customer</th>
                                <th>Item Name</th>
                                <th>Item Code</th>
                                <th>Category</th>
                                <th>Quantity</th>
                                <th>Unit Price</th>
                                <th>Amount</th>
                            </tr>
                        </thead>
                        <tbody>
                            <?php if (empty($invoice_items)): ?>
                                <tr>
                                    <td colspan="9" class="text-center">No invoice items found matching your criteria</td>
                                </tr>
                            <?php else: ?>
                                <?php foreach ($invoice_items as $item): ?>
                                <tr>
                                    <td><?php echo $item['invoice_no']; ?></td>
                                    <td><?php echo date('Y-m-d', strtotime($item['invoice_date'])); ?></td>
                                    <td><?php echo $item['customer_name']; ?></td>
                                    <td><?php echo $item['item_name']; ?></td>
                                    <td><?php echo $item['item_code']; ?></td>
                                    <td><?php echo $item['item_category']; ?></td>
                                    <td><?php echo $item['quantity']; ?></td>
                                    <td>Rs. <?php echo number_format($item['unit_price'], 2); ?></td>
                                    <td>Rs. <?php echo number_format($item['amount'], 2); ?></td>
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