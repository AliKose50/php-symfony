// navbar.js - Navbar işlemleri için modül

// Navbar fonksiyonlarını başlat
function initNavbar() {
    console.log("Navbar initialized");
}

// Kullanıcı menüsünü aç/kapat
function toggleUserMenu() {
    const menu = document.getElementById('user-dropdown-menu');
    if (menu) {
        menu.classList.toggle('hidden');
    }
}

// Mobil menüyü aç/kapat
function toggleMobileMenu() {
    const menu = document.getElementById('mobile-menu');
    if (menu) {
        menu.classList.toggle('hidden');
    }
}

// Login modalını aç/kapat
function toggleLoginModal() {
    const modal = document.getElementById('login-modal');
    if (modal) {
        modal.classList.toggle('hidden');
    } else {
        // Modal yoksa login sayfasına yönlendir
        window.location.href = '/login';
    }
}

// Export for module usage
if (typeof module !== 'undefined' && module.exports) {
    module.exports = { initNavbar, toggleUserMenu, toggleMobileMenu, toggleLoginModal };
}