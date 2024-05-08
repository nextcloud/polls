<!--
  - @copyright Copyright (c) 2024 René Gieling <github@dartcafe.de>
  -
  - @author René Gieling <github@dartcafe.de>
  -
  - @license GNU AGPL version 3 or any later version
  -
  - This program is free software: you can redistribute it and/or modify
  - it under the terms of the GNU Affero General Public License as
  - published by the Free Software Foundation, either version 3 of the
  - License, or (at your option) any later version.
  -
  - This program is distributed in the hope that it will be useful,
  - but WITHOUT ANY WARRANTY; without even the implied warranty of
  - MERCHANTABILITY or FITNESS FOR A PARTICULAR PURPOSE.  See the
  - GNU Affero General Public License for more details.
  -
  - You should have received a copy of the GNU Affero General Public License
  - along with this program.  If not, see <http://www.gnu.org/licenses/>.
  -
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
