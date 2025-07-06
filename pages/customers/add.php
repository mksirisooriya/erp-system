<?php
// Include database configuration and header
include '../../config/database.php';
include '../../includes/header.php';

// Initialize database connection
$database = new Database();
$db = $database->getConnection();

// Initialize variables for form handling
$errors = [];
$success = false;

// Handle form submission
if ($_SERVER['REQUEST_METHOD'] == 'POST') {
    // Get and sanitize form data
    $title = trim($_POST['title']);
    $first_name = trim($_POST['first_name']);
    $middle_name = trim($_POST['middle_name']);
    $last_name = trim($_POST['last_name']);
    $contact_no = trim($_POST['contact_no']);
    $district = $_POST['district'];
    
    // Validate required fields
    if (empty($title)) $errors['title'] = 'Title is required';
    if (empty($first_name)) $errors['first_name'] = 'First name is required';
    if (empty($last_name)) $errors['last_name'] = 'Last name is required';
    if (empty($contact_no)) $errors['contact_no'] = 'Contact number is required';
    if (!preg_match('/^[0-9]{10}$/', $contact_no)) $errors['contact_no'] = 'Contact number must be 10 digits';
    if (empty($district)) $errors['district'] = 'District is required';
    
    // Check for duplicate contact number
    if (!empty($contact_no) && !isset($errors['contact_no'])) {
        $check_query = "SELECT id, CONCAT(first_name, ' ', last_name) as name FROM customer WHERE contact_no = :contact_no";
        $check_stmt = $db->prepare($check_query);
        $check_stmt->bindParam(':contact_no', $contact_no);
        $check_stmt->execute();
        
        if ($check_stmt->rowCount() > 0) {
            $existing_customer = $check_stmt->fetch(PDO::FETCH_ASSOC);
            $errors['contact_no'] = 'This contact number already exists for customer: ' . $existing_customer['name'];
        }
    }
    
    // Check for duplicate name combination (warning only)
    if (!empty($first_name) && !empty($last_name) && empty($errors)) {
        $name_check_query = "SELECT id, contact_no FROM customer 
                            WHERE LOWER(first_name) = LOWER(:first_name) 
                            AND LOWER(last_name) = LOWER(:last_name)";
        $name_check_stmt = $db->prepare($name_check_query);
        $name_check_stmt->bindParam(':first_name', $first_name);
        $name_check_stmt->bindParam(':last_name', $last_name);
        $name_check_stmt->execute();
        
        if ($name_check_stmt->rowCount() > 0) {
            $existing_name = $name_check_stmt->fetch(PDO::FETCH_ASSOC);
            $errors['duplicate_warning'] = 'Warning: A customer with similar name already exists (Contact: ' . $existing_name['contact_no'] . '). Please verify this is not a duplicate.';
        }
    }
    
    // Insert customer if no critical errors (warnings allowed)
    if (empty($errors) || (count($errors) == 1 && isset($errors['duplicate_warning']))) {
        $query = "INSERT INTO customer (title, first_name, middle_name, last_name, contact_no, district) 
                  VALUES (:title, :first_name, :middle_name, :last_name, :contact_no, :district)";
        $stmt = $db->prepare($query);
        
        if ($stmt->execute([
            ':title' => $title,
            ':first_name' => $first_name,
            ':middle_name' => $middle_name,
            ':last_name' => $last_name,
            ':contact_no' => $contact_no,
            ':district' => $district
        ])) {
            $success = true;
            $_POST = []; // Clear form data after successful submission
            unset($errors['duplicate_warning']); // Remove warning after successful save
        } else {
            $errors['general'] = 'Error adding customer';
        }
    }
}

// Get active districts for dropdown
$query = "SELECT * FROM district WHERE active = 'yes' ORDER BY district";
$stmt = $db->prepare($query);
$stmt->execute();
$districts = $stmt->fetchAll(PDO::FETCH_ASSOC);
?>

