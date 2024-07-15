/**
 * SPDX-FileCopyrightText: 2024 Nextcloud contributors
 * SPDX-License-Identifier: AGPL-3.0-or-later
 */

import { computed, onMounted, onUnmounted, ref } from 'vue'

/**
 * returns a boolean value if the user has scrolled downwards more than 20px
 */
export function useHandleScroll() {
	const scrolled = ref(false)
	const scrollElement = ref(null)

	const handleScroll = computed(() => {
		if (scrollElement.value.scrollTop > 20) {
			scrolled.value = true
		} else {
			scrolled.value = false
		}
	})

	onMounted(() => {
		scrollElement.value = document.getElementById('app-content-vue')
		scrollElement.value.addEventListener('scroll', handleScroll)
	})

	onUnmounted(() => {
		scrollElement.value.removeEventListener('scroll', handleScroll)
	})
	return { scrolled }
}
