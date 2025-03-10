<?php
require_once '../config/cart_con.php';
require_once '../models/Cart.php';
require_once '../models/Product.php';

// Initialize session and database connection
initSession();

// Connect to database
$conn = connectDB();

// Get user ID if logged in
$user_id = isset($_SESSION['user_id']) ? $_SESSION['user_id'] : null;

// Create cart instance with user_id
$cart = new Cart($conn, $user_id);
$product = new Product($conn);

// Get cart count from the Cart class
$cart_count = $cart->getCartCount();

// Page title
$page_title = 'Shopping Cart';
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title><?php echo $page_title; ?></title>
    <script src="https://kit.fontawesome.com/d890c03bb3.js" crossorigin="anonymous"></script>
    <link rel="stylesheet" href="../assets/css/cart.css">
    <link rel="stylesheet" href="../assets/css/navbar.css">
    <link rel="stylesheet" href="../assets/css/footer.css">
    <script>
    function updateQuantity(productId, change) {
        const input = document.getElementById('quantity-' + productId);
        const currentVal = parseInt(input.value);
        const maxStock = parseInt(input.getAttribute('data-stock'));
        
        let newVal = currentVal + change;
        // Ensure quantity is at least 1 and doesn't exceed stock
        if (newVal < 1) newVal = 1;
        if (newVal > maxStock) {
            newVal = maxStock;
            
        }
        
        input.value = newVal;
        
        // Submit the form
        document.getElementById('update-form-' + productId).submit();
    }
    </script>
</head>
<body>
<?php include './components/navbar.php'; ?>
<div class="container cart-container">
    <div class="cart-items">
        <h2>Shopping Cart (<?php echo $cart_count; ?> items)</h2>
       
        <?php
        // Get cart items from database
        $cart_items = $cart->getCartItems();
        
        if (empty($cart_items)): 
        ?>
            <div class="empty-cart">
                <p>Your cart is empty.</p>
                <p>Continue <a href="/watch_store/public">shopping</a> to add items.</p>
            </div>
        <?php 
        else:
            foreach ($cart_items as $item):
                // Ensure we have all the necessary product information
                $product_id = $item['product_id'] ?? $item['id'] ?? 0;
                
                // Get product stock directly from the products table
                $product_info = $product->getProduct($product_id);
                $max_stock = $product_info['stock'] ?? 10; // Default to 10 if stock info not available
        ?>
            <div class="item">
                <div class="item-image">
                    <?php if (isset($item['image']) && $item['image']): ?>
                        <img src="/watch_store/dashboard/assets/productImages/<?php echo $item['image']; ?>" alt="<?php echo htmlspecialchars($item['name']); ?>">
                    <?php else: ?>
                        <div class="placeholder-image">
                            <i class="fas fa-watch"></i>
                        </div>
                    <?php endif; ?>
                </div>
                
                <div class="item-details">
                    <div class="item-category"><?php echo htmlspecialchars($item['category'] ?? ''); ?></div>
                    <div class="item-title"><?php echo htmlspecialchars($item['name']); ?></div>
                    <?php if (isset($item['color']) && isset($item['size'])): ?>
                    <div class="item-meta">
                        <?php echo htmlspecialchars($item['color']); ?> / 
                        <?php echo htmlspecialchars($item['size']); ?>
                    </div>
                    <?php endif; ?>
                    
                    <form id="update-form-<?php echo $product_id; ?>" action="../controllers/cart.php" method="post" class="quantity-control">
                        <input type="hidden" name="action" value="update">
                        <input type="hidden" name="product_id" value="<?php echo $product_id; ?>">
                        
                        <button type="button" class="quantity-btn" onclick="updateQuantity(<?php echo $product_id; ?>, -1);">-</button>
                        
                        <input type="text" id="quantity-<?php echo $product_id; ?>" name="quantity" class="quantity-input" 
                               value="<?php echo $item['quantity']; ?>" 
                               min="1" 
                               max="<?php echo $max_stock; ?>"
                               data-stock="<?php echo $max_stock; ?>">
                        
                        <button type="button" class="quantity-btn" onclick="updateQuantity(<?php echo $product_id; ?>, 1);">+</button>
                    </form>
                    
                    <?php if($item['quantity'] >= $max_stock): ?>
                    <div class="stock-warning" style="color: red;">Max stock reached</div>
                    <?php endif; ?>
                </div>
                
                <div class="item-price">
                    $<?php echo number_format($item['price'], 2); ?>
                </div>
                
                <form action="../controllers/cart.php" method="post" class="remove-form">
                    <input type="hidden" name="action" value="remove">
                    <input type="hidden" name="product_id" value="<?php echo $product_id; ?>">
                    <button type="submit" class="remove-item">&times;</button>
                </form>
            </div>
        <?php 
            endforeach; 
        endif; 
        ?>
    </div>
    
    <?php if (!empty($cart_items)): ?>
    <div class="order-summary">
        <h2>Order Summary</h2>
        
        <?php
        $subtotal = $cart->getSubtotal();
        $tax = $cart->calculateTax($subtotal);
        $shipping = 0; // Free shipping
        $total = $cart->getTotal($subtotal, $tax, $shipping);
        ?>
        
        <div class="summary-row">
            <span>Subtotal</span>
            <span>$<?php echo number_format($subtotal, 2); ?></span>
        </div>
        
        <div class="summary-row">
            <span>Shipping</span>
            <span><?php echo $shipping > 0 ? '$' . number_format($shipping, 2) : 'Free'; ?></span>
        </div>
        
        <div class="summary-row">
            <span>Tax</span>
            <span>$<?php echo number_format($tax, 2); ?></span>
        </div>
        
        <div class="summary-row summary-total">
            <span>Total</span>
            <span>$<?php echo number_format($total, 2); ?></span>
        </div>
        
        <form action="/watch_store/checkout/checkout.php" method="get">
            <button type="submit" class="checkout-btn">Proceed to Checkout</button>
        </form>
        
        <form action="../controllers/cart.php" method="post" class="empty-cart-form">
            <input type="hidden" name="action" value="empty">
            <button type="submit" class="empty-cart-btn">Empty Cart</button>
        </form>
    </div>
    <?php endif; ?>
</div>

<script src="https://cdn.jsdelivr.net/npm/sweetalert2@11"></script>
<script src="../assets/js/navbar.js"></script>
<?php include './components/footer.html'; ?>
</body>
</html>