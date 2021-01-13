function detectColorScheme() {
	if (!window.matchMedia) {
		return true
	} else if (window.matchMedia('(prefers-color-scheme: dark)').matches) {
		document.body.classList.add('dark-theme')
		return true
	}
}

export default { detectColorScheme }
