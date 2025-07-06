<?php
include '../../config/database.php';
include '../../includes/header.php';

$database = new Database();
$db = $database->getConnection();

// Handle delete request
if (isset($_GET['delete_id'])) {
    $delete_id = $_GET['delete_id'];
    
    // Get customer name for confirmation message
    $name_query = "SELECT CONCAT(first_name, ' ', last_name) as name FROM customer WHERE id = :id";
    $name_stmt = $db->prepare($name_query);
    $name_stmt->bindParam(':id', $delete_id);
    $name_stmt->execute();
    $customer_name = $name_stmt->fetch(PDO::FETCH_ASSOC);
    
    // Check if customer has any invoices (referential integrity check)
    $invoice_check = "SELECT COUNT(*) as count FROM invoice WHERE customer = :id";
    $invoice_stmt = $db->prepare($invoice_check);
    $invoice_stmt->bindParam(':id', $delete_id);
    $invoice_stmt->execute();
    $invoice_count = $invoice_stmt->fetch(PDO::FETCH_ASSOC);
    
    if ($invoice_count['count'] > 0) {
        $delete_message = '<div class="alert alert-warning"><i class="fas fa-exclamation-triangle"></i> Cannot delete customer "' . $customer_name['name'] . '" because they have ' . $invoice_count['count'] . ' invoice(s) in the system.</div>';
    } else {
        $query = "DELETE FROM customer WHERE id = :id";
        $stmt = $db->prepare($query);
        $stmt->bindParam(':id', $delete_id);
        if ($stmt->execute()) {
            $delete_message = '<div class="alert alert-success"><i class="fas fa-check-circle"></i> Customer "' . $customer_name['name'] . '" deleted successfully!</div>';
        } else {
            $delete_message = '<div class="alert alert-danger"><i class="fas fa-exclamation-circle"></i> Error deleting customer!</div>';
        }
    }
}

// Get filter parameters
$search_term = isset($_GET['search']) ? trim($_GET['search']) : '';
$district_filter = isset($_GET['district']) ? $_GET['district'] : '';

// Build query with filters
$query = "SELECT c.*, d.district as district_name 
          FROM customer c 
          LEFT JOIN district d ON c.district = d.id 
          WHERE 1=1";

$params = [];

// Add search filter
if (!empty($search_term)) {
    $query .= " AND (c.first_name LIKE :search OR c.last_name LIKE :search OR c.contact_no LIKE :search OR CONCAT(c.first_name, ' ', c.last_name) LIKE :search)";
    $params[':search'] = '%' . $search_term . '%';
}

// Add district filter
if (!empty($district_filter)) {
    $query .= " AND c.district = :district";
    $params[':district'] = $district_filter;
}

$query .= " ORDER BY c.id DESC";

$stmt = $db->prepare($query);
$stmt->execute($params);
$customers = $stmt->fetchAll(PDO::FETCH_ASSOC);

// Get districts for filter dropdown - only districts that have customers
$district_query = "SELECT DISTINCT d.id, d.district 
                   FROM district d 
                   INNER JOIN customer c ON d.id = c.district 
                   WHERE d.active = 'yes' 
                   ORDER BY d.district";
$district_stmt = $db->prepare($district_query);
$district_stmt->execute();
$districts = $district_stmt->fetchAll(PDO::FETCH_ASSOC);

// Get total customer count for statistics
$total_query = "SELECT COUNT(*) as total FROM customer";
$total_stmt = $db->prepare($total_query);
$total_stmt->execute();
$total_customers = $total_stmt->fetch(PDO::FETCH_ASSOC)['total'];
?>

