/**
 * SPDX-FileCopyrightText: 2024 Nextcloud contributors
 * SPDX-License-Identifier: AGPL-3.0-or-later
 */

import { onMounted, onUnmounted, ref } from 'vue'

/**
 * returns a boolean value if the user has scrolled downwards more than 20px
 *
 * @param scrollElementId the id of the element that should be checked for scrolling
 * @param offset the offset in px that should be scrolled before returning the scroll value
 */
export function useHandleScroll(scrollElementId: string, offset: number = 20) {
	const scrolled = ref(0)
	const scrollElement = ref<null | HTMLElement>(null)

	/**
	 *
	 */
	function handleScroll() {
		if (scrollElement.value !== null && scrollElement.value.scrollTop > offset) {
			scrolled.value = scrollElement.value.scrollTop
		} else {
			scrolled.value = 0
		}
	}

	onMounted(() => {
		scrollElement.value = document.getElementById(scrollElementId)
		// Fallback for class based elements
		if (scrollElement.value === null) {
			const scrollElements = document.getElementsByClassName(scrollElementId)
			// asssume the first one is the desired one
			scrollElement.value = scrollElements[0] as HTMLElement
		}

		if (scrollElement.value !== null) {
			scrollElement.value.addEventListener('scroll', handleScroll)
		}
	})

	onUnmounted(() => {
		if (scrollElement.value !== null)
			scrollElement.value.removeEventListener('scroll', handleScroll)
	})
	return scrolled
}
