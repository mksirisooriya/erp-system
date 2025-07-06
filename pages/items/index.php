<?php
include '../../config/database.php';
include '../../includes/header.php';

$database = new Database();
$db = $database->getConnection();

// Handle delete request
if (isset($_GET['delete_id'])) {
    $delete_id = $_GET['delete_id'];
    $query = "DELETE FROM item WHERE id = :id";
    $stmt = $db->prepare($query);
    $stmt->bindParam(':id', $delete_id);
    if ($stmt->execute()) {
        echo '<div class="alert alert-success">Item deleted successfully!</div>';
    } else {
        echo '<div class="alert alert-danger">Error deleting item!</div>';
    }
}

// Fetch items with category and subcategory names
$query = "SELECT i.*, c.category, s.sub_category 
          FROM item i 
          LEFT JOIN item_category c ON i.item_category = c.id 
          LEFT JOIN item_subcategory s ON i.item_subcategory = s.id 
          ORDER BY i.id DESC";
$stmt = $db->prepare($query);
$stmt->execute();
$items = $stmt->fetchAll(PDO::FETCH_ASSOC);
?>

<div class="row">
    <div class="col-12">
        <div class="card">
            <div class="card-header d-flex justify-content-between align-items-center">
                <h5 class="mb-0">
                    <i class="fas fa-boxes"></i> Item Management
                </h5>
                <a href="add.php" class="btn btn-primary">
                    <i class="fas fa-plus"></i> Add Item
                </a>
            </div>
            <div class="card-body">
                <div class="mb-3">
                    <input type="text" id="searchInput" class="form-control" placeholder="Search items...">
                </div>
                
                <div class="table-responsive">
                    <table class="table table-striped">
                        <thead>
                            <tr>
                                <th>ID</th>
                                <th>Item Code</th>
                                <th>Item Name</th>
                                <th>Category</th>
                                <th>Subcategory</th>
                                <th>Quantity</th>
                                <th>Unit Price</th>
                                <th>Actions</th>
                            </tr>
                        </thead>
                        <tbody>
                            <?php foreach ($items as $item): ?>
                            <tr>
                                <td><?php echo $item['id']; ?></td>
                                <td><?php echo $item['item_code']; ?></td>
                                <td><?php echo $item['item_name']; ?></td>
                                <td><?php echo $item['category']; ?></td>
                                <td><?php echo $item['sub_category']; ?></td>
                                <td><?php echo $item['quantity']; ?></td>
                                <td>Rs. <?php echo number_format($item['unit_price'], 2); ?></td>
                                <td>
                                    <a href="edit.php?id=<?php echo $item['id']; ?>" class="btn btn-sm btn-warning">
                                        <i class="fas fa-edit"></i>
                                    </a>
                                    <a href="?delete_id=<?php echo $item['id']; ?>" class="btn btn-sm btn-danger delete-btn">
                                        <i class="fas fa-trash"></i>
                                    </a>
                                </td>
                            </tr>
                            <?php endforeach; ?>
                        </tbody>
                    </table>
                </div>
            </div>
        </div>
    </div>
</div>

<?php include '../../includes/footer.php'; ?>