/* jshint esversion: 6 */
/**
 * SPDX-FileCopyrightText: 2021 Nextcloud contributors
 * SPDX-License-Identifier: AGPL-3.0-or-later
 */
import { debounce } from 'lodash'
import { showSuccess, showError } from '@nextcloud/dialogs'
import { mapState } from 'vuex'

export const writePoll = {
	computed: {
		...mapState({
			pollTitle: (state) => state.poll.configuration.title,
		}),

	},

	methods: {
		successDebounced: debounce(function() {
			showSuccess(t('polls', '"{pollTitle}" successfully saved', { pollTitle: this.pollTitle }), { timeout: 1000 })
		}, 1500),

		async writePoll() {
			if (this.pollTitle === '') {
				showError(t('polls', 'Title must not be empty!'))
			} else {
				try {
					await this.$store.dispatch('poll/update')
					this.successDebounced()
				} catch {
					showError(t('polls', 'Error writing poll'))
				}
			}
		},

	},
}
