/**
 * SPDX-FileCopyrightText: 2022 Nextcloud contributors
 * SPDX-License-Identifier: AGPL-3.0-or-later
 */
import { h } from 'vue'

const GuestBubble = {
	props: {
		user: {
			type: String,
			default: '',
		},
		displayName: {
			type: String,
			default: '',
		},
	},

	setup(props: { user: string; displayName: string }) {
		return () => h('span', props.displayName)
	},
}

export { GuestBubble }
