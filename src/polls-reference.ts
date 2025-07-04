/**
 * SPDX-FileCopyrightText: 2022 Nextcloud contributors
 * SPDX-License-Identifier: AGPL-3.0-or-later
 */

import { createApp } from 'vue'
import { pinia } from './stores/index.ts'
import { registerWidget } from '@nextcloud/vue/components/NcRichText'
import Reference from './views/Reference.vue'
import './assets/scss/polls-icon.scss'

registerWidget(
	'polls_reference_widget',
	async (el, { richObject }) => {
		const PollsReference = createApp(Reference, {
			richObject,
		})
			.use(pinia)
			.mount(el)
		return PollsReference
	},
	(el) => el.classList.add('nc-polls-reference-widget'),
	{},
)
