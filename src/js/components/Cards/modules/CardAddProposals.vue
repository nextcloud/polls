<!--
  - @copyright Copyright (c) 2018 René Gieling <github@dartcafe.de>
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
	<CardDiv :type="cardType">
		{{ t('polls', 'You are asked to propose more options. ') }}
		<p v-if="proposalsExpirySet && !proposalsExpired">
			{{ t('polls', 'The proposal period ends {timeRelative}.',
				{ timeRelative: proposalsExpireRelative }) }}
		</p>
		<OptionProposals v-if="pollType === 'textPoll'" />
		<template #button>
			<OptionProposals v-if="pollType === 'datePoll'" />
		</template>
	</CardDiv>
</template>

<script>
import { mapState, mapGetters } from 'vuex'
import { CardDiv } from '../../Base/index.js'
import OptionProposals from '../../Options/OptionProposals.vue'

export default {
	name: 'CardAddProposals',
	components: {
		CardDiv,
		OptionProposals,
	},

	data() {
		return {
			cardType: 'info',
		}
	},

	computed: {
		...mapState({
			pollType: (state) => state.poll.type,
		}),

		...mapGetters({
			proposalsExpirySet: 'poll/proposalsExpirySet',
			proposalsExpired: 'poll/proposalsExpired',
			proposalsExpireRelative: 'poll/proposalsExpireRelative',
		}),
	},
}
</script>
