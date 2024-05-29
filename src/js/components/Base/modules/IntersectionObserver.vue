<!--
  - SPDX-FileCopyrightText: 2024 Nextcloud contributors
  - SPDX-License-Identifier: AGPL-3.0-or-later
-->

<template>
	<div ref="observerTarget">
		<slot :in-viewport="inViewport" />
	</div>
</template>

<script>
export default {
	data() {
		return {
			inViewport: false,
			observer: null,
		}
	},
	mounted() {
		this.observer = new IntersectionObserver((entries) => {
			entries.forEach((entry) => {
				if (entry.isIntersecting) {
					this.inViewport = true
					this.$emit('visible')
				} else {
					this.inViewport = false
				}
			})
		})

		this.observer.observe(this.$refs.observerTarget)
	},

	beforeDestroy() {
		if (this.observer) {
			this.observer.disconnect()
		}
	},
}
</script>
