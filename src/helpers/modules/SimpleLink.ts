/**
 * SPDX-FileCopyrightText: 2022 Nextcloud contributors
 * SPDX-License-Identifier: AGPL-3.0-or-later
 */

import { h } from 'vue'

const SimpleLink = {
	props: {
		href: {
			type: String,
			default: '',
		},
		name: {
			type: String,
			default: '',
		},
		target: {
			type: String,
			default: null,
		},
	},

	setup(props: { href: string; target: string; name: string }) {
		return () =>
			h(
				'a',
				{
					href: props.href,
					target: props.target,
				},
				props.name,
			)
	},
}

export { SimpleLink }
