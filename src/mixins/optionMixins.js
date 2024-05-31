/* jshint esversion: 6 */
/**
 * SPDX-FileCopyrightText: 2020 Nextcloud contributors
 * SPDX-License-Identifier: AGPL-3.0-or-later
 */
export const confirmOption = {
	methods: {
		confirmOption(option) {
			this.$store.dispatch('options/confirm', { option })
		},
	},
}

export const deleteOption = {
	methods: {
		deleteOption(option) {
			this.$store.dispatch('options/delete', { option })
		},
	},
}

export const restoreOption = {
	methods: {
		restoreOption(option) {
			this.$store.dispatch('options/restore', { option })
		},
	},
}
