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
		<div class="option-proposals__header">
			<div v-if="proposalsOpen">
				{{ t('polls', 'You are asked to propose more options for this poll.') }}
			</div>
			<div v-if="proposalsExpirySet && !proposalsExpired">
				{{ t('polls', 'Adding proposals ends {timeRelative}.', {timeRelative: proposalsExpireRelative}) }}
			</div>
			<div v-if="proposalsExpired">
				{{ t('polls', 'Adding proposals ended {timeRelative}.', {timeRelative: proposalsExpireRelative}) }}
			</div>
		</div>
		<div v-if="allowAddOptions" class="option-proposals__add-proposal">
			<OptionsDateAdd v-if="pollType === 'datePoll'" />
			<OptionsTextAdd v-if="pollType === 'textPoll'" />
		</div>
	</div>
</template>

<script>
import { mapState, mapGetters } from 'vuex'
import OptionsDateAdd from './OptionsDateAdd'
import OptionsTextAdd from './OptionsTextAdd'

export default {
	name: 'OptionProposals',

	components: {
		OptionsDateAdd,
		OptionsTextAdd,
	},

	computed: {
		...mapState({
			pollType: state => state.poll.type,
			allowAddOptions: state => state.poll.acl.allowAddOptions,
		}),

		...mapGetters({
			proposalsOpen: 'poll/proposalsOpen',
			proposalsExpirySet: 'poll/proposalsExpirySet',
			proposalsExpired: 'poll/proposalsExpired',
			proposalsExpireRelative: 'poll/proposalsExpireRelative',
		}),
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
	}
</style>
