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
	<div class="participants-list">
		<h2 v-if="participantsVoted.length">
			{{ t('polls','Participants') }}
		</h2>
		<h2 v-else>
			{{ t('polls','No Participants until now') }}
		</h2>
		<div v-if="participantsVoted.length" class="participants">
			<userDiv v-for="(participant) in participantsVoted"
				:key="participant.userId"
				:hide-names="true"
				:user-id="participant.userId"
				:display-name="participant.displayName"
				type="user" />
		</div>
	</div>
</template>

<script>
import { mapState, mapGetters } from 'vuex'

export default {
	name: 'ParticipantsList',

	data() {
		return {
			voteSaved: false,
			delay: 50,
			newName: ''
		}
	},

	computed: {
		...mapState({
			acl: state => state.acl
		}),

		...mapGetters([
			'participantsVoted'
		])

	}

}
</script>

<style lang="scss" scoped>
	.participants-list {
		margin: 8px 0;
		padding-right: 24px;
	}

	.participants {
		display: flex;
		justify-content: flex-start;
		.user-row {
			display: block;
			flex: 0;
		}
		.user {
			padding: 0;
		}
	}

</style>
