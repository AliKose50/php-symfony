
// Main Application Logic

// initPage: sayfa/komponent init'lerini başlatır
function initPage() {
    // Dinamik component'leri yükle (eğer placeholder'lar varsa)
    const hasPlaceholders = document.querySelector('#navbar-placeholder, #hero-placeholder, #content-placeholder, #cart-placeholder, #footer-placeholder');
    if (hasPlaceholders) {
        loadComponents();
    } else {
        // Doğrudan interactive özelikleri başlat (ürün sayfası, sepet, vb)
        initializeInteractiveFeatures();
    }
}

// Çeşitli navigation yöntemleri için init çağrısı

document.addEventListener('turbo:load', initPage);
document.addEventListener('turbolinks:load', initPage);
window.initPage = initPage;

// Sayfa yenilendikçe buton bindinglerini de yenile (navigation olayları)
window.addEventListener('load', bindAddToCartButtons);
window.addEventListener('load', bindFavoriteButtons);

// HTML Parçalarını Yükleme Fonksiyonu
async function loadComponents() {
    const components = [
        { id: 'navbar-placeholder', url: 'views/navbar.html' },
        { id: 'hero-placeholder', url: 'views/hero.html' },
        { id: 'content-placeholder', url: 'views/content.html' },
        { id: 'cart-placeholder', url: 'views/cart.html' },
        { id: 'footer-placeholder', url: 'views/footer.html' }
    ];

    try {
        // Tüm dosyaları paralel olarak çek
        const promises = components.map(async (comp) => {
            const element = document.getElementById(comp.id);
            if (element) {
                const response = await fetch(comp.url);
                if (!response.ok) throw new Error(`Dosya yüklenemedi: ${comp.url}`);
                const html = await response.text();
                element.innerHTML = html;
            }
        });

        // Hepsi yüklenene kadar bekle
        await Promise.all(promises);

        // Yükleme bittikten sonra interaktif özellikleri başlat
        initializeInteractiveFeatures();

    } catch (error) {
        console.error("Bileşenler yüklenirken hata oluştu:", error);
        // Hata olsa bile interactive özelikleri başlat
        initializeInteractiveFeatures();
    }
}

function initializeInteractiveFeatures() {
    // 1. İkonları oluştur
    if (window.lucide) {
        if (typeof lucide.replace === 'function') {
            lucide.replace();
        } else if (typeof lucide.createIcons === 'function') {
            lucide.createIcons();
        }
    }

    // 2. Navbar fonksiyonlarını başlat
    initNavbar();

    // 3. Slider Başlat
    initSlider();

    // 4. Bind add-to-cart buttons
    bindAddToCartButtons();

    // 5. Bind favorite buttons
    bindFavoriteButtons();

    console.log("Tüm parçalar yüklendi ve özellikler aktif edildi.");
}


// Slider Logic
let currentSlide = 0;
function initSlider() {
    const slides = document.querySelectorAll('.slide');
    if (slides.length === 0) return;

    const indicatorsContainer = document.getElementById('slider-indicators');

    // Create indicators
    if (indicatorsContainer && indicatorsContainer.children.length === 0) {
        slides.forEach((_, idx) => {
            const dot = document.createElement('button');
            dot.className = `w-2 h-2 rounded-full transition-all duration-300 ${idx === 0 ? 'bg-white w-8' : 'bg-white/50'}`;
            dot.onclick = () => goToSlide(idx);
            indicatorsContainer.appendChild(dot);
        });
    }

    // Start auto slide
    setInterval(() => changeSlide(1), 5000);
}

// Make these global so HTML onclick attributes can find them
window.updateSlides = function () {
    const slides = document.querySelectorAll('.slide');
    const indicatorsContainer = document.getElementById('slider-indicators');
    const indicators = indicatorsContainer ? indicatorsContainer.children : [];

    slides.forEach((slide, idx) => {
        if (idx === currentSlide) {
            slide.classList.add('active');
        } else {
            slide.classList.remove('active');
        }
    });

    Array.from(indicators).forEach((dot, idx) => {
        if (idx === currentSlide) {
            dot.className = 'w-2 h-2 rounded-full transition-all duration-300 bg-white w-8';
        } else {
            dot.className = 'w-2 h-2 rounded-full transition-all duration-300 bg-white/50';
        }
    });
}

window.changeSlide = function (direction) {
    const slides = document.querySelectorAll('.slide');
    if (slides.length === 0) return;
    currentSlide = (currentSlide + direction + slides.length) % slides.length;
    window.updateSlides();
}

window.goToSlide = function (index) {
    currentSlide = index;
    window.updateSlides();
}

// Mobile Menu Logic
window.toggleMobileMenu = function () {
    const menu = document.getElementById('mobile-menu');
    if (menu) menu.classList.toggle('hidden');
}

