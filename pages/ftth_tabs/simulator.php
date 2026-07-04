<!-- Header -->
<div class="flex flex-col sm:flex-row justify-between items-center mb-6 gap-4">
    <div>
        <h2 class="text-xl font-bold text-gray-800 dark:text-white">FTTH Attenuation Simulator</h2>
        <p class="text-xs text-gray-600 dark:text-gray-400">Simulasikan topologi jaringan kabel fiber optik (FTTH) dan hitung redaman secara real-time.</p>
    </div>
</div>

<!-- Simulator Container -->
<div class="bg-white dark:bg-slate-800 border border-gray-200 dark:border-slate-700 rounded-2xl shadow-sm overflow-hidden flex flex-col h-[calc(100vh-270px)]">
    <iframe 
        id="simulator-iframe"
        src="<?= BASE_URL ?>/ftth_simulator/index.php" 
        class="w-full h-full border-0" 
        allowfullscreen>
    </iframe>
</div>

<script>
document.addEventListener('DOMContentLoaded', function() {
    const iframe = document.getElementById('simulator-iframe');
    if (!iframe) return;

    // Function to synchronize the theme to the iframe
    function syncThemeToIframe() {
        try {
            const iframeWin = iframe.contentWindow;
            const iframeDoc = iframe.contentDocument || iframeWin.document;
            if (!iframeDoc) return;

            const isParentDark = document.documentElement.classList.contains('dark');
            const targetTheme = isParentDark ? 'dark' : 'light';

            // 1. Set the theme attribute on the iframe's root element
            iframeDoc.documentElement.setAttribute('data-theme', targetTheme);
            
            // 2. Set the localStorage in the iframe context
            iframeWin.localStorage.setItem('ftth_simulator_theme', targetTheme);

            // 3. Trigger the internal setTheme method if the app has fully loaded
            if (iframeWin.app && typeof iframeWin.app.setTheme === 'function') {
                iframeWin.app.setTheme(targetTheme);
            } else {
                // Manual fallback toggling of SVG icons in the iframe header
                const sunIcon = iframeDoc.querySelector('.theme-icon-sun');
                const moonIcon = iframeDoc.querySelector('.theme-icon-moon');
                if (targetTheme === 'light') {
                    if (sunIcon) sunIcon.style.display = 'none';
                    if (moonIcon) moonIcon.style.display = 'block';
                } else {
                    if (sunIcon) sunIcon.style.display = 'block';
                    if (moonIcon) moonIcon.style.display = 'none';
                }
            }
        } catch (e) {
            console.warn("Unable to sync theme to iframe (possibly loading or cross-origin limits):", e);
        }
    }

    // Handle initial load of the iframe
    iframe.addEventListener('load', function() {
        syncThemeToIframe();

        // Hide the redundant theme toggler inside the iframe
        try {
            const iframeDoc = iframe.contentDocument || iframe.contentWindow.document;
            const iframeThemeToggle = iframeDoc.getElementById('btn-theme-toggle');
            if (iframeThemeToggle) {
                iframeThemeToggle.style.display = 'none';
            }
        } catch (e) {
            console.warn("Unable to hide iframe theme toggler:", e);
        }
    });

    // Observe theme class changes on parent <html> element
    const observer = new MutationObserver(function(mutations) {
        mutations.forEach(function(mutation) {
            if (mutation.attributeName === 'class') {
                syncThemeToIframe();
            }
        });
    });
    
    observer.observe(document.documentElement, { attributes: true });
});
</script>
