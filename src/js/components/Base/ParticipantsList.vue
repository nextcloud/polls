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
			{{ n('polls', '%n Participant', '%n Participants', participantsVoted.length) }}
		</h2>
		<h2 v-else>
			{{ t('polls','No Participants until now') }}
		</h2>
		<div v-if="participantsVoted.length" class="participants-list__list">
			<UserItem v-for="(participant) in participantsVoted" :key="participant.userId"
				v-bind="participant"
				:hide-names="true"
				type="user" />
		</div>
	</div>
</template>

<script>
import { mapGetters } from 'vuex'

export default {
	name: 'ParticipantsList',
	computed: {
		...mapGetters({
			participantsVoted: 'poll/participantsVoted',
		}),
	},
}
</script>

<style lang="scss" scoped>
	.participants-list {
		padding: 8px;
	}

	.participants-list__list {
		display: flex;
		justify-content: flex-start;
		flex-wrap: wrap;
		&> * {
			flex: 0;
		}
	}
</style>
