/**
 * SPDX-FileCopyrightText: 2024 Nextcloud contributors
 * SPDX-License-Identifier: AGPL-3.0-or-later
 */

import { nextTick, onMounted, onUnmounted, ref, watchEffect } from 'vue'

/**
 * returns the width of the element with the given id
 *
 * @param elementId the id of the element whose width should be checked
 * @param offset
 */
export function useElementWidth(elementId: string, offset: number = 0) {
	const width = ref(0)
	const element = ref<null | HTMLElement>(null)
	const isBelowOffset = ref(false)
	const resizeObserver = ref<ResizeObserver | null>(null)
	/**
	 * return the scrollTop value of the element, if scrollTop is greater than offset
	 * otherwise return 0
	 */
	function updateWidth() {
		if (element.value) {
			width.value = element.value.clientWidth
			isBelowOffset.value = width.value < offset
		} else {
			width.value = 0
		}
	}

	onMounted(() => {
		nextTick(() => {
			element.value = document.getElementById(elementId)
			updateWidth()

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

	watchEffect(() => {
		isBelowOffset.value = width.value < offset
	})

	return { width, isBelowOffset }
}
