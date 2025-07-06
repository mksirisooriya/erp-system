<?php include 'includes/header.php'; ?>

<div class="row">
    <div class="col-12">
        <h1 class="mb-4">
            <i class="fas fa-tachometer-alt"></i> Dashboard
        </h1>
    </div>
</div>

<div class="row">
    <div class="col-md-3 mb-4">
        <div class="card dashboard-card h-100">
            <div class="card-body text-center">
                <i class="fas fa-users fa-3x text-primary mb-3"></i>
                <h5 class="card-title">Customers</h5>
                <p class="card-text">Manage customer information</p>
                <a href="pages/customers/index.php" class="btn btn-primary">View Customers</a>
            </div>
        </div>
    </div>
    
    <div class="col-md-3 mb-4">
        <div class="card dashboard-card h-100">
            <div class="card-body text-center">
                <i class="fas fa-boxes fa-3x text-success mb-3"></i>
                <h5 class="card-title">Items</h5>
                <p class="card-text">Manage inventory items</p>
                <a href="pages/items/index.php" class="btn btn-success">View Items</a>
            </div>
        </div>
    </div>
    
    <div class="col-md-3 mb-4">
        <div class="card dashboard-card h-100">
            <div class="card-body text-center">
                <i class="fas fa-file-invoice fa-3x text-info mb-3"></i>
                <h5 class="card-title">Invoice Report</h5>
                <p class="card-text">View invoice reports</p>
                <a href="pages/reports/invoice-report.php" class="btn btn-info">View Report</a>
            </div>
        </div>
    </div>
    
    <div class="col-md-3 mb-4">
        <div class="card dashboard-card h-100">
            <div class="card-body text-center">
                <i class="fas fa-chart-bar fa-3x text-warning mb-3"></i>
                <h5 class="card-title">Item Report</h5>
                <p class="card-text">View item reports</p>
                <a href="pages/reports/item-report.php" class="btn btn-warning">View Report</a>
            </div>
        </div>
    </div>
</div>

<?php include 'includes/footer.php'; ?>