// User Dropdown Menu Logic
window.toggleUserMenu = function () {
    const dropdown = document.getElementById('user-dropdown-menu');
    if (dropdown) {
        dropdown.classList.toggle('hidden');
    }
}

// Close user dropdown when clicking outside
document.addEventListener('click', function (event) {
    const dropdown = document.getElementById('user-dropdown-menu');
    const userBtn = event.target.closest('button[onclick*="toggleUserMenu"]');

    if (dropdown && !userBtn && !dropdown.contains(event.target)) {
        dropdown.classList.add('hidden');
    }
});

// Cart Logic
// Cart toggle: show/hide cart sidebar and backdrop
window.toggleCart = function () {
    const sidebar = document.getElementById('cart-sidebar');
    const backdrop = document.getElementById('cart-backdrop');
    if (sidebar) sidebar.classList.toggle('hidden');
    if (backdrop) backdrop.classList.toggle('hidden');
}

// Update cart item quantity
window.updateCartItem = function (itemId, delta) {
    fetch('/cart/update/' + itemId, {
        method: 'POST',
        headers: {
            'Content-Type': 'application/x-www-form-urlencoded',
        },
        body: 'delta=' + delta
    })
        .then(response => {
            if (response.status === 401) {
                alert('Lütfen giriş yapın');
                return null;
            }
            return response.json();
        })
        .then(data => {
            if (!data) return;
            if (data.success) {
                // Update item quantity display
                const qtySpan = document.querySelector('#cart-item-' + itemId + ' .font-medium.px-3');
                if (qtySpan) qtySpan.textContent = data.quantity;

                // Update line total
                const lineTotalEl = document.getElementById('item-total-' + itemId);
                if (lineTotalEl) lineTotalEl.textContent = (data.lineTotal || 0).toFixed(2).replace('.', ',') + ' ₺';

                // Update cart counters
                const cartCountEls = document.querySelectorAll('.cart-count');
                cartCountEls.forEach(el => { if (data.cartCount !== undefined) el.textContent = data.cartCount; });

                // Update subtotal/total
                const subtotalEl = document.getElementById('cart-subtotal');
                const totalEl = document.getElementById('cart-total');
                if (subtotalEl) subtotalEl.textContent = (data.cartTotal || 0).toFixed(2).replace('.', ',') + ' ₺';
                if (totalEl) totalEl.textContent = (data.cartTotal || 0).toFixed(2).replace('.', ',') + ' ₺';
            } else {
                alert('Hata: ' + (data.error || 'Bilinmeyen hata'));
            }
        })
        .catch(error => {
            console.error('updateCartItem hata:', error);
            alert('Güncelleme sırasında hata oluştu');
        });
}

// Remove cart item
window.removeCartItem = function (itemId) {
    if (confirm('Bu ürünü sepetten kaldırmak istediğinize emin misiniz?')) {
        fetch('/cart/remove/' + itemId, {
            method: 'POST',
            headers: {
                'Content-Type': 'application/x-www-form-urlencoded',
            }
        })
            .then(response => {
                if (response.status === 401) {
                    alert('Lütfen giriş yapın');
                    return null;
                }
                return response.json();
            })
            .then(data => {
                if (!data) return;
                if (data.success) {
                    // remove item DOM
                    const itemEl = document.getElementById('cart-item-' + itemId);
                    if (itemEl) itemEl.remove();

                    // update counters and totals
                    const cartCountEls = document.querySelectorAll('.cart-count');
                    cartCountEls.forEach(el => { if (data.cartCount !== undefined) el.textContent = data.cartCount; });
                    const subtotalEl = document.getElementById('cart-subtotal');
                    const totalEl = document.getElementById('cart-total');
                    if (subtotalEl) subtotalEl.textContent = (data.cartTotal || 0).toFixed(2).replace('.', ',') + ' ₺';
                    if (totalEl) totalEl.textContent = (data.cartTotal || 0).toFixed(2).replace('.', ',') + ' ₺';

                    // if cart empty, show empty message
                    const remaining = document.querySelectorAll('[id^="cart-item-"]').length;
                    if (remaining === 0) {
                        const mainEl = document.querySelector('main');
                        if (mainEl) {
                            mainEl.innerHTML = '<div class="p-8 bg-white rounded-lg text-center"><p class="text-gray-700">Sepetinizde ürün bulunmuyor.</p><a href="/product" class="mt-4 inline-block bg-primary-600 text-white px-4 py-2 rounded">Alışverişe Başla</a></div>';
                        }
                    }
                } else {
                    alert('Hata: ' + (data.error || 'Bilinmeyen hata'));
                }
            })
            .catch(error => {
                console.error('removeCartItem hata:', error);
                alert('Silme sırasında hata oluştu');
            });
    }
}

