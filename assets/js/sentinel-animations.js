/**
 * Sentinel Cameroon - Animation Controller
 * Implements Reveal-on-Scroll and Staggered Grid Animations
 */

document.addEventListener('DOMContentLoaded', () => {
    // 1. Reveal on Scroll Observer
    const revealOptions = {
        threshold: 0.15,
        rootMargin: "0px 0px -50px 0px"
    };

    const revealObserver = new IntersectionObserver((entries, observer) => {
        entries.forEach(entry => {
            if (entry.isIntersecting) {
                entry.target.classList.add('visible');
                // Optional: Stop observing once revealed
                // observer.unobserve(entry.target);
            }
        });
    }, revealOptions);

    // 2. Initialize Reveal Elements
    const revealElements = document.querySelectorAll('.reveal');
    revealElements.forEach(el => revealObserver.observe(el));

    // 3. Staggered Grid Initialization
    const grids = document.querySelectorAll('.rs-grid, .features-grid, .partners-grid');
    grids.forEach(grid => {
        const items = grid.children;
        Array.from(items).forEach((item, index) => {
            // Add gradual reveal class if not already there
            if (!item.classList.contains('reveal')) {
                item.classList.add('reveal');
                revealObserver.observe(item);
            }
            // Set staggered delay
            item.style.transitionDelay = `${(index % 4) * 0.1}s`;
        });
    });

    // 4. Button & Interaction Feedback
    const buttons = document.querySelectorAll('.btn-rs, .rs-card');
    buttons.forEach(btn => {
        btn.addEventListener('mousedown', () => {
            btn.style.transform = 'scale(0.98)';
        });
        btn.addEventListener('mouseup', () => {
            btn.style.transform = '';
        });
        btn.addEventListener('mouseleave', () => {
            btn.style.transform = '';
        });
    });
});
