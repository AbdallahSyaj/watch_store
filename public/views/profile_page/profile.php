<?php

include '../../config/connectt.php';

if (session_status() == PHP_SESSION_NONE) {
    session_start();
}
if (!isset($_SESSION['user_id'])) {
    header('Location: /watch_store/public/views/signup_login.php');
    exit;
}

$user_id = $_SESSION['user_id'];

try {
    // Fetch user information
    $stmt = $pdo->prepare("SELECT * FROM users WHERE id = ?");
    $stmt->execute([$user_id]);
    $user = $stmt->fetch(PDO::FETCH_ASSOC);
    
    if (!$user) {
        echo "<pre>";
        print_r($user); // See what comes back from the database
        echo "</pre>";
        // die("User not found");
    }
    
    // Fetch payment history
    $paymentStmt = $pdo->prepare("SELECT * FROM orders WHERE user_id = ? ORDER BY created_at DESC");
    $paymentStmt->execute([$user_id]);
    $orders = $paymentStmt->fetchAll(PDO::FETCH_ASSOC);
    
} catch (PDOException $e) {
    die("Database error: " . $e->getMessage());
}
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <link rel="stylesheet" href="style_cloud.css">
    <link rel="stylesheet" href="../../assets/css/navbar.css">
    <link rel="stylesheet" href="../../assets/css/footer.css">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/bootstrap/5.3.3/css/bootstrap.min.css" integrity="sha512-jnSuA4Ss2PkkikSOLtYs8BlYIeeIK1h99ty4YfvRPAlzr377vr3CXDb7sb7eEEBYjDtcYj+AjBH3FLv5uSJuXg==" crossorigin="anonymous" referrerpolicy="no-referrer" />
    <script src="https://kit.fontawesome.com/d890c03bb3.js" crossorigin="anonymous"></script>
    <title>User Profile - <?php echo htmlspecialchars($user['name']); ?></title>
</head>
<body>
    <!-- <div class="status-bar">Connected successfully!</div> to test if con right--> 
    <?php require_once "../components/navbar.php" ?>

    <main>
        <div class="profile">
        <div class="profile_img" style="background-color:white;">
            <img src="/watch_store/public/assets/ProfileImages/<?php echo $user['image']?>" width="100%" alt="" style="vertical-align: middle;
    height: 100%;
    border-radius: 50%;
    
"
>
        </div>
        <div class="user_name"><h1><?php echo htmlspecialchars($user['name']); ?></h1></div>
        <div>
        <div class="edit_btn"><a href="./pro_edit.php" style="text-decoration: none; color: white;">Edit Profile <i class="fa-solid fa-pen-to-square " style="margin-left: .5rem;"></i></a></div>
        <div class="edit_btn logg"><a href="../logout.php" style="text-decoration: none; color: white;">LogOut  <i class="fa-solid fa-arrow-right-from-bracket" style="margin-left: .5rem;"></i> </a></div>
        </div>
    </div>
        <div class="content-container">
            <div class="user_info">
                <p><b>Email :</b> <?php echo htmlspecialchars($user['email']); ?></p>
                <p><b>Phone :</b> <?php echo htmlspecialchars($user['phone_number']); ?></p>
                <p><b>Addres :</b> <?php echo htmlspecialchars($user['country'] . ", " . $user['city']); ?></p>
                <!-- <p>Role : <?php echo htmlspecialchars($user['role']); ?></p> -->
            </div>
            
            <div class="history">
                <h2>History</h2>
                <div class="history_content">
                    <?php if (count($orders) > 0): ?>
                        <?php foreach ($orders as $order): ?>
                            <div class="history_item">
                                <div class="payment_info">
                                    <p class="payment_date"><?php echo date('M d, Y', strtotime($order['created_at'])); ?></p>
                                    <p class="payment_amount">$<?php echo number_format($order['total_price'],2); ?></p>
                                </div>
                                <div class="order_details">
                                    <?php
                                    // Fetch order items with product names
                                    $orderItemsStmt = $pdo->prepare("
                                        SELECT order_items.*, products.name AS product_name, products.description AS product_description, products.image AS product_image 
                                        FROM order_items 
                                        JOIN products ON order_items.product_id = products.id 
                                        WHERE order_items.order_id = ?
                                    ");
                                    $orderItemsStmt->execute([$order['id']]);
                                    $orderItems = $orderItemsStmt->fetchAll(PDO::FETCH_ASSOC);
                                    ?>
                                    <div class="order_header" style="display: flex; justify-content: space-between;">
                                    <h3>Order <?php echo $order['id']; ?> Details: </h3>
                                    <div style="display: flex; background-color: transparent; align-items: center;">
                                        <h6 style="margin-right: 5px;">status : </h6>
                                    <h6 class="payment_status <?php echo htmlspecialchars($order['status']); ?>"> <?php echo htmlspecialchars($order['status']); ?></h6>
                                    </div>    
                                </div>
                                   
                                    <table class="table table-striped align-items-center">
                                        <thead>
                                            <tr>
                                                <th>Product Image</th>
                                                <th>Product Name</th>
                                                <th>Price</th>
                                                <th>Quantity</th>
                                            </tr>
                                        </thead>
                                        <tbody>
                                            <?php foreach ($orderItems as $item): ?>
                                                <tr>
                                                    <td style="display: flex; justify-content:center; align-items:center;">
                                                        <img height="auto" width="50" src="/watch_store/dashboard/assets/productImages/<?= $item['product_image'] ?>" alt="<?= $item['product_name'] ?>">
                                                    </td>
                                                    <td><?php echo htmlspecialchars($item['product_name']); ?></td>
                                                    <td>$<?php echo number_format($item['price'], 2); ?></td>
                                                    <td><?php echo htmlspecialchars($item['quantity']); ?></td>
                                                </tr>
                                            <?php endforeach; ?>
                                        </tbody>
                                    </table>
                                </div>
                            </div>
                        <?php endforeach; ?>
                    <?php else: ?>
                        <p class="no_history">No payment history available</p>
                    <?php endif; ?>
                </div>
            </div>
        </div>
    </main>
    
    <?php require_once "../components/footer.html" ?>
    <script>
        <?php require_once "../../assets/js/navbar.js" ?>
    </script>
</body>
</html>