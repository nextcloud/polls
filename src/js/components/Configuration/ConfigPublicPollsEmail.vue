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
	<ConfigBox :title="t('polls', 'Options for public register dialog')" icon-class="icon-public">
		<RadioGroupDiv v-model="publicPollEmail" :options="emailOptions" />
	</ConfigBox>
</template>

<script>
import { mapState } from 'vuex'
import ConfigBox from '../Base/ConfigBox'
import RadioGroupDiv from '../Base/RadioGroupDiv'
import { writePoll } from '../../mixins/writePoll'

export default {
	name: 'ConfigPublicPollsEmail',

	components: {
		ConfigBox,
		RadioGroupDiv,
	},

	mixins: [writePoll],

	data() {
		return {
			emailOptions: [
				{ value: 'optional', label: t('polls', 'Email address is optional') },
				{ value: 'mandatory', label: t('polls', 'Email address is mandatory') },
				{ value: 'disabled', label: t('polls', 'Do not ask for email address') },
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
				this.writePoll() // from mixin
			},
		},
	},

}
</script>
