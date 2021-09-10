<!--
  - @copyright Copyright (c) 2021 René Gieling <github@dartcafe.de>
  -
  - @author René Gieling <github@dartcafe.de>
  -
  - @license GNU AGPL version 3 or any later version
  -
  - This program is free software: you can redistribute it and/or modify
  - it under the terms of the GNU Affero General Public License as
  - published by the Free Software Foundation, either version 3 of the
  - License, or (at your option) any later version.
  -
  - This program is distributed in the hope that it will be useful,
  - but WITHOUT ANY WARRANTY; without even the implied warranty of
  - MERCHANTABILITY or FITNESS FOR A PARTICULAR PURPOSE.  See the
  - GNU Affero General Public License for more details.
  -
  - You should have received a copy of the GNU Affero General Public License
  - along with this program.  If not, see <http://www.gnu.org/licenses/>.
  -
  -->

<template>
	<div>
		<RadioGroupDiv v-model="publicPollEmail" :options="emailOptions" />
	</div>
</template>

<script>
import debounce from 'lodash/debounce'
import { mapState } from 'vuex'
import { showSuccess, showError } from '@nextcloud/dialogs'
import RadioGroupDiv from '../Base/RadioGroupDiv'

export default {
	name: 'ConfigPublicPollsEmail',

	components: {
		RadioGroupDiv,
	},

	data() {
		return {
			emailOptions: [
				{ value: 'optional', label: t('polls', 'Email address is optional') },
				{ value: 'mandatory', label: t('polls', 'Email adress is mandatory') },
				{ value: 'disabled', label: t('polls', 'Hide email address') },
			],
		}
	},

	computed: {
		...mapState({
			poll: (state) => state.poll,
		}),

		publicPollEmail: {
			get() {
				return this.poll.publicPollEmail
			},
			set(value) {
				this.$store.commit('poll/setProperty', { publicPollEmail: value })
				this.writePoll()
			},
		},
	},

	methods: {
		successDebounced: debounce(function(title) {
			showSuccess(t('polls', '"{pollTitle}" successfully saved', { pollTitle: this.poll.title }))
		}, 1500),

		async writePoll() {
			if (this.poll.title) {
				try {
					await this.$store.dispatch('poll/update')
					this.successDebounced(this.poll.title)
				} catch {
					showError(t('polls', 'Error writing poll'))
				}
			} else {
				showError(t('polls', 'Title must not be empty!'))
			}
		},
	},
}
</script>
