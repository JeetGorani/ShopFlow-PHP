<!doctype html>
<html lang="en">

<head>
  <meta charset="utf-8">
  <meta name="viewport" content="width=device-width,initial-scale=1">
  <meta name="theme-color" content="#111827">
  <title>ShopFlow Core — Inventory & Orders</title>
  <link rel="stylesheet" href="assets/styles.css">
</head>

<body>
  <div class="app-shell">
    <aside class="sidebar">
      <a class="brand" href="#"><span class="brand-mark">S</span><span>ShopFlow<span class="muted">
            Core</span></span></a>
      <p class="nav-label">WORKSPACE</p>
      <button class="nav-link active" data-view="overview">▦ <span>Overview</span></button>
      <button class="nav-link" data-view="inventory">▤ <span>Inventory</span></button>
      <button class="nav-link" data-view="orders">↗ <span>Orders</span></button>
      <div class="sidebar-bottom">
        <div class="status-dot"></div><span>Local workspace</span><span class="version">v1.0</span>
      </div>
    </aside>
    <main class="main">
      <header class="topbar">
        <div>
          <p class="eyebrow">CLOTHING OPERATIONS</p>
          <h1 id="page-title">Overview</h1>
        </div>
        <div class="top-actions"><span class="live-pill"><i></i> System online</span><button class="primary"
            id="quick-add">＋ Add product</button></div>
      </header>
      <section id="overview-view" class="view">
        <div class="welcome">
          <div>
            <p class="eyebrow light">YOUR STORE AT A GLANCE</p>
            <h2>Good work starts with<br>clear inventory.</h2>
            <p>Track stock, process orders, and keep every size in sync.</p>
          </div>
          <div class="welcome-icon">✳</div>
        </div>
        <div class="stats-grid">
          <article class="stat-card">
            <div class="stat-top"><span>Product variants</span><span class="stat-icon purple">▤</span></div><strong
              id="stat-variants">—</strong><small>Distinct product + size rows</small>
          </article>
          <article class="stat-card">
            <div class="stat-top"><span>Units in stock</span><span class="stat-icon blue">□</span></div><strong
              id="stat-units">—</strong><small>Across all sizes</small>
          </article>
          <article class="stat-card">
            <div class="stat-top"><span>Low stock</span><span class="stat-icon orange">⌁</span></div><strong
              id="stat-low">—</strong><small>5 units or fewer</small>
          </article>
          <article class="stat-card">
            <div class="stat-top"><span>Total orders</span><span class="stat-icon green">↗</span></div><strong
              id="stat-orders">—</strong><small>Orders processed</small>
          </article>
        </div>
        <div class="content-grid">
          <section class="panel">
            <div class="panel-heading">
              <div>
                <h3>Inventory snapshot</h3>
                <p>Products that need your attention</p>
              </div><button class="text-button" data-go="inventory">View inventory →</button>
            </div>
            <div class="table-wrap">
              <table>
                <thead>

                  <tr>
                    <th>PRODUCT</th>
                    <th>CATEGORY</th>
                    <th>COLOR</th>
                    <th>SIZE</th>
                    <th>PRICE</th>
                    <th>STOCK</th>
                    <th>STATUS</th>
                    <th>ACTION</th>
                  </tr>
                </thead>
                <tbody id="low-stock-table"></tbody>
              </table>
            </div>
          </section>
          <section class="panel revenue-panel">
            <div class="panel-heading">
              <div>
                <h3>Sales overview</h3>
                <p>All-time order value</p>
              </div>
            </div>
            <p class="revenue-value" id="stat-sales">₹0.00</p>
            <div class="revenue-note"><span class="revenue-dot"></span> Calculated from saved orders</div>
            <div class="revenue-illustration">₹</div>
          </section>
        </div>
      </section>
      <section id="inventory-view" class="view hidden">
        <div class="toolbar">
          <div class="search-wrap"><span>⌕</span><input id="search" placeholder="Search products or categories..."
              aria-label="Search inventory"></div><button class="primary" id="add-product">＋ Add product</button>
        </div>
        <section class="panel">
          <div class="panel-heading">
            <div>
              <h3>All inventory</h3>
              <p>Stock is tracked separately for each size.</p>
            </div><span class="count-chip" id="inventory-count">0 variants</span>
          </div>
          <div class="table-wrap">
            <table>
              <thead>
                <tr>
                  <th>PRODUCT</th>
                  <th>CATEGORY</th>
                  <th>COLOR</th>
                  <th>SIZE</th>
                  <th>PRICE</th>
                  <th>STOCK</th>
                  <th>STATUS</th>
                  <th>ACTION</th>
                </tr>
              </thead>
              <tbody id="inventory-table"></tbody>
            </table>
          </div>
        </section>
      </section>
      <section id="orders-view" class="view hidden">
        <div class="toolbar">
          <div>
            <h2 class="section-title">Order history</h2>
            <p class="section-subtitle">Completed orders and printable invoices.</p>
          </div><button class="primary" id="new-order">＋ Create order</button>
        </div>
        <section class="panel">
          <div class="table-wrap">
            <table>
              <thead>
                <tr>
                  <th>INVOICE</th>
                  <th>CUSTOMER</th>
                  <th>DATE</th>
                  <th>ITEMS</th>
                  <th>TOTAL</th>
                  <th>ACTION</th>
                </tr>
              </thead>
              <tbody id="orders-table"></tbody>
            </table>
          </div>
        </section>
      </section>
      <footer><span>ShopFlow-Core</span><span>Open source · Built for small clothing businesses</span></footer>
    </main>
  </div>
  <div class="modal-backdrop hidden" id="modal-backdrop">
    <section class="modal" role="dialog" aria-modal="true" aria-labelledby="modal-title">
      <div class="modal-heading">
        <div>
          <p class="eyebrow">SHOPFLOW WORKSPACE</p>
          <h2 id="modal-title">Add product</h2>
        </div><button class="icon-button" id="close-modal" aria-label="Close">×</button>
      </div>
      
