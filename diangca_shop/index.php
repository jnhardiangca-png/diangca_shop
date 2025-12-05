<?php
include 'database.php';  // connect database

// =================== ADD CUSTOMER ===================
if (isset($_POST['add_customer'])) {
    $first = $_POST['first_name'];
    $last = $_POST['last_name'];
    $email = $_POST['email'];
    $phone = $_POST['phone'];

    $full_name = $first . " " . $last;

    $sql = "INSERT INTO customers (customer_name, email, phone)
            VALUES ('$full_name', '$email', '$phone')";

    if ($conn->query($sql)) {
        $customer_msg = "<p class='success-msg'>Customer Added Successfully!</p>";
    } else {
        $customer_msg = "<p class='error-msg'>Error adding customer.</p>";
    }
}

// =================== CREATE ORDER ===================
if (isset($_POST['create_order'])) {

    $customer_id = $_POST['customer_id'];

    // Insert order first
    $order_sql = "INSERT INTO orders (customer_id, order_date)
                  VALUES ($customer_id, NOW())";

    if ($conn->query($order_sql)) {
        $order_id = $conn->insert_id;

        // Insert each order item
        if (!empty($_POST['product_id'])) {
            foreach ($_POST['product_id'] as $index => $product_id) {

                $qty = $_POST['quantity'][$index];

                // Get product price
                $price_sql = "SELECT price FROM products WHERE product_id = $product_id";
                $price_res = $conn->query($price_sql);
                $price_row = $price_res->fetch_assoc();
                $price = $price_row['price'];

                // Insert order item
                $item_sql = "INSERT INTO order_items (order_id, product_id, quantity, price)
                             VALUES ($order_id, $product_id, $qty, $price)";
                $conn->query($item_sql);
            }
        }

        $order_msg = "<p class='success-msg'>Order Created Successfully! (#$order_id)</p>";
    } else {
        $order_msg = "<p class='error-msg'>Error creating order.</p>";
    }
}

// =================== LOAD DROPDOWN DATA ===================
$customers = $conn->query("SELECT * FROM customers ORDER BY customer_name");
$products = $conn->query("SELECT * FROM products ORDER BY product_name");

?>




<!DOCTYPE html>
<html lang="en">

<head>
  <meta charset="UTF-8">
  <meta name="viewport" content="width=device-width, initial-scale=1.0">
  <title>Burger Haven</title>
  <link rel="stylesheet" href="CSS/style.css">
</head>

<body>

  <div class="container">

    <!-- ================= NAVIGATION ================= -->
    <nav class="nav">
      <div class="nav-logo">
        <h1>Burger Haven</h1>
      </div>
      <div class="nav-links">
        <a href="#">Home</a>
        <a href="#categories">Menu</a>
        <a href="#deals">Deals</a>
        <a href="#contact">Contact</a>
      </div>
      <div class="nav-search">
        <input type="text" placeholder="Search Burgers...">
        <button>Search</button>
      </div>
    </nav>

    <!-- ================= HERO SECTION ================= -->
    <section class="hero">
      <div class="hero-intro">
        <h1>Fresh. Juicy. Delicious Burgers!</h1>
        <p>Your favorite burgers made with passion and flame-grilled goodness.</p>
        <button onclick="window.location='#categories'">Order Now</button>
      </div>
      <div class="hero-image">
      </div>
    </section>

    <!-- ================= BURGER CATEGORIES ================= -->
    <section id="categories" class="categories">
      <h2 class="section-title">Browse Burger Categories</h2>
      <div class="categories-wrapper">
        <?php
        include 'database.php';
        $cat_sql = "SELECT * FROM categories";
        $cat_result = $conn->query($cat_sql);

        if ($cat_result->num_rows > 0) {
          while ($cat = $cat_result->fetch_assoc()) {
            echo "
              <div class='category-card'>
                <h3>" . $cat['category_name'] . "</h3>
              </div>
            ";
          }
        } else {
          echo "<p>No categories available.</p>";
        }
        ?>
      </div>
    </section>

    <!-- ================= PRODUCTS ================= -->
    <section class="products">
      <h2 class="section-title">Our Best Burgers</h2>
      <div class="products-wrapper">
        <?php
$sql = "SELECT * FROM products";
$result = $conn->query($sql);

if ($result->num_rows > 0) {
  while ($row = $result->fetch_assoc()) {

    // Use placeholder if image is empty

    echo "
    <div class='product-card'>
        <img src='IMG/burger.png' alt='" . $row["product_name"] . "'>
        <h4>" . $row["product_name"] . "</h4>
        <p class='price'>₱" . number_format($row["price"], 2) . "</p>
        <button>Add to Cart</button>
    </div>
    ";

  }
} else {
  echo "<p>No products found.</p>";
}
?>

      </div>
    </section>

    <!-- ================= ORDERS MANAGEMENT ================= -->
