/**
 * SPDX-FileCopyrightText: 2026 Nextcloud contributors
 * SPDX-License-Identifier: AGPL-3.0-or-later
 */

import { h } from 'vue'

/**
 * Renders a translated string where [link]...[/link] is replaced by an anchor element.
 * Square brackets are used so t() does not strip the markers as HTML tags.
 *
 * Usage:
 *   <InlineLink :text="t('app', 'click [link]here[/link]')" href="/login" />
 *
 *   <InlineLink :text="t('app', 'click [link]here[/link]')" @click.prevent="fn()" />
 */
const InlineLink = {
	inheritAttrs: false,

	props: {
		text: {
			type: String,
			default: '',
		},
		href: {
			type: String,
			default: '#',
		},
		target: {
			type: String,
			default: null,
		},
	},

	setup(
		props: { text: string; href: string; target: string | null },
		{ attrs }: { attrs: Record<string, unknown> },
	) {
		return () => {
			const match = props.text.match(
				/^([\s\S]*)\[link\]([\s\S]*)\[\/link\]([\s\S]*)$/,
			)

			if (!match) {
				return h('span', props.text)
			}

			const [, before, linkText, after] = match

			return h('span', [
				before,
				h(
					'a',
					{ href: props.href, target: props.target, style: 'text-decoration: revert', ...attrs },
					linkText,
				),
				after,
			])
		}
	},
}

export { InlineLink }
