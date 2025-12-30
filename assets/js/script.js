// Data Keranjang
let cart = [];

// DOM Elements
const productsContainer = document.querySelector('.products-container');
const cartItemsContainer = document.querySelector('.cart-items');
const subtotalElement = document.querySelector('.subtotal');
const discountElement = document.querySelector('.discount');
const totalPriceElement = document.querySelector('.total-price');
const btnClear = document.querySelector('.btn-clear');
const btnCheckout = document.querySelector('.btn-checkout');
const categoryItems = document.querySelectorAll('.category-item');
const submenuItems = document.querySelectorAll('.submenu-item');
const btnViews = document.querySelectorAll('.btn-view');

// Format Rupiah
function formatRupiah(amount) {
    return new Intl.NumberFormat('id-ID', {
        style: 'currency',
        currency: 'IDR',
        minimumFractionDigits: 0
    }).format(amount);
}

// Get product data from PHP-generated HTML
function getProductData(productElement) {
    const productId = parseInt(productElement.querySelector('.btn-add').dataset.id);
    const productName = productElement.querySelector('.product-name').textContent;
    const productPrice = parseInt(productElement.querySelector('.product-price').textContent.replace(/[^\d]/g, ''));
    const productStock = parseInt(productElement.querySelector('.product-stock').textContent.match(/\d+/)[0]);
    const productImage = productElement.querySelector('.product-image img').src;
    
    return {
        id: productId,
        name: productName,
        price: productPrice,
        stock: productStock,
        image: productImage
    };
}

// Get all products from the page
function getAllProducts() {
    const productCards = document.querySelectorAll('.product-card');
    const products = [];
    
    productCards.forEach(card => {
        products.push(getProductData(card));
    });
    
    return products;
}

// Get product by ID
function getProductById(id) {
    const products = getAllProducts();
    return products.find(product => product.id === id);
}

// Add to cart
function addToCart(productId) {
    console.log('addToCart called with productId:', productId);
    
    const product = getProductById(productId);
    
    if (!product) {
        console.error('Product not found:', productId);
        return;
    }
    
    const existingItem = cart.find(item => item.id === productId);
    
    if (existingItem) {
        if (existingItem.quantity < product.stock) {
            existingItem.quantity += 1;
            console.log('Increased quantity to:', existingItem.quantity);
        } else {
            alert(`Stok ${product.name} tidak mencukupi!`);
            return;
        }
    } else {
        cart.push({
            id: productId,
            quantity: 1,
            name: product.name,
            price: product.price,
            image: product.image
        });
        console.log('Added new item to cart');
    }
    
    renderCart();
    showNotification(`${product.name} ditambahkan ke keranjang`);
    updateCartCounter();
}

// Update cart item quantity
function updateCartItemQuantity(productId, change) {
    const product = getProductById(productId);
    const cartItem = cart.find(item => item.id === productId);
    
    if (!cartItem || !product) return;
    
    const newQuantity = cartItem.quantity + change;
    
    if (newQuantity < 1) {
        removeFromCart(productId);
        return;
    }
    
    if (newQuantity > product.stock) {
        alert(`Stok ${product.name} tidak mencukupi!`);
        return;
    }
    
    cartItem.quantity = newQuantity;
    renderCart();
    updateCartCounter();
}

// Set cart item quantity
function setCartItemQuantity(productId, quantity) {
    const product = getProductById(productId);
    const cartItem = cart.find(item => item.id === productId);
    
    if (!cartItem || !product) return;
    
    if (quantity < 1) {
        removeFromCart(productId);
        return;
    }
    
    if (quantity > product.stock) {
        alert(`Stok ${product.name} tidak mencukupi!`);
        cartItem.quantity = product.stock;
    } else {
        cartItem.quantity = quantity;
    }
    
    renderCart();
    updateCartCounter();
}

// Remove from cart
function removeFromCart(productId) {
    const product = getProductById(productId);
    cart = cart.filter(item => item.id !== productId);
    renderCart();
    updateCartCounter();
    
    if (product) {
        showNotification(`${product.name} dihapus dari keranjang`);
    }
}

// Clear cart
function clearCart() {
    if (cart.length === 0) return;
    
    if (confirm('Apakah Anda yakin ingin mengosongkan keranjang?')) {
        cart = [];
        renderCart();
        updateCartCounter();
        showNotification('Keranjang dikosongkan');
    }
}

