(function() {
	/**
	 * Initialize dashboard interactivity
	 */
	function syncInitDashboard() {
	  const menuLinks = document.querySelectorAll('.sync-menu-link, .sync-submenu-link');
	  menuLinks.forEach(link => {
		link.addEventListener('click', syncHandleMenuClick);
	  });
	}
  
	/**
	 * Handle any menu or submenu click
	 */
	function syncHandleMenuClick(event) {
	  event.preventDefault();
	  const targetKey = event.currentTarget.getAttribute('data-sync-target');
	  if (!targetKey) return;
  
	  // Toggle submenus if present
	  const parentItem = event.currentTarget.parentElement;
	  if (parentItem.classList.contains('has-submenu')) {
		syncToggleSubMenu(parentItem);
	  }
  
	  // Highlight active menu item
	  document.querySelectorAll('.sync-menu-item, .sync-submenu-item').forEach(item => {
		item.classList.remove('active');
	  });
	  parentItem.classList.add('active');
  
	  // Show corresponding content section
	  document.querySelectorAll('.sync-content').forEach(sec => {
		sec.hidden = sec.getAttribute('data-sync-content') !== targetKey;
	  });
	}
  
	/**
	 * Expand/collapse a submenu
	 */
	function syncToggleSubMenu(menuItem) {
	  const btn = menuItem.querySelector('.sync-menu-link');
	  const submenu = menuItem.querySelector('.sync-submenu');
	  const isOpen = submenu && !submenu.hidden;
  
	  if (submenu) {
		submenu.hidden = isOpen;
		btn.setAttribute('aria-expanded', String(!isOpen));
	  }
	}
  
	// Kick things off when the DOM is ready
	if (document.readyState === 'loading') {
	  document.addEventListener('DOMContentLoaded', syncInitDashboard);
	} else {
	  syncInitDashboard();
	}
  })();
  