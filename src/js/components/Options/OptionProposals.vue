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
	<div class="option-proposals">
		<!-- <div>{{ proposalsStatus }}</div> -->
		<div v-if="proposalsOpen" class="option-proposals__add-proposal">
			<OptionsDateAdd v-if="pollType === 'datePoll'"
				:caption="t('polls', 'Propose a date')"
				class="add-date-proposal"
				primary />
			<OptionsTextAdd v-if="pollType === 'textPoll'" :placeholder="t('polls', 'Propose an option')" class="add-text-proposal" />
		</div>
	</div>
</template>

<script>
import { mapState, mapGetters } from 'vuex'

export default {
	name: 'OptionProposals',

	components: {
		OptionsDateAdd: () => import('./OptionsDateAdd'),
		OptionsTextAdd: () => import('./OptionsTextAdd'),
	},

	computed: {
		...mapState({
			pollType: (state) => state.poll.type,
		}),

		...mapGetters({
			proposalsExpirySet: 'poll/proposalsExpirySet',
			proposalsExpired: 'poll/proposalsExpired',
			proposalsOpen: 'poll/proposalsOpen',
			proposalsExpireRelative: 'poll/proposalsExpireRelative',
		}),

		proposalsStatus() {
			if (this.proposalsExpirySet && !this.proposalsExpired) {
				return t('polls', 'Proposal period ends {timeRelative}.', { timeRelative: this.proposalsExpireRelative })
			}
			if (this.proposalsExpirySet && this.proposalsExpired) {
				return t('polls', 'Proposal period ended {timeRelative}.', { timeRelative: this.proposalsExpireRelative })
			}
			return t('polls', 'You are asked to propose more poll options.')
		},
	},
}

</script>

<style lang="scss">
	.option-proposals {
		align-self: center;
		display: flex;
		flex-direction: column;
	}

	.option-proposals__add-proposal {
		padding: 8px 0;
		.add-date-proposal {
			min-width: 85px;
		}
	}
</style>