// Render cart
function renderCart() {
    cartItemsContainer.innerHTML = '';
    
    if (cart.length === 0) {
        cartItemsContainer.innerHTML = '<p class="empty-cart">Keranjang belanja kosong</p>';
        subtotalElement.textContent = formatRupiah(0);
        discountElement.textContent = formatRupiah(0);
        totalPriceElement.textContent = formatRupiah(0);
        return;
    }
    
    let subtotal = 0;
    
    cart.forEach(item => {
        const totalPrice = item.price * item.quantity;
        subtotal += totalPrice;
        
        const cartItemElement = document.createElement('div');
        cartItemElement.className = 'cart-item';
        cartItemElement.innerHTML = `
            <div class="cart-item-image">
                <img src="${item.image}" alt="${item.name}" onerror="this.src='assets/images/default-product.png'">
            </div>
            <div class="cart-item-details">
                <h4 class="cart-item-name">${item.name}</h4>
                <p class="cart-item-price">${formatRupiah(item.price)}</p>
                <div class="cart-item-actions">
                    <div class="quantity-control">
                        <button class="quantity-btn decrease" data-id="${item.id}">-</button>
                        <input type="number" class="quantity-input" value="${item.quantity}" min="1" max="${getProductById(item.id)?.stock || 1}" data-id="${item.id}">
                        <button class="quantity-btn increase" data-id="${item.id}">+</button>
                    </div>
                    <button class="btn-remove" data-id="${item.id}">
                        <i class="fas fa-trash"></i>
                    </button>
                </div>
            </div>
        `;
        
        cartItemsContainer.appendChild(cartItemElement);
    });
    
    // Calculate discount (10% if subtotal > 100000)
    const discount = subtotal > 100000 ? subtotal * 0 : 0;
    const total = subtotal - discount;
    
    subtotalElement.textContent = formatRupiah(subtotal);
    discountElement.textContent = formatRupiah(discount);
    totalPriceElement.textContent = formatRupiah(total);
    
    // Add event listeners for quantity buttons
    attachCartEventListeners();
}

// Attach event listeners to cart items
function attachCartEventListeners() {
    document.querySelectorAll('.quantity-btn.decrease').forEach(button => {
        button.addEventListener('click', (e) => {
            const productId = parseInt(e.target.dataset.id);
            updateCartItemQuantity(productId, -1);
        });
    });
    
    document.querySelectorAll('.quantity-btn.increase').forEach(button => {
        button.addEventListener('click', (e) => {
            const productId = parseInt(e.target.dataset.id);
            updateCartItemQuantity(productId, 1);
        });
    });
    
    document.querySelectorAll('.quantity-input').forEach(input => {
        input.addEventListener('change', (e) => {
            const productId = parseInt(e.target.dataset.id);
            const newQuantity = parseInt(e.target.value);
            setCartItemQuantity(productId, newQuantity);
        });
    });
    
    document.querySelectorAll('.btn-remove').forEach(button => {
        button.addEventListener('click', (e) => {
            const productId = parseInt(e.target.closest('.btn-remove').dataset.id);
            removeFromCart(productId);
        });
    });
}

// Update cart counter
function updateCartCounter() {
    const totalItems = cart.reduce((total, item) => total + item.quantity, 0);
    
    // Update cart badge if exists
    const cartBadge = document.querySelector('.cart-badge');
    if (cartBadge) {
        cartBadge.textContent = totalItems;
        cartBadge.style.display = totalItems > 0 ? 'flex' : 'none';
    }
}

// Show notification
function showNotification(message) {
    // Remove existing notifications
    document.querySelectorAll('.notification').forEach(notification => notification.remove());
    
    const notification = document.createElement('div');
    notification.className = 'notification';
    notification.textContent = message;
    document.body.appendChild(notification);
    
    setTimeout(() => {
        notification.classList.add('show');
    }, 10);
    
    setTimeout(() => {
        notification.classList.remove('show');
        setTimeout(() => {
            notification.remove();
        }, 300);
    }, 3000);
}

// Checkout function
function checkout() {
    if (cart.length === 0) {
        alert('Keranjang belanja kosong!');
        return;
    }
    
    // Save cart to localStorage
    localStorage.setItem('shoppingCart', JSON.stringify(cart));
    
    // Calculate and save total
    const subtotal = cart.reduce((total, item) => total + (item.price * item.quantity), 0);
    const discount = subtotal > 100000 ? subtotal * 0 : 0;
    const total = subtotal - discount;
    
    const totalAmount = { subtotal, discount, total };
    localStorage.setItem('totalAmount', JSON.stringify(totalAmount));
    
    // Redirect to payment page
    window.location.href = 'bayar.php';
}

// Toggle category submenu
function setupCategoryToggle() {
    categoryItems.forEach(item => {
        if (item.classList.contains('has-submenu')) {
            const title = item.querySelector('.category-title');
            title.addEventListener('click', (e) => {
                e.preventDefault();
                item.classList.toggle('active');
            });
        }
    });
}

