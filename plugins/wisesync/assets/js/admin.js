function sync_toggleMenu() {
	document.querySelector('.sync-sidebar').classList.toggle('open');
  }
  
  function sync_toggleSubMenu(event) {
	event.preventDefault();
	event.currentTarget.parentElement.classList.toggle('open');
  }
  