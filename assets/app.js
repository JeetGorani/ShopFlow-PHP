const $ = (selector) => document.querySelector(selector);
const state = { products: [], orders: [], view: "overview", toastTimer: null };
const money = (n) => new Intl.NumberFormat("en-IN", { style: "currency", currency: "INR" }).format(Number(n || 0));
const escapeHtml = (value) => String(value ?? "").replace(/[&<>"']/g, c => ({ "&": "&amp;", "<": "&lt;", ">": "&gt;", '"': "&quot;", "'": "&#39;" }[c]));
async function api(path, options = {}) {
  const response = await fetch(path, { ...options, headers: { "Content-Type": "application/json", ...(options.headers || {}) } });
  const body = await response.json().catch(() => ({}));
  if (!response.ok) throw new Error(body.error || `Request failed (${response.status})`);
  return body;
}
function toast(message, error = false) {
  const el = $("#toast"); el.textContent = message; el.classList.remove("hidden", "error");
  if (error) el.classList.add("error");
  clearTimeout(state.toastTimer); state.toastTimer = setTimeout(() => el.classList.add("hidden"), 4000);
}
function stockStatus(stock) {
  if (stock === 0) return '<span class="stock-status out">Out of stock</span>';
  if (stock <= 5) return '<span class="stock-status low">Low stock</span>';
  return '<span class="stock-status ok">In stock</span>';
}
function productCell(p) { return `<div class="product-name">${escapeHtml(p.name)}</div><div class="subline">SKU #${p.id}</div>`; }

function renderInventory() {
  const query = ($("#search")?.value || "").toLowerCase().trim();
  const list = state.products.filter(p =>
    `${p.name} ${p.category} ${p.size} ${p.color || ""}`
      .toLowerCase().includes(query)
  );

  $("#inventory-count").textContent = `${list.length} variants`;

  $("#inventory-table").innerHTML = list.length
    ? list.map(p => `<tr>
        <td>${productCell(p)}</td>
        <td>${escapeHtml(p.category)}</td>
        <td><span class="color-chip">${escapeHtml(p.color || "Unspecified")}</span></td>
        <td><span class="size-chip">${escapeHtml(p.size)}</span></td>
        <td>${money(p.price)}</td>
        <td><strong>${p.stock}</strong></td>
        <td>${stockStatus(p.stock)}</td>
        <td><button class="action-btn" data-restock="${p.id}">Restock</button></td>
      </tr>`).join("")
    : '<tr><td colspan="8" class="empty">No matching products found.</td></tr>';
}

function renderOverview(summary) {
  $("#stat-variants").textContent = summary.productVariants;
  $("#stat-units").textContent = summary.unitsInStock.toLocaleString("en-IN");
  $("#stat-low").textContent = summary.lowStockVariants;
  $("#stat-orders").textContent = summary.orderCount;
  $("#stat-sales").textContent = money(summary.totalSales);
  const low = [...state.products].filter(p => p.stock <= 5).sort((a, b) => a.stock - b.stock).slice(0, 5);
  $("#low-stock-table").innerHTML = low.length ? low.map(p => `<tr><td>${productCell(p)}</td><td><span class="size-chip">${escapeHtml(p.size)}</span></td><td>${money(p.price)}</td><td><strong>${p.stock}</strong></td><td>${stockStatus(p.stock)}</td></tr>`).join("") : '<tr><td colspan="5" class="empty">No low-stock items. Nice work!</td></tr>';
}
function renderOrders() {
  $("#orders-table").innerHTML = state.orders.length ? state.orders.map(o => `<tr><td><strong>${escapeHtml(o.invoiceNumber)}</strong></td><td>${escapeHtml(o.customerName)}<div class="subline">${escapeHtml(o.customerEmail)}</div></td><td>${new Date(o.createdAt).toLocaleString()}</td><td>${o.items.reduce((sum, i) => sum + i.quantity, 0)} units</td><td><strong>${money(o.total)}</strong></td><td><button class="action-btn" data-invoice="${o.id}">View invoice</button></td></tr>`).join("") : '<tr><td colspan="6" class="empty">No orders yet. Create your first order.</td></tr>';
}
async function refresh() {
  const [products, orders, summary] = await Promise.all([api("api.php?action=products"), api("api.php?action=orders"), api("api.php?action=dashboard")]);
  state.products = products; state.orders = orders; renderInventory(); renderOrders(); renderOverview(summary);
}
function showView(view) {
  state.view = view;
  document.querySelectorAll(".view").forEach(el => el.classList.add("hidden"));
  $(`#${view}-view`).classList.remove("hidden");
  document.querySelectorAll(".nav-link").forEach(el => el.classList.toggle("active", el.dataset.view === view));
  $("#page-title").textContent = ({ overview: "Overview", inventory: "Inventory", orders: "Orders" })[view];
}

function openProductModal() {
  $("#product-form").reset();
  $("#modal-title").textContent = "Add product";
  updateCategoryField();
  renderVariantEntries();
  $("#modal-backdrop").classList.remove("hidden");
}

function closeProductModal() { $("#modal-backdrop").classList.add("hidden"); }

function updateCategoryField() {
  const custom = $("#product-category").value === "Custom";
  $("#custom-category").hidden = !custom;
  $("#custom-category").required = custom;
}

function renderVariantEntries() {
  const sizes = [...document.querySelectorAll(
    '#product-form input[name="sizes"]:checked'
  )].map(el => el.value);

  const colors = [...document.querySelectorAll(
    '#product-form input[name="colors"]:checked'
  )].map(el => el.value);

  const wrap = $("#variant-entries");
  const previous = new Map();

  wrap.querySelectorAll(".variant-entry").forEach(row => {
    const key = `${row.dataset.size}|${row.dataset.color}`;
    previous.set(key, {
      price: row.querySelector('[data-field="price"]').value,
      stock: row.querySelector('[data-field="stock"]').value
    });
  });

  const combinations = sizes.flatMap(size =>
    colors.map(color => ({ size, color }))
  );

  if (!combinations.length) {
    wrap.innerHTML = '<p class="form-hint">Choose at least one size and one colour.</p>';
    return;
  }

  wrap.innerHTML = combinations.map(({ size, color }) => {
    const old = previous.get(`${size}|${color}`) || {};
    return `
      <div class="variant-entry" data-size="${size}" data-color="${color}">
        <strong>${escapeHtml(color)} · ${escapeHtml(size)}</strong>
        <label>Price (₹)
          <input data-field="price" type="number" min="0.01"
            max="99999999.99" step="0.01" required
            value="${escapeHtml(old.price ?? "")}" placeholder="499.00">
        </label>
        <label>Opening stock
          <input data-field="stock" type="number" min="0" max="1000000"
            step="1" required value="${escapeHtml(old.stock ?? "0")}">
        </label>
      </div>`;
  }).join("");
}


function getSelectedCategory(form) {
  const choice = String(form.get("category") || "").trim();

  return choice === "Custom"
    ? String(form.get("customCategory") || "").trim()
    : choice;
}


function productOptions() {
  const available = state.products.filter(p => p.stock > 0);

  return available.map(p =>
    `<option value="${p.id}">
      ${escapeHtml(p.name)} · ${escapeHtml(p.color || "Unspecified")}
      · ${escapeHtml(p.size)} · ${money(p.price)} · ${p.stock} in stock
    </option>`
  ).join("");
}

function addOrderLine() {
  const wrap = document.createElement("div"); wrap.className = "order-line";
  wrap.innerHTML = `<select aria-label="Product and size" required>${productOptions() || '<option value="">No stock available</option>'}</select><input type="number" aria-label="Quantity" min="1" max="10000" value="1" required><button type="button" class="remove-line" aria-label="Remove item">×</button>`;
  wrap.querySelector(".remove-line").addEventListener("click", () => { if ($("#order-lines").children.length > 1) wrap.remove(); else toast("An order needs at least one item.", true); });
  $("#order-lines").append(wrap);
}
function openOrderModal() {
  if (!state.products.some(p => p.stock > 0)) { toast("Add products with available stock before creating an order.", true); return; }
  $("#order-form").reset(); $("#order-lines").innerHTML = ""; addOrderLine(); $("#order-backdrop").classList.remove("hidden");
}
function closeOrderModal() { $("#order-backdrop").classList.add("hidden"); }

function showInvoice(order) {
  const win = window.open("", "_blank", "width=850,height=900");

  if (!win) {
    toast("Allow pop-ups to open the invoice.", true);
    return;
  }

  
const rows = order.items.map(item => `
    <tr>
        <td>${escapeHtml(item.productName)}</td>
        <td>${escapeHtml(item.color || "Unspecified")}</td>
        <td>${escapeHtml(item.size)}</td>
        <td>${item.quantity}</td>
        <td>${money(item.unitPrice)}</td>
        <td>${money(item.lineTotal)}</td>
    </tr>
`).join("");

  win.document.write(`
        <!doctype html>
        <html lang="en">
        <head>
            <meta charset="UTF-8">
            <meta name="viewport" content="width=device-width, initial-scale=1">
            <title>ShopFlow-Core-Invoice-${escapeHtml(order.invoiceNumber)}</title>
            <style>
                * { box-sizing: border-box; }
                body {
                    font-family: Arial, sans-serif;
                    color: #172033;
                    padding: 38px;
                    max-width: 1000px;
                    margin: auto;
                }
                header {
                    display: flex;
                    justify-content: space-between;
                    gap: 20px;
                    border-bottom: 2px solid #6254e8;
                    padding-bottom: 20px;
                }
                h1 { margin: 0 0 6px; font-size: 34px; }
                h3 { margin-top: 24px; }
                p { color: #687286; }
                table {
                    width: 100%;
                    border-collapse: collapse;
                    margin-top: 30px;
                }
                th, td {
                    text-align: left;
                    padding: 13px 8px;
                    border-bottom: 1px solid #e7e9ef;
                }
                th { font-size: 12px; color: #747d8e; }
                .total {
                    text-align: right;
                    font-size: 25px;
                    font-weight: bold;
                    margin-top: 28px;
                }
                .invoice-actions {
                    display: flex;
                    flex-wrap: wrap;
                    gap: 12px;
                    margin-top: 28px;
                }
                .invoice-actions button {
                    padding: 14px 20px;
                    border-radius: 10px;
                    font-size: 15px;
                    font-weight: 600;
                    cursor: pointer;
                }
                .btn-print {
                    background: #6254e8;
                    color: white;
                    border: 1px solid #6254e8;
                }
                .btn-pdf {
                    background: white;
                    color: #6254e8;
                    border: 1px solid #6254e8;
                }
                @media (max-width: 600px) {
                    body { padding: 18px; }
                    header { flex-direction: column; }
                    h1 { font-size: 27px; }
                    table { font-size: 12px; }
                    th, td { padding: 9px 5px; }
                    .invoice-actions button { flex: 1; }
                }
                @media print {
                    .no-print { display: none !important; }
                    body { padding: 0; max-width: none; }
                    header, tr { break-inside: avoid; }
                }
            </style>
        </head>
        <body>
            <header>
                <div>
                    <h1>ShopFlow-Core</h1>
                    <p>Clothing order invoice</p>
                </div>
                <div>
                    <strong>${escapeHtml(order.invoiceNumber)}</strong>
                    <p>${new Date(order.createdAt).toLocaleString()}</p>
                </div>
            </header>

            <h3>Bill to</h3>
            <strong>${escapeHtml(order.customerName)}</strong>
            <p>${escapeHtml(order.customerEmail)}</p>

            <table>
                <thead>
                    <tr>
                        <th>ITEM</th>
                        <th>COLOR</th>
                        <th>SIZE</th>
                        <th>QTY</th>
                        <th>UNIT PRICE</th>
                        <th>AMOUNT</th>
                    </tr>
                </thead>
                <tbody>${rows}</tbody>
            </table>

            <div class="total">Total: ${money(order.total)}</div>
            <p>Thank you for your order.</p>

            <div class="invoice-actions no-print">
                <button type="button" class="btn-print"
                    onclick="window.print()">
                    Print Invoice
                </button>
                <button type="button" class="btn-pdf"
                    onclick="window.print()">
                    Save as PDF
                </button>
            </div>
        </body>
        </html>
    `);

  win.document.close();
}


document.querySelectorAll(".nav-link").forEach(b => b.addEventListener("click", () => showView(b.dataset.view)));
document.querySelectorAll("[data-go]").forEach(b => b.addEventListener("click", () => showView(b.dataset.go)));
$("#quick-add").addEventListener("click", () => { showView("inventory"); openProductModal(); });
$("#add-product").addEventListener("click", openProductModal);
$("#close-modal").addEventListener("click", closeProductModal); $("#cancel-modal").addEventListener("click", closeProductModal);
$("#modal-backdrop").addEventListener("click", e => { if (e.target.id === "modal-backdrop") closeProductModal(); });
$("#search").addEventListener("input", renderInventory);

$("#product-category").addEventListener("change", () => {
  updateCategoryField();
});

$("#product-form").querySelectorAll(
  'input[name="sizes"], input[name="colors"]'
).forEach(el => el.addEventListener("change", renderVariantEntries));

$("#product-form").addEventListener("submit", async e => {
  e.preventDefault();

  const form = new FormData(e.currentTarget);
  const category = getSelectedCategory(form);

  const variants = [...$("#variant-entries").querySelectorAll(".variant-entry")]
    .map(row => ({
      size: row.dataset.size,
      color: row.dataset.color,
      price: Number(row.querySelector('[data-field="price"]').value),
      stock: Number(row.querySelector('[data-field="stock"]').value)
    }));

  if (!category || category.length > 50) {
    toast("Enter a valid category.", true);
    return;
  }

  if (!variants.length) {
    toast("Choose at least one size and colour.", true);
    return;
  }

  const payload = {
    name: String(form.get("name") || "").trim(),
    category,
    variants
  };

  const saveButton = $("#save-product");
  saveButton.disabled = true;

  try {
    const result = await api("api.php?action=products", {
      method: "POST",
      body: JSON.stringify(payload)
    });

    closeProductModal();
    await refresh();
    toast(`${result.created} product variant(s) added successfully.`);
  } catch (err) {
    toast(err.message, true);
  } finally {
    saveButton.disabled = false;
  }
});

$("#inventory-table").addEventListener("click", async e => {
  const button = e.target.closest("[data-restock]"); if (!button) return;
  const qty = prompt("How many units should be added to this size?");
  if (qty === null) return;
  if (!/^[1-9]\d*$/.test(qty) || Number(qty) > 1000000) { toast("Enter a whole number greater than zero.", true); return; }
  try { await api(`api.php?action=restock&id=${button.dataset.restock}`, { method: "POST", body: JSON.stringify({ quantity: Number(qty) }) }); await refresh(); toast("Stock updated."); }
  catch (err) { toast(err.message, true); }
});
$("#new-order").addEventListener("click", openOrderModal);
$("#close-order").addEventListener("click", closeOrderModal); $("#cancel-order").addEventListener("click", closeOrderModal);
$("#order-backdrop").addEventListener("click", e => { if (e.target.id === "order-backdrop") closeOrderModal(); });
$("#add-order-line").addEventListener("click", addOrderLine);
$("#order-form").addEventListener("submit", async e => {
  e.preventDefault(); const form = new FormData(e.currentTarget);
  const lines = [...$("#order-lines").querySelectorAll(".order-line")].map(row => ({ productId: Number(row.querySelector("select").value), quantity: Number(row.querySelector("input").value) }));
  const payload = { customerName: form.get("customerName").trim(), customerEmail: form.get("customerEmail").trim(), items: lines };
  try { const order = await api("api.php?action=orders", { method: "POST", body: JSON.stringify(payload) }); closeOrderModal(); await refresh(); showView("orders"); toast(`Order ${order.invoiceNumber} created.`); showInvoice(order); }
  catch (err) { toast(err.message, true); }
});
$("#orders-table").addEventListener("click", async e => {
  const button = e.target.closest("[data-invoice]"); if (!button) return;
  try { showInvoice(await api(`api.php?action=order&id=${button.dataset.invoice}`)); } catch (err) { toast(err.message, true); }
});
async function boot() { try { await refresh(); } catch (err) { toast(`Could not connect to backend: ${err.message}`, true); } }
boot();

const profileToggle = document.getElementById('profile-toggle');
const profileMenu = document.getElementById('profile-menu');

if (profileToggle && profileMenu) {
    profileToggle.addEventListener('click', () => {
        const isOpen = !profileMenu.hidden;

        profileMenu.hidden = isOpen;
        profileToggle.setAttribute('aria-expanded', String(!isOpen));
    });

    document.addEventListener('click', (event) => {
        if (!event.target.closest('.profile-wrapper')) {
            profileMenu.hidden = true;
            profileToggle.setAttribute('aria-expanded', 'false');
        }
    });

    document.addEventListener('keydown', (event) => {
        if (event.key === 'Escape') {
            profileMenu.hidden = true;
            profileToggle.setAttribute('aria-expanded', 'false');
        }
    });
}

document.addEventListener('click', function (event) {
    const logoutLink = event.target.closest('a[href="logout.php"]');

    if (!logoutLink) return;

    const confirmed = window.confirm(
        'Are you sure you want to log out?'
    );

    if (!confirmed) {
        event.preventDefault();
    }
});
