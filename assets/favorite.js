// favorite.js - Favori işlemleri için modül

// Favori butonlarını bağla - Event delegation ile
function bindFavoriteButtons() {
    // Eğer zaten bağlıysa tekrar bağlama
    if (window.favoriteButtonsBound) return;

    // Document'a delegate event listener ekle
    document.addEventListener('click', handleFavoriteClick);

    window.favoriteButtonsBound = true;
}

// Favori tıklama işleyicisi
function handleFavoriteClick(e) {
    const btn = e.target.closest('.favorite-btn');
    if (!btn) return;

    e.preventDefault();
    const pid = btn.getAttribute('data-product-id') || btn.dataset.productId;
    if (!pid) return;

    // Eğer buton disabled ise veya zaten işlemde ise durdur
    if (btn.disabled || btn.dataset.processing) return;

    console.log('favorite button clicked for product', pid);

    // İşlemde olduğunu işaretle
    btn.dataset.processing = '1';

    if (typeof window.toggleFavorite === 'function') {
        window.toggleFavorite(parseInt(pid)).finally(() => {
            // İşlem bittiğinde işaret kaldır
            delete btn.dataset.processing;
        });
    }
}

// Favori toggle - Promise döndüren versiyon
window.toggleFavorite = function (productId) {
    const csrfToken = document.querySelector('meta[name="csrf-token"]')?.getAttribute('content') || '';

    return fetch('/favorite/toggle/' + productId, {
        method: 'POST',
        headers: {
            'Content-Type': 'application/x-www-form-urlencoded',
        },
        body: new URLSearchParams({
            '_token': csrfToken
        })
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

            if (typeof showTempMessage === 'function') {
                showTempMessage(data.message || 'Favori güncellendi!');
            } else {
                alert(data.message || 'Favori güncellendi!');
            }

            const btn = document.querySelector(`.favorite-btn[data-product-id="${productId}"]`);
            if (btn) {
                const icon = btn.querySelector('i[data-lucide="heart"]') || btn.querySelector('i');

                if (data.isFavorite) {
                    // Favoriye eklendi - kırmızı göster
                    btn.classList.remove('bg-gray-600', 'hover:bg-gray-700');
                    btn.classList.add('bg-red-600', 'hover:bg-red-700');

                    // Text'i değiştir (icon'dan sonraki text node)
                    const textNode = Array.from(btn.childNodes).find(node =>
                        node.nodeType === Node.TEXT_NODE && node.textContent.trim()
                    );
                    if (textNode) {
                        textNode.textContent = ' Favorilerden Çıkar';
                    }
                } else {
                    // Favoriden çıkarıldı - gri göster
                    btn.classList.remove('bg-red-600', 'hover:bg-red-700');
                    btn.classList.add('bg-gray-600', 'hover:bg-gray-700');

                    // Text'i değiştir
                    const textNode = Array.from(btn.childNodes).find(node =>
                        node.nodeType === Node.TEXT_NODE && node.textContent.trim()
                    );
                    if (textNode) {
                        textNode.textContent = ' Favoriye Ekle';
                    }
                }
            }
        })
        .catch(error => {
            console.error('toggleFavorite hata:', error);
            alert('Favori işlemi sırasında hata oluştu.');
        });
};

// Export for module usage
if (typeof module !== 'undefined' && module.exports) {
    module.exports = { bindFavoriteButtons };
}