<div class="row">
    <div class="col-md-8 mx-auto">
        <div class="card">
            <div class="card-header">
                <h5 class="mb-0">
                    <i class="fas fa-user-plus"></i> Add Customer
                </h5>
            </div>
            <div class="card-body">
                <?php if ($success): ?>
                    <div class="alert alert-success">
                        <i class="fas fa-check-circle"></i> Customer added successfully! 
                        <a href="index.php" class="alert-link">View all customers</a>
                    </div>
                <?php endif; ?>
                
                <?php if (isset($errors['general'])): ?>
                    <div class="alert alert-danger">
                        <i class="fas fa-exclamation-circle"></i> <?php echo $errors['general']; ?>
                    </div>
                <?php endif; ?>
                
                <?php if (isset($errors['duplicate_warning'])): ?>
                    <div class="alert alert-warning">
                        <i class="fas fa-exclamation-triangle"></i> <?php echo $errors['duplicate_warning']; ?>
                        <br><small>You can still proceed if this is a different customer with the same name.</small>
                    </div>
                <?php endif; ?>
                
                <form method="POST" class="needs-validation" novalidate>
                    <div class="row">
                        <!-- Title Field -->
                        <div class="col-md-6 mb-3">
                            <label for="title" class="form-label">Title *</label>
                            <select class="form-control <?php echo isset($errors['title']) ? 'is-invalid' : ''; ?>" id="title" name="title" required>
                                <option value="">Select Title</option>
                                <option value="Mr" <?php echo (isset($_POST['title']) && $_POST['title'] == 'Mr') ? 'selected' : ''; ?>>Mr</option>
                                <option value="Mrs" <?php echo (isset($_POST['title']) && $_POST['title'] == 'Mrs') ? 'selected' : ''; ?>>Mrs</option>
                                <option value="Miss" <?php echo (isset($_POST['title']) && $_POST['title'] == 'Miss') ? 'selected' : ''; ?>>Miss</option>
                                <option value="Dr" <?php echo (isset($_POST['title']) && $_POST['title'] == 'Dr') ? 'selected' : ''; ?>>Dr</option>
                            </select>
                            <?php if (isset($errors['title'])): ?>
                                <div class="invalid-feedback">
                                    <i class="fas fa-exclamation-circle"></i> <?php echo $errors['title']; ?>
                                </div>
                            <?php endif; ?>
                        </div>
                        
                        <!-- First Name Field -->
                        <div class="col-md-6 mb-3">
                            <label for="first_name" class="form-label">First Name *</label>
                            <input type="text" class="form-control <?php echo isset($errors['first_name']) ? 'is-invalid' : ''; ?>" 
                                   id="first_name" name="first_name" 
                                   value="<?php echo isset($_POST['first_name']) ? htmlspecialchars($_POST['first_name']) : ''; ?>" required>
                            <?php if (isset($errors['first_name'])): ?>
                                <div class="invalid-feedback">
                                    <i class="fas fa-exclamation-circle"></i> <?php echo $errors['first_name']; ?>
                                </div>
                            <?php endif; ?>
                        </div>
                    </div>
                    
                    <div class="row">
                        <!-- Middle Name Field -->
                        <div class="col-md-6 mb-3">
                            <label for="middle_name" class="form-label">Middle Name</label>
                            <input type="text" class="form-control" id="middle_name" name="middle_name" 
                                   value="<?php echo isset($_POST['middle_name']) ? htmlspecialchars($_POST['middle_name']) : ''; ?>">
                            <div class="form-text">Optional</div>
                        </div>
                        
                        <!-- Last Name Field -->
                        <div class="col-md-6 mb-3">
                            <label for="last_name" class="form-label">Last Name *</label>
                            <input type="text" class="form-control <?php echo isset($errors['last_name']) ? 'is-invalid' : ''; ?>" 
                                   id="last_name" name="last_name" 
                                   value="<?php echo isset($_POST['last_name']) ? htmlspecialchars($_POST['last_name']) : ''; ?>" required>
                            <?php if (isset($errors['last_name'])): ?>
                                <div class="invalid-feedback">
                                    <i class="fas fa-exclamation-circle"></i> <?php echo $errors['last_name']; ?>
                                </div>
                            <?php endif; ?>
                        </div>
                    </div>
                    
                    <div class="row">
                        <!-- Contact Number Field -->
                        <div class="col-md-6 mb-3">
                            <label for="contact_no" class="form-label">Contact Number *</label>
                            <input type="text" class="form-control <?php echo isset($errors['contact_no']) ? 'is-invalid' : ''; ?>" 
                                   id="contact_no" name="contact_no" 
                                   value="<?php echo isset($_POST['contact_no']) ? htmlspecialchars($_POST['contact_no']) : ''; ?>" 
                                   pattern="[0-9]{10}" maxlength="10" required>
                            <div class="form-text">Enter 10-digit phone number (e.g., 0771234567)</div>
                            <?php if (isset($errors['contact_no'])): ?>
                                <div class="invalid-feedback">
                                    <i class="fas fa-exclamation-circle"></i> <?php echo $errors['contact_no']; ?>
                                </div>
                            <?php endif; ?>
                        </div>
                        
                        <!-- District Field -->
                        <div class="col-md-6 mb-3">
                            <label for="district" class="form-label">District *</label>
                            <select class="form-control <?php echo isset($errors['district']) ? 'is-invalid' : ''; ?>" 
                                    id="district" name="district" required>
                                <option value="">Select District</option>
                                <?php foreach ($districts as $district): ?>
                                    <option value="<?php echo $district['id']; ?>" 
                                            <?php echo (isset($_POST['district']) && $_POST['district'] == $district['id']) ? 'selected' : ''; ?>>
                                        <?php echo $district['district']; ?>
                                    </option>
                                <?php endforeach; ?>
                            </select>
                            <?php if (isset($errors['district'])): ?>
                                <div class="invalid-feedback">
                                    <i class="fas fa-exclamation-circle"></i> <?php echo $errors['district']; ?>
                                </div>
                            <?php endif; ?>
                        </div>
                    </div>
                    
                    <!-- Submit Buttons -->
                    <div class="row">
                        <div class="col-12">
                            <button type="submit" class="btn btn-primary">
                                <i class="fas fa-save"></i> Save Customer
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

<script>
// Real-time contact number formatting and validation
document.getElementById('contact_no').addEventListener('input', function(e) {
    this.value = this.value.replace(/[^0-9]/g, ''); // Remove non-numeric characters
    
    if (this.value.length > 10) { // Limit to 10 digits
        this.value = this.value.substring(0, 10);
    }
});

// Form validation styling
document.addEventListener('DOMContentLoaded', function() {
    const form = document.querySelector('.needs-validation');
    const inputs = form.querySelectorAll('input[required], select[required]');
    
    // Add validation styling on blur
    inputs.forEach(function(input) {
        input.addEventListener('blur', function() {
            if (this.checkValidity()) {
                this.classList.remove('is-invalid');
                this.classList.add('is-valid');
            } else {
                this.classList.remove('is-valid');
                this.classList.add('is-invalid');
            }
        });
    });
    
    // Auto-hide success alerts after 5 seconds
    const successAlert = document.querySelector('.alert-success');
    if (successAlert) {
        setTimeout(function() {
            successAlert.style.transition = 'opacity 0.5s';
            successAlert.style.opacity = '0';
            setTimeout(function() {
                successAlert.remove();
            }, 500);
        }, 5000);
    }
});
</script>

<?php include '../../includes/footer.php'; ?>