// Add to Cart with Authentication Check
window.addToCart = function (productId) {
    // Sepete ekle butonuna tıklanırsa önce giriş kontrolü yap
    const loginModal = document.getElementById('login-modal');
    const loginBackdrop = document.getElementById('login-backdrop');

    // Kullanıcı giriş yapmışsa AJAX ile ürünü sepete ekle
    fetch('/cart/add/' + productId, {
        method: 'POST',
        headers: {
            'Content-Type': 'application/x-www-form-urlencoded',
        },
        body: 'quantity=1'
    })
        .then(response => {
            // Eğer yetkilendirme yoksa (401/403) login modalını aç
            if (response.status === 401 || response.status === 403) {
                if (loginModal) loginModal.classList.remove('hidden');
                if (loginBackdrop) loginBackdrop.classList.remove('hidden');
                return null;
            }

            const ct = response.headers.get('content-type') || '';
            if (!ct.includes('application/json')) {
                // Beklenmeyen cevap; debug için text olarak al
                return response.text().then(txt => {
                    console.error('Beklenmeyen cevap (JSON değil):', txt);
                    alert('Sunucudan beklenmeyen bir cevap alındı. Detaylar konsolda.');
                    return null;
                });
            }

            return response.json();
        })
        .then(data => {
            if (!data) return;

            if (data.error) {
                if (loginModal) loginModal.classList.remove('hidden');
                if (loginBackdrop) loginBackdrop.classList.remove('hidden');
                alert(data.error);
                return;
            }

            // Başarılı ekleme: küçük bir bildirim göster ve cart sayacını güncelle
            if (typeof showTempMessage === 'function') {
                showTempMessage(data.message || 'Ürün sepete eklendi!');
            } else {
                alert(data.message || 'Ürün sepete eklendi!');
            }

            // Prefer server-provided cartCount; fall back to incrementing local count
            const cartCountEls = document.querySelectorAll('.cart-count');
            let newCount;
            if (typeof data.cartCount !== 'undefined') {
                newCount = data.cartCount;
            } else {
                const c0 = document.querySelector('.cart-count');
                const prev = c0 ? parseInt((c0.textContent || '').replace(/[^0-9]/g, '')) || 0 : 0;
                newCount = prev + 1;
            }
            cartCountEls.forEach(el => { el.textContent = newCount; });

            // Also update product page's inline counter (if present) which shows like "X ürün"
            try {
                const productCountEls = Array.from(document.querySelectorAll('.font-medium'))
                    .filter(el => /\d+\s*ürün/.test(el.textContent.trim()));
                productCountEls.forEach(el => {
                    el.textContent = newCount + ' ürün';
                });
            } catch (e) { /* ignore if DOM different */ }

            // Temporarily change the Add button text to indicate success (if found)
            try {
                const addBtn = document.querySelector(`button[data-product-id="${productId}"]`) || document.querySelector(`#add-to-cart-${productId}`);
                if (addBtn) {
                    const orig = addBtn.textContent;
                    addBtn.textContent = 'Eklendi';
                    setTimeout(() => { addBtn.textContent = orig; }, 1400);
                }
            } catch (e) { /* ignore */ }
        })
        .catch(error => {
            console.error('addToCart hata:', error);
            alert('Sepete ekleme işlemi sırasında hata oluştu. Konsolu kontrol edin.');
        });
}

// Favori toggle fonksiyonu
window.toggleFavorite = function (productId) {
    // CSRF token al
    const csrfToken = document.querySelector('meta[name="csrf-token"]')?.getAttribute('content') || '';

    fetch('/favorite/toggle/' + productId, {
        method: 'POST',
        headers: {
            'Content-Type': 'application/x-www-form-urlencoded',
            'X-CSRF-Token': csrfToken,
        },
    })
        .then(response => {
            if (response.status === 401 || response.status === 403) {
                alert('Lütfen giriş yapın');
                return null;
            }

            const ct = response.headers.get('content-type') || '';
            if (!ct.includes('application/json')) {
                return response.text().then(txt => {
                    console.error('Beklenmeyen cevap:', txt);
                    alert('Sunucudan beklenmeyen bir cevap alındı.');
                    return null;
                });
            }

            return response.json();
        })
        .then(data => {
            if (!data) return;

            if (data.error) {
                alert(data.error);
                return;
            }

            // Başarılı: mesaj göster ve butonu güncelle
            if (typeof showTempMessage === 'function') {
                showTempMessage(data.message || 'Favori güncellendi!');
            } else {
                alert(data.message || 'Favori güncellendi!');
            }

            // Butonu güncelle
            const btn = document.querySelector(`.favorite-btn[data-product-id="${productId}"]`);
            if (btn) {
                if (data.isFavorite) {
                    btn.textContent = '💖 Zaten Favoride';
                    btn.className = 'flex-1 bg-gray-600 text-white font-semibold py-3 px-4 rounded-lg cursor-not-allowed';
                    btn.disabled = true;
                } else {
                    btn.textContent = '❤️ Favoriye Ekle';
                    btn.className = 'flex-1 bg-red-600 hover:bg-red-700 text-white font-semibold py-3 px-4 rounded-lg transition-colors duration-200 favorite-btn';
                    btn.disabled = false;
                }
            }
        })
        .catch(error => {
            console.error('toggleFavorite hata:', error);
            alert('Favori işlemi sırasında hata oluştu.');
        });
}

