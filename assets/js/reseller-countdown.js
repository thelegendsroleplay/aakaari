/**
 * Reseller Application Countdown Timer
 * Live countdown for rejected applications showing time until reapplication
 */
document.addEventListener('DOMContentLoaded', function() {
    const countdownElement = document.querySelector('.cooldown-timer');
    
    if (!countdownElement) return;
    
    const cooldownExpires = countdownElement.getAttribute('data-expires');
    
    if (!cooldownExpires) return;
    
    // Parse the expiry timestamp (expects Unix timestamp in seconds)
    const expiryTime = parseInt(cooldownExpires) * 1000; // Convert to milliseconds
    
    function updateCountdown() {
        const now = new Date().getTime();
        const distance = expiryTime - now;
        
        if (distance < 0) {
            // Countdown expired - show reapply message and reload
            countdownElement.innerHTML = `
                <div class="countdown-expired">
                    <p class="expired-text">⏰ Cooldown period has ended!</p>
                    <p class="expired-subtext">You can now resubmit your application.</p>
                    <button onclick="window.location.reload()" class="btn btn-primary" style="margin-top: 1rem;">
                        Reload Page to Continue
                    </button>
                </div>
            `;
            return;
        }
        
        // Calculate time components
        const days = Math.floor(distance / (1000 * 60 * 60 * 24));
        const hours = Math.floor((distance % (1000 * 60 * 60 * 24)) / (1000 * 60 * 60));
        const minutes = Math.floor((distance % (1000 * 60 * 60)) / (1000 * 60));
        const seconds = Math.floor((distance % (1000 * 60)) / 1000);
        
        // Update the display
        const daysElement = countdownElement.querySelector('.timer-days .timer-number');
        const hoursElement = countdownElement.querySelector('.timer-hours .timer-number');
        const minutesElement = countdownElement.querySelector('.timer-minutes .timer-number');
        const secondsElement = countdownElement.querySelector('.timer-seconds .timer-number');
        
        if (daysElement) daysElement.textContent = days.toString().padStart(2, '0');
        if (hoursElement) hoursElement.textContent = hours.toString().padStart(2, '0');
        if (minutesElement) minutesElement.textContent = minutes.toString().padStart(2, '0');
        if (secondsElement) secondsElement.textContent = seconds.toString().padStart(2, '0');
    }
    
    // Update immediately
    updateCountdown();
    
    // Update every second
    setInterval(updateCountdown, 1000);
});

