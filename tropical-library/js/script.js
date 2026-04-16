document.addEventListener('DOMContentLoaded', function() {
    const alerts = document.querySelectorAll('.alert');
    alerts.forEach(alert => {
        setTimeout(() => {
            alert.style.opacity = '0';
            setTimeout(() => alert.remove(), 300);
        }, 3000);
    });
    
    const cards = document.querySelectorAll('.book-card');
    cards.forEach(card => {
        card.addEventListener('mouseenter', () => { card.style.transform = 'translateY(-8px)'; });
        card.addEventListener('mouseleave', () => { card.style.transform = 'translateY(0)'; });
    });
});