// Küçük geçici bildirim göster
function showTempMessage(msg) {
    try {
        const el = document.createElement('div');
        el.textContent = msg;
        el.style = 'position:fixed; top:1rem; right:1rem; background:#16a34a; color:white; padding:8px 12px; border-radius:6px; box-shadow:0 6px 18px rgba(0,0,0,0.12); z-index:9999; font-family:Inter, sans-serif;';
        document.body.appendChild(el);
        setTimeout(() => el.remove(), 3000);
    } catch (e) {
        console.log(msg);
    }
}

// Login Modal Logic
window.toggleLoginModal = function () {
    const modal = document.getElementById('login-modal');
    const backdrop = document.getElementById('login-backdrop');
    if (modal) modal.classList.toggle('hidden');
    if (backdrop) backdrop.classList.toggle('hidden');
}

tailwind.config = {
    theme: {
        extend: {
            fontFamily: {
                sans: ['Inter', 'sans-serif'],
            },
            colors: {
                primary: {
                    50: '#eff6ff',
                    100: '#dbeafe',
                    500: '#3b82f6',
                    600: '#2563eb',
                    700: '#1d4ed8',
                    900: '#1e3a8a',
                }
            }
        }
    }
}

// Bind click handlers for add-to-cart buttons (non-inline, resilient)
// Her sayfa yüklemesinde bindleri yenile (önceki event listener'ları sıfırla)
function bindAddToCartButtons() {
    const buttons = document.querySelectorAll('.add-to-cart-btn');
    buttons.forEach(btn => {
        // Eski event listener'ları kaldırmak için butonu klonla ve değiştir
        // (veya basitçe yeni listener ekle, eski olanlar çağrılmayacak)
        // Sadece one-time binding için güvenli bir yol: element'i yenile

        // Daha basit: her listener'a bir marker koy ve kontrol et
        if (btn.dataset.addToCartBound) return;

        btn.addEventListener('click', (e) => {
            e.preventDefault();
            const pid = btn.getAttribute('data-product-id') || btn.dataset.productId;
            if (!pid) return;
            console.log('add-to-cart button clicked for product', pid); // debug log
            if (typeof window.addToCart === 'function') {
                window.addToCart(parseInt(pid));
            }
        });
        btn.dataset.addToCartBound = '1';
    });
}

function bindFavoriteButtons() {
    const buttons = document.querySelectorAll('.favorite-btn');
    buttons.forEach(btn => {
        if (btn.dataset.favoriteBound) return;

        btn.addEventListener('click', (e) => {
            e.preventDefault();
            const pid = btn.getAttribute('data-product-id') || btn.dataset.productId;
            if (!pid) return;
            console.log('favorite button clicked for product', pid);
            if (typeof window.toggleFavorite === 'function') {
                window.toggleFavorite(parseInt(pid));
            }
        });
        btn.dataset.favoriteBound = '1';
    });
}

// Navbar Functions
function initNavbar() {
    // Navbar fonksiyonları için event listener'ları başlat
    // Bu fonksiyonlar global olarak tanımlandı, burada sadece başlatma işlemi
    console.log("Navbar initialized");
}

function toggleUserMenu() {
    const menu = document.getElementById('user-dropdown-menu');
    if (menu) {
        menu.classList.toggle('hidden');
    }
}

function toggleMobileMenu() {
    const menu = document.getElementById('mobile-menu');
    if (menu) {
        menu.classList.toggle('hidden');
    }
}

function toggleLoginModal() {
    // Login modal açma fonksiyonu - eğer modal varsa
    const modal = document.getElementById('login-modal');
    if (modal) {
        modal.classList.toggle('hidden');
    } else {
        // Modal yoksa login sayfasına yönlendir
        window.location.href = '/login';
    }
}

// Close dropdowns when clicking outside
document.addEventListener('click', function (event) {
    const userMenu = document.getElementById('user-dropdown-menu');
    const mobileMenu = document.getElementById('mobile-menu');

    if (userMenu && !event.target.closest('.relative')) {
        userMenu.classList.add('hidden');
    }

    if (mobileMenu && !event.target.closest('#mobile-menu') && !event.target.closest('button[onclick="toggleMobileMenu()"]')) {
        mobileMenu.classList.add('hidden');
    }
});
