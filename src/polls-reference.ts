/**
 * SPDX-FileCopyrightText: 2022 Nextcloud contributors
 * SPDX-License-Identifier: AGPL-3.0-or-later
 */

import { createApp } from 'vue'
import { registerWidget } from '@nextcloud/vue/components/NcRichText'
import ReferenceView from './views/ReferenceView.vue'
import { pinia } from './stores/index.ts'

import './assets/scss/polls-icon.scss'

registerWidget(
	'polls_reference_widget',
	async (el, { richObject }) => {
		const PollsReference = createApp(ReferenceView, {
			richObject,
		})
			.use(pinia)
			.mount(el)
		return PollsReference
	},
	(el) => el.classList.add('nc-polls-reference-widget'),
	{},
)