<div class="row">
    <div class="col-12">
        <div class="card">
            <div class="card-header d-flex justify-content-between align-items-center">
                <h5 class="mb-0">
                    <i class="fas fa-users"></i> Customer Management
                </h5>
                <div>
                    <span class="badge bg-primary me-2">Total Customers: <?php echo $total_customers; ?></span>
                    <a href="add.php" class="btn btn-primary">
                        <i class="fas fa-plus"></i> Add Customer
                    </a>
                </div>
            </div>
            <div class="card-body">
                <!-- Display delete message if exists -->
                <?php if (isset($delete_message)): ?>
                    <?php echo $delete_message; ?>
                <?php endif; ?>

                <!-- Enhanced Search & Filter -->
                <div class="row mb-4">
                    <div class="col-md-10">
                        <form method="GET" class="d-flex gap-2">
                            <div class="flex-grow-1">
                                <input type="text" name="search" class="form-control" 
                                       placeholder="Search by name or contact number..." 
                                       value="<?php echo htmlspecialchars($search_term); ?>">
                            </div>
                            <div style="min-width: 180px;">
                                <select name="district" class="form-select">
                                    <option value="">All Districts</option>
                                    <?php foreach ($districts as $district): ?>
                                        <option value="<?php echo $district['id']; ?>" 
                                                <?php echo ($district_filter == $district['id']) ? 'selected' : ''; ?>>
                                            <?php echo $district['district']; ?>
                                        </option>
                                    <?php endforeach; ?>
                                </select>
                            </div>
                            <button type="submit" class="btn btn-primary">
                                <i class="fas fa-search"></i> Search
                            </button>
                            <a href="index.php" class="btn btn-secondary">
                                <i class="fas fa-redo"></i> Reset
                            </a>
                        </form>
                    </div>
                    <div class="col-md-2 text-end">
                        <span class="text-muted small">
                            Showing <?php echo count($customers); ?> of <?php echo $total_customers; ?>
                        </span>
                    </div>
                </div>

                <!-- Active Filters Display -->
                <?php if (!empty($search_term) || !empty($district_filter)): ?>
                    <div class="alert alert-info py-2">
                        <strong><i class="fas fa-filter"></i> Active Filters:</strong>
                        <?php if (!empty($search_term)): ?>
                            <span class="badge bg-primary me-2">Search: "<?php echo htmlspecialchars($search_term); ?>"</span>
                        <?php endif; ?>
                        <?php if (!empty($district_filter)): ?>
                            <span class="badge bg-success me-2">District: <?php 
                                $selected_district = array_filter($districts, function($d) use ($district_filter) {
                                    return $d['id'] == $district_filter;
                                });
                                echo reset($selected_district)['district'];
                            ?></span>
                        <?php endif; ?>
                        <a href="index.php" class="btn btn-sm btn-outline-secondary ms-2">
                            <i class="fas fa-times"></i> Clear All
                        </a>
                    </div>
                <?php endif; ?>
                
                <div class="table-responsive">
                    <table class="table table-striped">
                        <thead>
                            <tr>
                                <th>ID</th>
                                <th>Title</th>
                                <th>Full Name</th>
                                <th>Contact</th>
                                <th>District</th>
                                <th>Actions</th>
                            </tr>
                        </thead>
                        <tbody>
                            <?php if (empty($customers)): ?>
                                <tr>
                                    <td colspan="6" class="text-center py-4">
                                        <i class="fas fa-users fa-3x text-muted mb-3"></i><br>
                                        <strong>No customers found</strong><br>
                                        <small class="text-muted">
                                            <?php if (!empty($search_term) || !empty($district_filter)): ?>
                                                Try adjusting your search criteria or <a href="index.php">view all customers</a>
                                            <?php else: ?>
                                                <a href="add.php">Add your first customer</a> to get started
                                            <?php endif; ?>
                                        </small>
                                    </td>
                                </tr>
                            <?php else: ?>
                                <?php foreach ($customers as $customer): ?>
                                <tr>
                                    <td><?php echo $customer['id']; ?></td>
                                    <td><?php echo $customer['title']; ?></td>
                                    <td><?php echo trim($customer['first_name'] . ' ' . $customer['middle_name'] . ' ' . $customer['last_name']); ?></td>
                                    <td><?php echo $customer['contact_no']; ?></td>
                                    <td><?php echo $customer['district_name']; ?></td>
                                    <td>
                                        <a href="edit.php?id=<?php echo $customer['id']; ?>" class="btn btn-sm btn-warning">
                                            <i class="fas fa-edit"></i>
                                        </a>
                                        <button class="btn btn-sm btn-danger delete-btn" 
                                                onclick="confirmDelete(<?php echo $customer['id']; ?>, '<?php echo addslashes(trim($customer['first_name'] . ' ' . $customer['last_name'])); ?>')">
                                            <i class="fas fa-trash"></i>
                                        </button>
                                    </td>
                                </tr>
                                <?php endforeach; ?>
                            <?php endif; ?>
                        </tbody>
                    </table>
                </div>

                <!-- Pagination notice for large datasets -->
                <?php if (count($customers) >= 50): ?>
                    <div class="alert alert-info mt-3">
                        <i class="fas fa-info-circle"></i> 
                        Showing first 50+ results. Use search filters to narrow down results.
                    </div>
                <?php endif; ?>
            </div>
        </div>
    </div>
</div>

<!-- Delete Confirmation Modal -->
<div class="modal fade" id="deleteModal" tabindex="-1" aria-labelledby="deleteModalLabel" aria-hidden="true">
    <div class="modal-dialog">
        <div class="modal-content">
            <div class="modal-header">
                <h5 class="modal-title" id="deleteModalLabel">
                    <i class="fas fa-exclamation-triangle text-danger"></i> Confirm Delete
                </h5>
                <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>
            </div>
            <div class="modal-body">
                <p>Are you sure you want to delete customer <strong id="customerName"></strong>?</p>
                <div class="alert alert-warning">
                    <i class="fas fa-exclamation-triangle"></i> 
                    <strong>Warning:</strong> This action cannot be undone. If this customer has invoices, deletion will be prevented.
                </div>
            </div>
            <div class="modal-footer">
                <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">
                    <i class="fas fa-times"></i> Cancel
                </button>
                <a href="#" id="confirmDeleteBtn" class="btn btn-danger">
                    <i class="fas fa-trash"></i> Delete Customer
                </a>
            </div>
        </div>
    </div>
</div>

<script>
function confirmDelete(customerId, customerName) {
    document.getElementById('customerName').textContent = customerName;
    document.getElementById('confirmDeleteBtn').href = '?delete_id=' + customerId;
    
    var deleteModal = new bootstrap.Modal(document.getElementById('deleteModal'));
    deleteModal.show();
}

// Auto-hide alerts after 5 seconds
document.addEventListener('DOMContentLoaded', function() {
    setTimeout(function() {
        const alerts = document.querySelectorAll('.alert-success, .alert-danger');
        alerts.forEach(function(alert) {
            alert.style.transition = 'opacity 0.5s';
            alert.style.opacity = '0';
            setTimeout(function() {
                alert.remove();
            }, 500);
        });
    }, 5000);

    // Enhanced search with Enter key support
    document.querySelector('input[name="search"]').addEventListener('keypress', function(e) {
        if (e.key === 'Enter') {
            this.closest('form').submit();
        }
    });
});
</script>

<?php include '../../includes/footer.php'; ?>