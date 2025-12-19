// slider.js - Slider işlemleri için modül

let currentSlide = 0;

// Slider'ı başlat
function initSlider() {
    const slides = document.querySelectorAll('.slide');
    if (slides.length === 0) return;

    const indicatorsContainer = document.getElementById('slider-indicators');

    // Indicator'ları oluştur
    if (indicatorsContainer && indicatorsContainer.children.length === 0) {
        slides.forEach((_, idx) => {
            const dot = document.createElement('button');
            dot.className = `w-2 h-2 rounded-full transition-all duration-300 ${idx === 0 ? 'bg-white w-8' : 'bg-white/50'}`;
            dot.onclick = () => goToSlide(idx);
            indicatorsContainer.appendChild(dot);
        });
    }

    // Otomatik geçiş
    setInterval(() => {
        currentSlide = (currentSlide + 1) % slides.length;
        updateSlides();
    }, 5000);
}

// Slayt'a git
function goToSlide(index) {
    currentSlide = index;
    updateSlides();
}

// Slaytları güncelle
function updateSlides() {
    const slides = document.querySelectorAll('.slide');
    const indicators = document.querySelectorAll('#slider-indicators button');

    slides.forEach((slide, idx) => {
        slide.classList.toggle('active', idx === currentSlide);
    });

    indicators.forEach((dot, idx) => {
        dot.className = `w-2 h-2 rounded-full transition-all duration-300 ${idx === currentSlide ? 'bg-white w-8' : 'bg-white/50'}`;
    });
}

// Export for module usage
if (typeof module !== 'undefined' && module.exports) {
    module.exports = { initSlider, goToSlide };
}