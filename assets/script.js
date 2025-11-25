
// Main Application Logic

// initPage: sayfa/komponent init'lerini başlatır
function initPage() {
    loadComponents();
}

// Çeşitli navigation yöntemleri için init çağrısı
document.addEventListener('DOMContentLoaded', initPage);
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

        // Hata mesajını ekrana bas (Kullanıcı sunucu kullanmıyorsa görsün)
        const errorMsg = document.createElement('div');
        errorMsg.style = "position:fixed; top:0; left:0; width:100%; height:100%; background:white; z-index:9999; display:flex; flex-direction:column; align-items:center; justify-content:center; text-align:center; padding:20px;";
        errorMsg.innerHTML = `
            <h1 style="color:red; font-size:24px; margin-bottom:10px;">⚠️ Görüntüleme Hatası</h1>
            <p style="font-size:18px; color:#333;">Dosyalar yüklenemedi (CORS Hatası).</p>
            <p style="margin-top:10px; color:#666;">Bu yapıyı (HTML parçalama) kullanmak için dosyayı direkt çift tıklayarak açamazsınız.</p>
            <p style="font-weight:bold; margin-top:10px;">Lütfen VS Code "Live Server" eklentisi veya bir Localhost sunucusu kullanın.</p>
        `;
        document.body.appendChild(errorMsg);
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

    // 2. Navbar Scroll Efekti
    initNavbar();

    // 3. Slider Başlat
    initSlider();

    console.log("Tüm parçalar yüklendi ve özellikler aktif edildi.");
}


// --- Logic Functions ---

// Navbar Scroll Logic
function initNavbar() {
    const navbar = document.getElementById('navbar');
    const navLogo = document.getElementById('nav-logo');
    const navLinks = document.querySelectorAll('.nav-link');
    const navIconBtns = document.querySelectorAll('.nav-icon-btn');
    const navMobileBtn = document.querySelector('.nav-mobile-btn');

    if (!navbar) return;

    window.addEventListener('scroll', () => {
        if (window.scrollY > 20) {
            navbar.classList.remove('bg-transparent', 'py-6');
            navbar.classList.add('bg-white/90', 'backdrop-blur-md', 'shadow-sm', 'py-4');

            if (navLogo) {
                navLogo.classList.remove('text-white');
                navLogo.classList.add('text-gray-900');
            }

            navLinks.forEach(link => {
                link.classList.remove('text-white');
                link.classList.add('text-gray-700');
            });

            navIconBtns.forEach(btn => {
                btn.classList.remove('text-white', 'hover:bg-white/20');
                btn.classList.add('text-gray-700', 'hover:bg-gray-100');
            });

            if (navMobileBtn) {
                navMobileBtn.classList.remove('text-white');
                navMobileBtn.classList.add('text-gray-700');
            }

        } else {
            navbar.classList.add('bg-transparent', 'py-6');
            navbar.classList.remove('bg-white/90', 'backdrop-blur-md', 'shadow-sm', 'py-4');

            if (navLogo) {
                navLogo.classList.add('text-white');
                navLogo.classList.remove('text-gray-900');
            }

            navLinks.forEach(link => {
                link.classList.add('text-white');
                link.classList.remove('text-gray-700');
            });

            navIconBtns.forEach(btn => {
                btn.classList.add('text-white', 'hover:bg-white/20');
                btn.classList.remove('text-gray-700', 'hover:bg-gray-100');
            });

            if (navMobileBtn) {
                navMobileBtn.classList.add('text-white');
                navMobileBtn.classList.remove('text-gray-700');
            }
        }
    });
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

// Cart Logic
window.toggleCart = function () {
    const sidebar = document.getElementById('cart-sidebar');
    const backdrop = document.getElementById('cart-backdrop');
    if (sidebar) sidebar.classList.toggle('open');
    if (backdrop) backdrop.classList.toggle('open');
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