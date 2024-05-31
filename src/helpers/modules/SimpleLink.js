/**
 * SPDX-FileCopyrightText: 2022 Nextcloud contributors
 * SPDX-License-Identifier: AGPL-3.0-or-later
 */
const SimpleLink = {
	name: 'SimpleLink',
	functional: true,

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

	render(createElement, context) {
		return createElement('a', {
			attrs: {
				href: context.props.href,
				target: context.props.target,
			},
		}, context.props.name)
	},
}

export default SimpleLink
