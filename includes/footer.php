    <!-- ApexCharts (Required for Dashboard) -->
    <script src="https://cdn.jsdelivr.net/npm/apexcharts"></script>

    <!-- Preline UI (Optional fallback) -->
    <script src="https://cdn.jsdelivr.net/npm/preline/dist/preline.js"></script>

    <!-- Global Feedback Modal -->
    <div id="feedbackModal" class="hidden fixed inset-0 z-[70] overflow-y-auto w-full h-full bg-black/50 backdrop-blur-sm flex p-4 transition-all">
        <div class="m-auto flex flex-col bg-white dark:bg-slate-800 border border-gray-200 dark:border-slate-700 shadow-xl rounded-xl pointer-events-auto max-w-sm w-full">
            <div class="p-6 text-center">
                <!-- Icon Container -->
                <div id="feedbackIcon" class="mx-auto flex items-center justify-center size-12 rounded-full mb-4">
                    <!-- Icon SVG will be injected here -->
                </div>
                <h3 id="feedbackTitle" class="mb-2 text-lg font-bold text-gray-800 dark:text-gray-200"></h3>
                <p id="feedbackMessage" class="text-sm text-gray-500 dark:text-gray-400 mb-6"></p>
                <div class="flex justify-center">
                    <button type="button" onclick="closeModal('feedbackModal')" class="py-2.5 px-4 inline-flex items-center gap-x-2 text-sm font-semibold rounded-lg border border-transparent bg-blue-600 text-white hover:bg-blue-700 shadow-sm">
                        OK, Mengerti
                    </button>
                </div>
            </div>
        </div>
    </div>

    <script>
      // Global Modal Helpers
      window.openModal = function(modalId) {
          const modal = document.getElementById(modalId);
          if (modal) {
              modal.classList.remove('hidden');
              modal.classList.add('flex');
          }
      }

      window.closeModal = function(modalId) {
          const modal = document.getElementById(modalId);
          if (modal) {
              modal.classList.add('hidden');
              modal.classList.remove('flex');
          }
      }
      
      window.showFeedbackModal = function(type, message) {
          const modal = document.getElementById('feedbackModal');
          const title = document.getElementById('feedbackTitle');
          const msg = document.getElementById('feedbackMessage');
          const iconContainer = document.getElementById('feedbackIcon');
          
          // Reset classes
          iconContainer.className = 'mx-auto flex items-center justify-center size-12 rounded-full mb-4';
          
          if (type === 'success') {
              iconContainer.classList.add('bg-green-100', 'dark:bg-green-900/30');
              iconContainer.innerHTML = '<svg class="size-6 text-green-600 dark:text-green-400" xmlns="http://www.w3.org/2000/svg" width="24" height="24" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><path d="M22 11.08V12a10 10 0 1 1-5.93-9.14"/><polyline points="22 4 12 14.01 9 11.01"/></svg>';
              title.innerText = 'Berhasil!';
          } else if (type === 'error') {
              iconContainer.classList.add('bg-red-100', 'dark:bg-red-900/30');
              iconContainer.innerHTML = '<svg class="size-6 text-red-600 dark:text-red-400" xmlns="http://www.w3.org/2000/svg" width="24" height="24" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><circle cx="12" cy="12" r="10"/><line x1="12" x2="12" y1="8" y2="12"/><line x1="12" x2="12.01" y2="16"/></svg>';
              title.innerText = 'Gagal!';
          } else if (type === 'warning') {
              iconContainer.classList.add('bg-yellow-100', 'dark:bg-yellow-900/30');
              iconContainer.innerHTML = '<svg class="size-6 text-yellow-600 dark:text-yellow-400" xmlns="http://www.w3.org/2000/svg" width="24" height="24" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><path d="M10.29 3.86L1.82 18a2 2 0 0 0 1.71 3h16.94a2 2 0 0 0 1.71-3L13.71 3.86a2 2 0 0 0-3.42 0z"/><line x1="12" y1="9" x2="12" y2="13"/><line x1="12" y1="17" x2="12.01" y2="17"/></svg>';
              title.innerText = 'Peringatan';
          }
          
          msg.innerHTML = message; // Allow HTML content (e.g. <br>)
          openModal('feedbackModal');
      }

      // Check for Flash Message from PHP
      <?php if ($msg = get_flash_message()): ?>
      document.addEventListener('DOMContentLoaded', function() {
          showFeedbackModal('<?= $msg['type'] ?>', <?= json_encode($msg['message']) ?>);
      });
      <?php endif; ?>


          // --- Sidebar Toggle Logic (Desktop vs Mobile) ---
          const sidebarToggle = document.getElementById('sidebar-toggle');
          if (sidebarToggle) {
              sidebarToggle.addEventListener('click', function(e) {
                  e.stopImmediatePropagation();
                  e.preventDefault();
                  
                  const sidebar = document.getElementById('application-sidebar');
                  
                  // Mobile Logic (< 1024px)
                  if (window.innerWidth < 1024) {
                      const isClosed = sidebar.classList.contains('hidden') || sidebar.classList.contains('-translate-x-full');
                      
                      if (isClosed) {
                          // Open
                          sidebar.classList.remove('hidden');
                          
                          // Small delay to allow display:block to apply before transition
                          setTimeout(() => {
                              sidebar.classList.remove('-translate-x-full');
                              sidebar.classList.add('translate-x-0');
                          }, 10);

                          // Create Backdrop
                          let backdrop = document.getElementById('sidebar-backdrop');
                          if (!backdrop) {
                              backdrop = document.createElement('div');
                              backdrop.id = 'sidebar-backdrop';
                              backdrop.className = 'fixed inset-0 z-[59] bg-gray-900/50 transition-opacity opacity-0'; // z-59 to be below sidebar (z-60)
                              document.body.appendChild(backdrop);
                              
                              // Close on click
                              backdrop.addEventListener('click', () => {
                                  closeMobileSidebar(sidebar, backdrop);
                              });
                              
                              // Fade In
                              requestAnimationFrame(() => {
                                  backdrop.classList.remove('opacity-0');
                              });
                          }
                      } else {
                          // Close (Logic extracted for reuse)
                          const backdrop = document.getElementById('sidebar-backdrop');
                          closeMobileSidebar(sidebar, backdrop);
                      }
                  } 
                  // Desktop Logic (>= 1024px)
                  else {
                      const isClosed = document.documentElement.classList.toggle('sidebar-closed');
                      localStorage.setItem('sidebar-closed', isClosed);
                      
                      // Transition is handled by CSS on html.sidebar-closed
                  }
              });
          }

          function closeMobileSidebar(sidebar, backdrop) {
              sidebar.classList.remove('translate-x-0');
              sidebar.classList.add('-translate-x-full');
              
              if (backdrop) {
                  backdrop.classList.add('opacity-0');
                  setTimeout(() => {
                      if(backdrop.parentNode) backdrop.parentNode.removeChild(backdrop);
                  }, 300); // Match transition duration
              }
              
              // Hide after transition
              setTimeout(() => {
                  sidebar.classList.add('hidden');
              }, 300);
          }

          // --- Manual Modal & Dropdown Handler ---
      document.addEventListener('click', function(e) {
          
          // --- Modal Logic ---
          const toggleBtn = e.target.closest('[data-hs-overlay], [data-manual-toggle]');
          if (toggleBtn) {
              const targetSelector = toggleBtn.getAttribute('data-hs-overlay') || toggleBtn.getAttribute('data-manual-toggle');
              const targetModal = document.querySelector(targetSelector);
              
              // Exclude Sidebar from this logic (Let Preline or specific handler manage it)
              if (targetSelector === '#application-sidebar') return;

              if (targetModal) {
                  // Find the inner content container (animation wrapper)
                  const innerContent = targetModal.firstElementChild;

                  if (targetModal.classList.contains('hidden')) {
                      // Open
                      targetModal.classList.remove('hidden');
                      targetModal.classList.remove('pointer-events-none');
                      targetModal.classList.add('flex');
                      targetModal.classList.add('bg-gray-900/50'); // Backdrop
                      
                      // Animate Content IN
                      if(innerContent) {
                          innerContent.classList.remove('opacity-0', 'mt-0');
                          innerContent.classList.add('opacity-100', 'mt-7');
                      }
                  } else {
                      // Close
                      targetModal.classList.add('hidden');
                      targetModal.classList.remove('flex');
                      targetModal.classList.add('pointer-events-none');
                      targetModal.classList.remove('bg-gray-900/50');

                      // Reset Content State
                      if(innerContent) {
                          innerContent.classList.add('opacity-0', 'mt-0');
                          innerContent.classList.remove('opacity-100', 'mt-7');
                      }
                  }
              }
          }
          
          // Close Modals when clicking outside (overlay background)
          if (e.target.classList.contains('hs-overlay') && !e.target.classList.contains('hidden')) {
               e.target.classList.add('hidden');
               e.target.classList.remove('flex');
               e.target.classList.add('pointer-events-none');
               e.target.classList.remove('bg-gray-900/50');
               
               // Reset Inner Content
               const innerContent = e.target.firstElementChild;
               if(innerContent) {
                   innerContent.classList.add('opacity-0', 'mt-0');
                   innerContent.classList.remove('opacity-100', 'mt-7');
               }
          }

          // --- Dropdown Logic ---
          const dropdownToggle = e.target.closest('.manual-dropdown-toggle');
          if (dropdownToggle) {
              // Find the next sibling which should be the menu
              const dropdownMenu = dropdownToggle.parentElement.querySelector('.manual-dropdown-menu');
              if (dropdownMenu) {
                  const isHidden = dropdownMenu.classList.contains('hidden');
                  
                  // Close all other open dropdowns first (optional but good UI)
                  document.querySelectorAll('.manual-dropdown-menu').forEach(menu => {
                     if(menu !== dropdownMenu) {
                         menu.classList.add('hidden');
                         menu.classList.add('opacity-0');
                     }
                  });

                  if (isHidden) {
                      dropdownMenu.classList.remove('hidden');
                      dropdownMenu.classList.remove('opacity-0');
                  } else {
                      dropdownMenu.classList.add('hidden');
                      dropdownMenu.classList.add('opacity-0');
                  }
                  e.stopPropagation();
                  return;
              }
          }

          // Close all dropdowns when clicking outside
          if (!e.target.closest('.manual-dropdown-menu') && !e.target.closest('.manual-dropdown-toggle')) {
              document.querySelectorAll('.manual-dropdown-menu').forEach(menu => {
                  if (!menu.classList.contains('hidden')) {
                      menu.classList.add('hidden');
                      menu.classList.add('opacity-0');
                  }
              });
          }
      });
      // --- Theme Switcher Logic ---
      function setTheme(theme) {
          const html = document.documentElement;
          const lightIcon = document.getElementById('theme-icon-light');
          const darkIcon = document.getElementById('theme-icon-dark');
          const systemIcon = document.getElementById('theme-icon-system');

          // Reset Icons
          lightIcon?.classList.add('hidden');
          darkIcon?.classList.add('hidden');
          systemIcon?.classList.add('hidden');

          let activeTheme = theme;
          if (theme === 'system') {
              localStorage.removeItem('theme');
              if (window.matchMedia('(prefers-color-scheme: dark)').matches) {
                  html.classList.add('dark');
              } else {
                  html.classList.remove('dark');
              }
              systemIcon?.classList.remove('hidden');
          } else {
              localStorage.setItem('theme', theme);
              if (theme === 'dark') {
                  html.classList.add('dark');
                  darkIcon?.classList.remove('hidden');
              } else {
                  html.classList.remove('dark');
                  lightIcon?.classList.remove('hidden');
              }
          }

          // Highlight Active Button
          const buttons = {
              'light': document.getElementById('btn-theme-light'),
              'dark': document.getElementById('btn-theme-dark'),
              'system': document.getElementById('btn-theme-system')
          };

          // Base classes for inactive state (hover only)
          const baseClasses = ['hover:bg-[#F1F5F9]', 'dark:hover:bg-slate-700'];
          // Active classes (background + bold)
          const activeClasses = ['bg-gray-100', 'dark:bg-slate-700', 'font-bold'];

          for (const [key, btn] of Object.entries(buttons)) {
              if (btn) {
                  // Reset to inactive first
                  btn.classList.remove(...activeClasses);
                  btn.classList.add(...baseClasses);
                  
                  // Apply active if matched
                  // Note: activeTheme is the passed arg ('light', 'dark', 'system')
                  if (key === activeTheme) {
                      btn.classList.remove(...baseClasses);
                      btn.classList.add(...activeClasses);
                  }
              }
          }

          // Auto-close Dropdown
          const dropdownMenu = document.querySelector('[aria-labelledby="hs-dropdown-theme"]');
          if (dropdownMenu) {
              dropdownMenu.classList.add('hidden');
              dropdownMenu.classList.add('opacity-0');
          }
      }

      // Initialize Icons on Load (Sync with Header Script)
      (function() {
          const savedTheme = localStorage.getItem('theme');
          const lightIcon = document.getElementById('theme-icon-light');
          const darkIcon = document.getElementById('theme-icon-dark');
          const systemIcon = document.getElementById('theme-icon-system');
          
          let activeTheme = savedTheme || 'system';

          if (!savedTheme) {
              systemIcon?.classList.remove('hidden');
          } else if (savedTheme === 'dark') {
              darkIcon?.classList.remove('hidden');
          } else {
              lightIcon?.classList.remove('hidden');
          }
          
           // Highlight Active Button on Load
          const buttons = {
              'light': document.getElementById('btn-theme-light'),
              'dark': document.getElementById('btn-theme-dark'),
              'system': document.getElementById('btn-theme-system')
          };

          // Base classes for inactive state (hover only)
          const baseClasses = ['hover:bg-[#F1F5F9]', 'dark:hover:bg-slate-700'];
          // Active classes (background + bold)
          const activeClasses = ['bg-gray-100', 'dark:bg-slate-700', 'font-bold'];
          
          for (const [key, btn] of Object.entries(buttons)) {
              if (btn) {
                   // Reset to inactive first
                  btn.classList.remove(...activeClasses);
                  btn.classList.add(...baseClasses);

                  if (key === activeTheme) {
                      btn.classList.remove(...baseClasses);
                      btn.classList.add(...activeClasses);
                  }
              }
          }
      })();
      
      // Listen for System Changes
      window.matchMedia('(prefers-color-scheme: dark)').addEventListener('change', e => {
          if (!localStorage.getItem('theme')) {
              if (e.matches) {
                  document.documentElement.classList.add('dark');
              } else {
                  document.documentElement.classList.remove('dark');
              }
          }
      });
    </script>
</body>
</html>