<section id="orders" class="orders-section">
  <h2 class="section-title">Orders Management</h2>

  <div class="orders-wrapper">

    <!-- ALL ORDERS TABLE -->
    <div class="card" style="width:100%; margin-bottom:2rem;">
      <h3>All Orders</h3>
      <?php
      $orders_sql = "
    SELECT o.order_id, c.customer_name, COUNT(oi.order_item_id) AS total_items
    FROM orders o
    JOIN customers c ON o.customer_id = c.customer_id
    JOIN order_items oi ON o.order_id = oi.order_id
    GROUP BY o.order_id
    ORDER BY o.order_id DESC
";

      $orders = $conn->query($orders_sql);
      ?>
      <table border="1" cellpadding="8" style="width:100%; border-collapse:collapse;">
        <tr>
          <th>Order ID</th>
          <th>Customer</th>
          <th>Total Items</th>
          <th>Action</th>
        </tr>
        <?php while($o = $orders->fetch_assoc()): ?>
          <tr>
            <td><?= $o['order_id'] ?></td>
            <td><?= $o['customer_name'] ?></td>
            <td><?= $o['total_items'] ?></td>
            <td>
              <form method="GET">
                <input type="hidden" name="view_order_id" value="<?= $o['order_id'] ?>">
                <button type="submit">View Details</button>
              </form>
            </td>
          </tr>
        <?php endwhile; ?>
      </table>
    </div>

    <!-- SINGLE ORDER DETAILS -->
    <?php
    if(isset($_GET['view_order_id'])){
      $order_id = $_GET['view_order_id'];

      $details_sql = "
        SELECT p.product_name, oi.quantity, oi.price
        FROM order_items oi
        JOIN products p ON oi.product_id = p.product_id
        WHERE oi.order_id = $order_id
      ";
      $details = $conn->query($details_sql);
    ?>
    <div class="card" style="width:100%;">
      <h3>Order #<?= $order_id ?> Details</h3>
      <table border="1" cellpadding="8" style="width:100%; border-collapse:collapse;">
        <tr>
          <th>Product</th>
          <th>Quantity</th>
          <th>Price</th>
          <th>Subtotal</th>
        </tr>
        <?php 
        $total_amount = 0;
        while($item = $details->fetch_assoc()): 
          $subtotal = $item['quantity'] * $item['price'];
          $total_amount += $subtotal;
        ?>
        <tr>
          <td><?= $item['product_name'] ?></td>
          <td><?= $item['quantity'] ?></td>
          <td>₱<?= number_format($item['price'],2) ?></td>
          <td>₱<?= number_format($subtotal,2) ?></td>
        </tr>
        <?php endwhile; ?>
        <tr>
          <th colspan="3" style="text-align:right;">Total:</th>
          <th>₱<?= number_format($total_amount,2) ?></th>
        </tr>
      </table>
    </div>
    <?php } ?>

  </div>
</section>
<!-- ================= CUSTOMER & ORDER MANAGEMENT ================= -->
<section id="customer-orders" class="customer-orders-section">
  <h2 class="section-title">Customer & Order Management</h2>

  <div class="management-wrapper">
    
    <!-- ============ ADD CUSTOMER ============ -->
    <div class="card management-card">
      <h3>Add New Customer</h3>
      <?= $customer_msg ?? '' ?>
      <form method="POST" class="management-form">
        <input type="text" name="first_name" placeholder="First Name" required>
        <input type="text" name ="last_name" placeholder="Last Name" required>
        <input type="email" name="email" placeholder="Email">
        <input type="text" name="phone" placeholder="Phone">
        <button type="submit" name="add_customer">Add Customer</button>
      </form>
    </div>

    <!-- ============ CREATE ORDER ============ -->
    <div class="card management-card">
      <h3>Create New Order</h3>
      <?= $order_msg ?? '' ?>
      <form method="POST" class="management-form">
        
        <label>Select Customer:</label>
        <select name="customer_id" required>
          <option value="">-- Select Customer --</option>
          <?php while($c = $customers->fetch_assoc()): ?>
            <option value="<?= $c['customer_id'] ?>"><?= $c['customer_name'] ?></option>
          <?php endwhile; ?>
        </select>

        <label>Select Products:</label>
        <div class="product-list">
          <?php while($p = $products->fetch_assoc()): ?>
            <div class="product-item">
              <input type="checkbox" name="product_id[]" value="<?= $p['product_id'] ?>">
              <span class="prod-name"><?= $p['product_name'] ?></span>
              <span class="prod-price">(₱<?= number_format($p['price'],2) ?>)</span>
              Qty:
              <input type="number" name="quantity[]" value="1" min="1">
            </div>
          <?php endwhile; ?>
        </div>

        <button type="submit" name="create_order">Create Order</button>
      </form>
    </div>

  </div>
</section>


  </div>
</section>


  </div>

</body>
</html>