// Filter products by category
function setupCategoryFilter() {
    submenuItems.forEach(item => {
        item.addEventListener('click', () => {
            // Remove active class from all items
            submenuItems.forEach(i => i.classList.remove('active'));
            // Add active class to clicked item
            item.classList.add('active');
            
            const categoryId = item.dataset.category;
            filterProductsByCategory(categoryId);
        });
    });
}

// Filter products by category
function filterProductsByCategory(categoryId) {
    const productCards = document.querySelectorAll('.product-card');
    
    productCards.forEach(card => {
        const cardCategory = card.dataset.category;
        if (categoryId === 'all' || cardCategory === categoryId) {
            card.style.display = 'block';
        } else {
            card.style.display = 'none';
        }
    });
}

// Setup view toggle
function setupViewToggle() {
    let currentView = 'grid';
    
    btnViews.forEach((btn, index) => {
        btn.addEventListener('click', () => {
            btnViews.forEach(b => b.classList.remove('active'));
            btn.classList.add('active');
            currentView = index === 0 ? 'grid' : 'list';
            
            // Toggle view
            if (productsContainer) {
                productsContainer.className = 'products-container';
                productsContainer.classList.add(currentView + '-view');
            }
        });
    });
}

// Setup product add buttons
function setupProductAddButtons() {
    document.addEventListener('click', (e) => {
        const btn = e.target.closest('.btn-add');
        if (btn && !btn.disabled) {
            const productId = parseInt(btn.dataset.id);
            addToCart(productId);
        }
    });
}

// Setup admin sidebar toggle
function setupAdminSidebar() {
    const menuToggle = document.getElementById('menuToggle');
    const sidebar = document.getElementById('sidebar');
    
    if (menuToggle && sidebar) {
        menuToggle.addEventListener('click', function() {
            sidebar.classList.toggle('active');
        });
        
        // Close sidebar when clicking outside
        document.addEventListener('click', function(event) {
            if (window.innerWidth <= 768 && 
                !sidebar.contains(event.target) && 
                !menuToggle.contains(event.target) &&
                sidebar.classList.contains('active')) {
                sidebar.classList.remove('active');
            }
        });
    }
}

// Initialize everything
function initialize() {
    // Setup event listeners
    if (btnClear) btnClear.addEventListener('click', clearCart);
    if (btnCheckout) btnCheckout.addEventListener('click', checkout);
    
    // Setup features
    setupCategoryToggle();
    setupCategoryFilter();
    setupViewToggle();
    setupProductAddButtons();
    setupAdminSidebar();
    
    // Initial render
    renderCart();
    updateCartCounter();
    
    // Add notification styles
    addNotificationStyles();
}

// Add notification styles to head
function addNotificationStyles() {
    if (!document.querySelector('#notification-styles')) {
        const style = document.createElement('style');
        style.id = 'notification-styles';
        style.textContent = `
            .notification {
                position: fixed;
                bottom: 20px;
                left: 50%;
                transform: translateX(-50%);
                background-color: #333;
                color: white;
                padding: 12px 24px;
                border-radius: 4px;
                opacity: 0;
                transition: opacity 0.3s ease;
                z-index: 10000;
                font-size: 14px;
                max-width: 90%;
                text-align: center;
            }
            
            .notification.show {
                opacity: 1;
            }
            
            .cart-badge {
                position: absolute;
                top: -5px;
                right: -5px;
                background-color: var(--danger-color);
                color: white;
                border-radius: 50%;
                width: 18px;
                height: 18px;
                font-size: 10px;
                display: flex;
                align-items: center;
                justify-content: center;
                display: none;
            }
            
            /* View styles */
            .products-container.grid-view {
                display: grid;
                grid-template-columns: repeat(auto-fill, minmax(200px, 1fr));
                gap: 1.5rem;
            }
            
            .products-container.list-view {
                display: flex;
                flex-direction: column;
                gap: 1rem;
            }
            
            .products-container.list-view .product-card {
                display: flex;
                align-items: center;
                padding: 1rem;
                border: 1px solid #e9ecef;
                border-radius: var(--border-radius);
            }
            
            .products-container.list-view .product-image {
                width: 80px;
                height: 80px;
                margin-right: 1rem;
                flex-shrink: 0;
            }
            
            .products-container.list-view .product-info {
                flex: 1;
            }
            
            .products-container.list-view .product-actions {
                margin-left: auto;
                flex-shrink: 0;
            }
        `;
        document.head.appendChild(style);
    }
}

// Initialize when DOM is loaded
document.addEventListener('DOMContentLoaded', initialize);

// Make functions available globally for PHP onclick events
window.addToCart = addToCart;
window.updateCartItemQuantity = updateCartItemQuantity;
window.removeFromCart = removeFromCart;
window.clearCart = clearCart;
window.checkout = checkout;