<form id="product-form">

  <!-- Product Name -->
  <label>
    Product name
    <input
      name="name"
      type="text"
      required
      maxlength="100"
      placeholder="e.g. Essential T-Shirt"
    >
  </label>

  <!-- Category -->
  <label>
    Category
    <select name="category" id="product-category" required>
      <option value="">Select category</option>
      <option value="T-Shirts">T-Shirts</option>
      <option value="Shirts">Shirts</option>
      <option value="Hoodies">Hoodies</option>
      <option value="Jeans">Jeans</option>
      <option value="Trousers">Trousers</option>
      <option value="Jackets">Jackets</option>
      <option value="Custom">Custom category</option>
    </select>
  </label>

  <!-- Custom Category -->
  <label id="custom-category-wrapper" hidden>
    Custom category name
    <input
      type="text"
      name="customCategory"
      id="custom-category"
      type="text"
      maxlength="50"
      placeholder="Enter your category"
    >
  </label>

  <!-- Multiple Sizes -->
  <fieldset class="variant-field">
    <legend>Select sizes</legend>

    <div class="size-options">
      <label><input type="checkbox" name="sizes" value="XS"> XS</label>
      <label><input type="checkbox" name="sizes" value="S"> S</label>
      <label><input type="checkbox" name="sizes" value="M"> M</label>
      <label><input type="checkbox" name="sizes" value="L"> L</label>
      <label><input type="checkbox" name="sizes" value="XL"> XL</label>
      <label><input type="checkbox" name="sizes" value="XXL"> XXL</label>
      <label><input type="checkbox" name="sizes" value="FREE"> FREE</label>
    </div>
  </fieldset>

  <!-- Multiple Colours -->
  <fieldset class="variant-field">
    <legend>Select colours</legend>

    <div class="color-options">
      <label class="color-choice">
        <input type="checkbox" name="colors" value="Black">
        <span class="color-swatch swatch-black"></span>
        Black
      </label>

      <label class="color-choice">
        <input type="checkbox" name="colors" value="White">
        <span class="color-swatch swatch-white"></span>
        White
      </label>

      <label class="color-choice">
        <input type="checkbox" name="colors" value="Red">
        <span class="color-swatch swatch-red"></span>
        Red
      </label>

      <label class="color-choice">
        <input type="checkbox" name="colors" value="Blue">
        <span class="color-swatch swatch-blue"></span>
        Blue
      </label>

      <label class="color-choice">
        <input type="checkbox" name="colors" value="Green">
        <span class="color-swatch swatch-green"></span>
        Green
      </label>

      <label class="color-choice">
        <input type="checkbox" name="colors" value="Pink">
        <span class="color-swatch swatch-pink"></span>
        Pink
      </label>
    </div>
  </fieldset>

<div id="variant-entries">
  <p class="form-hint">Choose at least one size and one colour.</p>
</div>

  

 
<p class="form-hint">
  Each size and colour combination has its own price and opening stock.
</p>


  <div class="modal-actions">
    <button type="button" class="secondary" id="cancel-modal">
      Cancel
    </button>

    <button class="primary" type="submit" id="save-product">
      Save product
    </button>
  </div>

</form>
    </section>
  </div>
  <div class="modal-backdrop hidden" id="order-backdrop">
    <section class="modal" role="dialog" aria-modal="true" aria-labelledby="order-modal-title">
      <div class="modal-heading">
        <div>
          <p class="eyebrow">CHECKOUT</p>
          <h2 id="order-modal-title">Create order</h2>
        </div><button class="icon-button" id="close-order" aria-label="Close">×</button>
      </div>
      <form id="order-form"><label>Customer name<input name="customerName" required maxlength="100"
            placeholder="Customer name"></label><label>Customer email<input name="customerEmail" type="email" required
            maxlength="150" placeholder="customer@example.com"></label>
        <div class="order-items-heading"><strong>Items</strong><button type="button" class="text-button"
            id="add-order-line">＋ Add item</button></div>
        <div id="order-lines"></div>
        <p class="form-hint">Stock is checked by the backend. An order is saved only if every line is valid and
          available.</p>
        <div class="modal-actions"><button type="button" class="secondary" id="cancel-order">Cancel</button><button
            class="primary" type="submit" id="submit-order">Place order</button></div>
      </form>
    </section>
  </div>
  <div id="toast" role="status" class="toast hidden"></div>
  <script src="assets/app.js"></script>
</body>

</html>