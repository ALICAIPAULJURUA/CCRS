// ============================================
// HOMEPAGE SLIDESHOW
// ============================================

let slideIndex = 1;
let slideInterval;

// Start slideshow when page loads
document.addEventListener('DOMContentLoaded', function() {
    if (document.querySelector('.slideshow-container')) {
        showSlides(slideIndex);
        startAutoSlide();
        
        // Pause on hover
        const container = document.querySelector('.slideshow-container');
        if (container) {
            container.addEventListener('mouseenter', pauseAutoSlide);
            container.addEventListener('mouseleave', startAutoSlide);
        }
    }
});

function startAutoSlide() {
    if (slideInterval) clearInterval(slideInterval);
    slideInterval = setInterval(function() {
        plusSlides(1);
    }, 5000); // Change slide every 5 seconds
}

function pauseAutoSlide() {
    if (slideInterval) {
        clearInterval(slideInterval);
        slideInterval = null;
    }
}

function plusSlides(n) {
    showSlides(slideIndex += n);
    resetAutoSlide();
}

function currentSlide(n) {
    showSlides(slideIndex = n);
    resetAutoSlide();
}

function showSlides(n) {
    let slides = document.getElementsByClassName("slide");
    let dots = document.getElementsByClassName("dot");
    
    if (!slides.length) return;
    
    if (n > slides.length) { slideIndex = 1; }
    if (n < 1) { slideIndex = slides.length; }
    
    // Hide all slides
    for (let i = 0; i < slides.length; i++) {
        slides[i].style.display = "none";
    }
    
    // Remove active class from all dots
    for (let i = 0; i < dots.length; i++) {
        dots[i].className = dots[i].className.replace(" active", "");
    }
    
    // Show current slide
    slides[slideIndex - 1].style.display = "block";
    
    // Add active class to current dot
    if (dots[slideIndex - 1]) {
        dots[slideIndex - 1].className += " active";
    }
}

function resetAutoSlide() {
    pauseAutoSlide();
    startAutoSlide();
}