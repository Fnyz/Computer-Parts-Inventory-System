// Point of sale. The cart and totals here are a preview; the server recalculates everything on checkout.
(function () {
  'use strict';

  var data = JSON.parse(document.getElementById('pos-data').textContent);
  var products = data.products;
  var byId = {};
  products.forEach(function (p) { byId[p.id] = p; });
  var cart = {}; // product id -> qty
  var category = '';
  var busy = false;
  var csrf = (document.querySelector('meta[name="csrf-token"]') || {}).content || '';
  var peso = new Intl.NumberFormat('en-PH', { style: 'currency', currency: 'PHP' });

  var $ = function (id) { return document.getElementById(id); };
  var search = $('pos-search');
  var grid = $('pos-grid');
  var discountInput = $('discount');
  var paidInput = $('paid');
  var method = $('method');

  function money(cents) { return peso.format(cents / 100); }
  function cents(value) {
    var n = parseFloat(String(value || '').replace(/[,₱\s]/g, ''));
    return isNaN(n) ? 0 : Math.round(n * 100);
  }
  function el(tag, attrs, text) {
    var node = document.createElement(tag);
    Object.keys(attrs || {}).forEach(function (k) { node.setAttribute(k, attrs[k]); });
    if (text !== undefined) { node.textContent = text; }
    return node;
  }
  function stockState(p) {
    var left = p.stock - (cart[p.id] || 0);
    if (p.stock <= 0) { return ['status-out', 'OUT']; }
    return [p.stock <= p.reorder ? 'status-low' : 'status-ok', left + ' LEFT'];
  }

  function matches() {
    var q = search.value.trim().toLowerCase();
    return products.filter(function (p) {
      if (category && p.category !== category) { return false; }
      return !q || p.sku.toLowerCase().indexOf(q) !== -1 || p.name.toLowerCase().indexOf(q) !== -1 || (p.brand || '').toLowerCase().indexOf(q) !== -1;
    });
  }

  function renderGrid() {
    var list = matches();
    grid.textContent = '';
    list.slice(0, 120).forEach(function (p) {
      var card = el('button', { type: 'button', class: 'pos-card' + (cart[p.id] ? ' in-cart' : ''), 'data-id': p.id });
      if (p.stock <= 0) { card.disabled = true; }
      card.appendChild(el('span', { class: 'sku' }, p.sku));
      card.appendChild(el('span', { class: 'name' }, p.name));
      var row = el('span', { class: 'row' });
      row.appendChild(el('span', { class: 'mono num', style: 'font-weight:700' }, money(p.price)));
      var state = stockState(p);
      var status = el('span', { class: 'status ' + state[0] });
      status.appendChild(el('span', { class: 'led' }));
      status.appendChild(document.createTextNode(state[1]));
      row.appendChild(status);
      card.appendChild(row);
      grid.appendChild(card);
    });
    $('pos-empty').hidden = list.length > 0;
  }

  function add(id, qty) {
    var p = byId[id];
    if (!p || p.stock <= 0) { return; }
    var next = Math.min(p.stock, (cart[id] || 0) + (qty || 1));
    if (next === (cart[id] || 0)) { showError('Only ' + p.stock + ' of ' + p.name + ' in stock.'); return; }
    cart[id] = next;
    hideError();
    render();
  }

  function setQty(id, qty) {
    var p = byId[id];
    qty = Math.max(0, Math.min(p.stock, qty | 0));
    if (qty === 0) { delete cart[id]; } else { cart[id] = qty; }
    render();
  }

  function totals() {
    var subtotal = 0;
    Object.keys(cart).forEach(function (id) { subtotal += byId[id].price * cart[id]; });
    var discount = discountInput ? Math.min(cents(discountInput.value), subtotal) : 0;
    var total = subtotal - discount;
    var paid = method.value === 'cash' ? cents(paidInput.value) : total;
    return { subtotal: subtotal, discount: discount, total: total, paid: paid, change: Math.max(0, paid - total) };
  }

  function renderCart() {
    var lines = $('cart-lines');
    lines.textContent = '';
    var ids = Object.keys(cart);
    var items = 0;
    ids.forEach(function (id) {
      var p = byId[id];
      items += cart[id];
      var line = el('div', { class: 'cart-line' });
      var info = el('div', { style: 'min-width:0' });
      info.appendChild(el('div', { style: 'font-weight:600' }, p.name));
      info.appendChild(el('div', { class: 'mono small muted' }, money(p.price) + ' each'));
      var qty = el('div', { class: 'qty' });
      var minus = el('button', { type: 'button', 'data-dec': id, 'aria-label': 'One less ' + p.name }, '−');
      var input = el('input', { type: 'number', min: '0', max: String(p.stock), value: String(cart[id]), 'data-qty': id, 'aria-label': 'Quantity of ' + p.name });
      var plus = el('button', { type: 'button', 'data-inc': id, 'aria-label': 'One more ' + p.name }, '+');
      qty.appendChild(minus); qty.appendChild(input); qty.appendChild(plus);
      line.appendChild(info);
      line.appendChild(qty);
      line.appendChild(el('span', { class: 'mono num r', style: 'min-width:92px' }, money(p.price * cart[id])));
      lines.appendChild(line);
    });
    $('cart-empty').hidden = ids.length > 0;
    $('cart-count').textContent = ids.length + ' LINE' + (ids.length === 1 ? '' : 'S') + ' · ' + items + ' ITEM' + (items === 1 ? '' : 'S');

    var t = totals();
    $('t-subtotal').textContent = money(t.subtotal);
    $('t-total').textContent = money(t.total);
    $('t-change').textContent = money(t.change);
    var cash = method.value === 'cash';
    $('paid-field').hidden = !cash;
    $('change-row').hidden = !cash;
    $('complete').disabled = ids.length === 0 || busy || (cash && t.paid < t.total);
  }

  function render() { renderGrid(); renderCart(); }
  function showError(message) { var box = $('pos-error'); box.querySelector('span').textContent = message; box.hidden = false; }
  function hideError() { $('pos-error').hidden = true; }

  function checkout() {
    if ($('complete').disabled) { return; }
    var t = totals();
    busy = true;
    $('complete').textContent = 'Saving…';
    renderCart();
    fetch(data.checkoutUrl, {
      method: 'POST',
      headers: { 'Content-Type': 'application/json', 'Accept': 'application/json', 'X-CSRF-Token': csrf },
      body: JSON.stringify({
        lines: Object.keys(cart).map(function (id) { return { product_id: Number(id), qty: cart[id] }; }),
        discount: discountInput ? (t.discount / 100).toFixed(2) : '0',
        payment_method: method.value,
        amount_paid: method.value === 'cash' ? (t.paid / 100).toFixed(2) : null,
        customer_name: $('customer').value
      })
    }).then(function (res) {
      return res.json().then(function (body) { return { ok: res.ok, body: body }; });
    }).then(function (result) {
      if (!result.ok) { throw new Error(result.body.error || 'The sale was not saved. Try again.'); }
      Object.keys(cart).forEach(function (id) { byId[id].stock -= cart[id]; });
      $('done-number').textContent = result.body.number;
      $('done-change').textContent = money(result.body.change);
      $('done-receipt').href = result.body.receipt_url;
      $('done-sale').href = result.body.sale_url;
      $('cart-form').hidden = true;
      $('pos-done').hidden = false;
      $('new-sale').focus();
    }).catch(function (err) {
      showError(err.message || 'The sale was not saved. Check your connection and try again.');
    }).then(function () {
      busy = false;
      $('complete').textContent = 'Complete sale · F9';
      renderCart();
    });
  }

  function reset() {
    cart = {};
    if (discountInput) { discountInput.value = '0'; }
    paidInput.value = '';
    $('customer').value = '';
    method.value = 'cash';
    hideError();
    $('pos-done').hidden = true;
    $('cart-form').hidden = false;
    search.value = '';
    render();
    search.focus();
  }

  // ---- Events ----
  grid.addEventListener('click', function (e) {
    var card = e.target.closest('.pos-card');
    if (card && !card.disabled) { add(Number(card.getAttribute('data-id'))); }
  });
  search.addEventListener('input', renderGrid);
  search.addEventListener('keydown', function (e) {
    if (e.key !== 'Enter') { return; }
    e.preventDefault();
    var q = search.value.trim().toLowerCase();
    if (!q) { return; }
    // A barcode scanner types the SKU and presses Enter: exact SKU first, then the only match
    var exact = products.filter(function (p) { return p.sku.toLowerCase() === q; })[0];
    var list = matches();
    var pick = exact || (list.length === 1 ? list[0] : null) || list.filter(function (p) { return p.stock > 0; })[0];
    if (pick) { add(pick.id); search.value = ''; renderGrid(); } else { showError('No part matches “' + search.value + '”.'); }
  });
  $('pos-categories').addEventListener('click', function (e) {
    var chip = e.target.closest('[data-category]');
    if (!chip) { return; }
    category = chip.getAttribute('data-category');
    document.querySelectorAll('#pos-categories .chip').forEach(function (c) { c.classList.toggle('on', c === chip); });
    renderGrid();
  });
  $('cart-lines').addEventListener('click', function (e) {
    var inc = e.target.closest('[data-inc]');
    var dec = e.target.closest('[data-dec]');
    if (inc) { add(Number(inc.getAttribute('data-inc'))); }
    if (dec) { var id = Number(dec.getAttribute('data-dec')); setQty(id, (cart[id] || 0) - 1); }
  });
  $('cart-lines').addEventListener('change', function (e) {
    if (e.target.matches('[data-qty]')) { setQty(Number(e.target.getAttribute('data-qty')), parseInt(e.target.value, 10) || 0); }
  });
  [discountInput, paidInput, method].forEach(function (input) {
    if (input) { input.addEventListener('input', renderCart); input.addEventListener('change', renderCart); }
  });
  $('complete').addEventListener('click', checkout);
  $('clear').addEventListener('click', function () { if (Object.keys(cart).length === 0 || window.confirm('Clear the cart?')) { reset(); } });
  $('new-sale').addEventListener('click', reset);
  document.addEventListener('keydown', function (e) {
    if (e.key === 'F2') { e.preventDefault(); search.focus(); search.select(); }
    if (e.key === 'F9') { e.preventDefault(); checkout(); }
  });

  render();
})();
