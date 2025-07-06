<?php
include '../../config/database.php';
include '../../includes/header.php';

$database = new Database();
$db = $database->getConnection();

// Get filter parameters
$from_date = isset($_GET['from_date']) ? $_GET['from_date'] : '';
$to_date = isset($_GET['to_date']) ? $_GET['to_date'] : '';
$customer_filter = isset($_GET['customer']) ? $_GET['customer'] : '';
$district_filter = isset($_GET['district']) ? $_GET['district'] : '';
$min_amount = isset($_GET['min_amount']) ? $_GET['min_amount'] : '';
$max_amount = isset($_GET['max_amount']) ? $_GET['max_amount'] : '';
$export = isset($_GET['export']) ? $_GET['export'] : '';

// Build query with filters
$query = "SELECT i.invoice_no, i.date, i.time, 
                 CONCAT(c.first_name, ' ', c.middle_name, ' ', c.last_name) as customer_name,
                 d.district as customer_district, i.item_count, i.amount
          FROM invoice i
          LEFT JOIN customer c ON i.customer = c.id
          LEFT JOIN district d ON c.district = d.id
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

// Add district filter
if (!empty($district_filter)) {
    $query .= " AND c.district = :district";
    $params[':district'] = $district_filter;
}

// Add amount range filter
if (!empty($min_amount)) {
    $query .= " AND i.amount >= :min_amount";
    $params[':min_amount'] = $min_amount;
}
if (!empty($max_amount)) {
    $query .= " AND i.amount <= :max_amount";
    $params[':max_amount'] = $max_amount;
}

$query .= " ORDER BY i.date DESC, i.time DESC";

$stmt = $db->prepare($query);
$stmt->execute($params);
$invoices = $stmt->fetchAll(PDO::FETCH_ASSOC);

// Handle CSV export
if ($export === 'csv') {
    header('Content-Type: text/csv');
    header('Content-Disposition: attachment; filename="invoice_report_' . date('Y-m-d') . '.csv"');
    
    $output = fopen('php://output', 'w');
    fputcsv($output, ['Invoice No', 'Date', 'Time', 'Customer', 'District', 'Item Count', 'Amount']);
    
    foreach ($invoices as $invoice) {
        fputcsv($output, [
            $invoice['invoice_no'],
            $invoice['date'],
            $invoice['time'],
            $invoice['customer_name'],
            $invoice['customer_district'],
            $invoice['item_count'],
            $invoice['amount']
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

// Get districts for dropdown
$district_query = "SELECT id, district FROM district WHERE active = 'yes' ORDER BY district";
$district_stmt = $db->prepare($district_query);
$district_stmt->execute();
$districts = $district_stmt->fetchAll(PDO::FETCH_ASSOC);

// Calculate totals
$total_amount = 0;
$total_items = 0;
foreach ($invoices as $invoice) {
    $total_amount += $invoice['amount'];
    $total_items += $invoice['item_count'];
}
?>

<div class="row">
    <div class="col-12">
        <div class="card">
            <div class="card-header d-flex justify-content-between align-items-center">
                <h5 class="mb-0">
                    <i class="fas fa-file-invoice"></i> Invoice Report
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
                            <label for="district" class="form-label">District</label>
                            <select class="form-control" id="district" name="district">
                                <option value="">All Districts</option>
                                <?php foreach ($districts as $district): ?>
                                    <option value="<?php echo $district['id']; ?>" 
                                            <?php echo ($district_filter == $district['id']) ? 'selected' : ''; ?>>
                                        <?php echo $district['district']; ?>
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
                                <a href="invoice-report.php" class="btn btn-secondary">
                                    <i class="fas fa-redo"></i> Reset
                                </a>
                            </div>
                        </div>
                    </form>
                </div>

                <!-- Active Filters Display -->
                <?php if (!empty($from_date) || !empty($to_date) || !empty($customer_filter) || !empty($district_filter) || !empty($min_amount) || !empty($max_amount)): ?>
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
                        <?php if (!empty($district_filter)): ?>
                            <span class="badge bg-info me-2">District: <?php 
                                $selected_district = array_filter($districts, function($d) use ($district_filter) {
                                    return $d['id'] == $district_filter;
                                });
                                echo reset($selected_district)['district'];
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
                                <h5 class="card-title">Total Invoices</h5>
                                <h3 class="text-primary"><?php echo count($invoices); ?></h3>
                            </div>
                        </div>
                    </div>
                    <div class="col-md-4">
                        <div class="card text-center">
                            <div class="card-body">
                                <h5 class="card-title">Total Items</h5>
                                <h3 class="text-success"><?php echo $total_items; ?></h3>
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
                    Showing <?php echo count($invoices); ?> invoice<?php echo count($invoices) !== 1 ? 's' : ''; ?>
                </p>

                <!-- Invoice Table -->
                <div class="table-responsive">
                    <table class="table table-striped">
                        <thead>
                            <tr>
                                <th>Invoice No</th>
                                <th>Date</th>
                                <th>Time</th>
                                <th>Customer</th>
                                <th>District</th>
                                <th>Item Count</th>
                                <th>Amount</th>
                            </tr>
                        </thead>
                        <tbody>
                            <?php if (empty($invoices)): ?>
                                <tr>
                                    <td colspan="7" class="text-center">No invoices found matching your criteria</td>
                                </tr>
                            <?php else: ?>
                                <?php foreach ($invoices as $invoice): ?>
                                <tr>
                                    <td><?php echo $invoice['invoice_no']; ?></td>
                                    <td><?php echo date('Y-m-d', strtotime($invoice['date'])); ?></td>
                                    <td><?php echo date('H:i:s', strtotime($invoice['time'])); ?></td>
                                    <td><?php echo $invoice['customer_name']; ?></td>
                                    <td><?php echo $invoice['customer_district']; ?></td>
                                    <td><?php echo $invoice['item_count']; ?></td>
                                    <td>Rs. <?php echo number_format($invoice['amount'], 2); ?></td>
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