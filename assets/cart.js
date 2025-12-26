// cart.js - Sepet işlemleri için modül

// Sepet butonlarını bağla - Event delegation ile
function bindAddToCartButtons() {
    // Eğer zaten bağlıysa tekrar bağlama
    if (window.addToCartButtonsBound) return;

    // Document'a delegate event listener ekle
    document.addEventListener('click', handleAddToCartClick);

    window.addToCartButtonsBound = true;
}

// Sepet tıklama işleyicisi
function handleAddToCartClick(e) {
    const btn = e.target.closest('.add-to-cart-btn');
    if (!btn) return;

    e.preventDefault();
    const pid = btn.getAttribute('data-product-id') || btn.dataset.productId;
    if (!pid) return;

    // Eğer buton disabled ise veya zaten işlemde ise durdur
    if (btn.disabled || btn.dataset.processing) return;

    console.log('add-to-cart button clicked for product', pid);

    // İşlemde olduğunu işaretle
    btn.dataset.processing = '1';

    if (typeof window.addToCart === 'function') {
        window.addToCart(parseInt(pid)).finally(() => {
            // İşlem bittiğinde işaret kaldır
            delete btn.dataset.processing;
        });
    }
}

// Sepete ürün ekle
window.addToCart = function (productId) {
    const loginModal = document.getElementById('login-modal');
    const loginBackdrop = document.getElementById('login-backdrop');

    fetch('/cart/add/' + productId, {
        method: 'POST',
        headers: {
            'Content-Type': 'application/x-www-form-urlencoded',
        },
        body: 'quantity=1'
    })
        .then(response => {
            if (response.status === 401 || response.status === 403) {
                if (loginModal) loginModal.classList.remove('hidden');
                if (loginBackdrop) loginBackdrop.classList.remove('hidden');
                return null;
            }

            const ct = response.headers.get('content-type') || '';
            if (!ct.includes('application/json')) {
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

            if (typeof showTempMessage === 'function') {
                showTempMessage(data.message || 'Ürün sepete eklendi!');
            } else {
                alert(data.message || 'Ürün sepete eklendi!');
            }

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

            try {
                const productCountEls = Array.from(document.querySelectorAll('.font-medium'))
                    .filter(el => /\d+\s*ürün/.test(el.textContent.trim()));
                productCountEls.forEach(el => {
                    el.textContent = newCount + ' ürün';
                });
            } catch (e) { /* ignore if DOM different */ }

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
};

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

// Export for module usage
if (typeof module !== 'undefined' && module.exports) {
    module.exports = { bindAddToCartButtons, showTempMessage };
}

// Initialize cart button bindings
bindAddToCartButtons();