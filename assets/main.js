// main.js - Ana uygulama başlatma modülü

// Sayfa başlatma fonksiyonu
function initPage() {
    // Dinamik component'leri yükle (eğer placeholder'lar varsa)
    const hasPlaceholders = document.querySelector('#navbar-placeholder, #hero-placeholder, #content-placeholder, #cart-placeholder, #footer-placeholder');
    if (hasPlaceholders) {
        loadComponents();
    } else {
        // Doğrudan interactive özelikleri başlat
        initializeInteractiveFeatures();
    }
}

// Çeşitli navigation yöntemleri için init çağrısı
document.addEventListener('turbo:load', initPage);
document.addEventListener('turbolinks:load', initPage);
window.initPage = initPage;

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

// İnteraktif özellikleri başlat
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
    if (typeof initNavbar === 'function') {
        initNavbar();
    }

    // 3. Slider başlat
    if (typeof initSlider === 'function') {
        initSlider();
    }

    // 4. Sepet butonlarını bağla
    if (typeof bindAddToCartButtons === 'function') {
        bindAddToCartButtons();
    }

    // 5. Favori butonlarını bağla
    if (typeof bindFavoriteButtons === 'function') {
        bindFavoriteButtons();
    }

    console.log("Tüm parçalar yüklendi ve özellikler aktif edildi.");
}

// Sayfa yenilendikçe buton bindinglerini de yenile
window.addEventListener('load', () => {
    if (typeof bindAddToCartButtons === 'function') {
        bindAddToCartButtons();
    }
    if (typeof bindFavoriteButtons === 'function') {
        bindFavoriteButtons();
    }
});

// Export for module usage
if (typeof module !== 'undefined' && module.exports) {
    module.exports = { initPage, initializeInteractiveFeatures };
}