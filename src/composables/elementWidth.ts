/**
 * SPDX-FileCopyrightText: 2024 Nextcloud contributors
 * SPDX-License-Identifier: AGPL-3.0-or-later
 */

import { nextTick, onMounted, onUnmounted, ref } from 'vue'

/**
 * returns the width of the element with the given id
 *
 * @param elementId the id of the element whose width should be checked
 * @param elWidthOffset the width offset to check against
 */
export function useResizeObserver(elementId: string, elWidthOffset: number = 0) {
	const elWidth = ref(0)
	const element = ref<null | HTMLElement>(null)
	const isBelowWidthOffset = ref(false)
	const resizeObserver = ref<ResizeObserver | null>(null)

	function updateWidth() {
		if (element.value) {
			elWidth.value = element.value.clientWidth
			isBelowWidthOffset.value = elWidth.value < elWidthOffset

		} else {
			elWidth.value = 0
		}
	}

	onMounted(() => {
		nextTick(() => {
			element.value = document.getElementById(elementId)
			updateWidth() // set initial width

			resizeObserver.value = new ResizeObserver(() => {
				updateWidth()
			})

			if (element.value) {
				resizeObserver.value.observe(element.value)
			}
		})
	})

	onUnmounted(() => {
		if (resizeObserver.value) resizeObserver.value.disconnect()
	})

	return { isBelowWidthOffset }
}
