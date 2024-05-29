/**
 * SPDX-FileCopyrightText: 2022 Nextcloud contributors
 * SPDX-License-Identifier: AGPL-3.0-or-later
 */
const GuestBubble = {
	name: 'GuestBubble',
	functional: true,

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

	render(createElement, context) {
		return createElement('span', context.props.displayName)
	},
}

export default GuestBubble
