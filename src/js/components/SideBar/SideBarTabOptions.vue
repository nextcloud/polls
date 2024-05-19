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
	<div class="side-bar-tab-options">
		<ConfigBox v-if="!currentUser.isOwner" :name="t('polls', 'As an admin you may edit this poll')" />
		<ConfigBox :name="t('polls', 'Allow proposals from participants')">
			<template #icon>
				<AddDateIcon />
			</template>
			<ConfigProposals />
		</ConfigBox>

		<ConfigBox v-if="pollType === 'datePoll' && countOptions && !isPollClosed" :name="t('polls', 'Shift all date options')">
			<template #icon>
				<ShiftDateIcon />
			</template>
			<OptionsDateShift />
		</ConfigBox>

		<ConfigBox v-if="pollType === 'datePoll'" :name="t('polls', 'Available Options')">
			<template #icon>
				<DateOptionsIcon />
			</template>

			<OptionsDate />

			<template #actions>
				<OptionsDateAdd v-if="!isPollClosed"
					:caption="t('polls', 'Add a date')"
					show-caption
					primary />
			</template>
		</ConfigBox>

		<ConfigBox v-if="pollType === 'textPoll'" :name="t('polls', 'Available Options')">
			<template #icon>
				<TextOptionsIcon />
			</template>

			<OptionsText />

			<template #actions>
				<OptionsTextAddBulk v-if="!isPollClosed" />
			</template>
		</ConfigBox>
	</div>
</template>

<script>
import { mapGetters, mapState } from 'vuex'
import { ConfigBox } from '../Base/index.js'
import OptionsDate from '../Options/OptionsDate.vue'
import OptionsDateShift from '../Options/OptionsDateShift.vue'
import OptionsText from '../Options/OptionsText.vue'
import ConfigProposals from '../Configuration/ConfigProposals.vue'
import AddDateIcon from 'vue-material-design-icons/CalendarPlus.vue'
import DateOptionsIcon from 'vue-material-design-icons/CalendarMonth.vue'
import ShiftDateIcon from 'vue-material-design-icons/CalendarStart.vue'
import TextOptionsIcon from 'vue-material-design-icons/FormatListBulletedSquare.vue'
import OptionsDateAdd from '../Options/OptionsDateAdd.vue'
import OptionsTextAddBulk from '../Options/OptionsTextAddBulk.vue'

export default {
	name: 'SideBarTabOptions',

	components: {
		AddDateIcon,
		DateOptionsIcon,
		ShiftDateIcon,
		TextOptionsIcon,
		ConfigBox,
		ConfigProposals,
		OptionsDate,
		OptionsDateShift,
		OptionsText,
		OptionsDateAdd,
		OptionsTextAddBulk,
	},

	computed: {
		...mapGetters({
			isPollClosed: 'poll/isClosed',
			countOptions: 'options/count',
		}),
		...mapState({
			pollType: (state) => state.poll.type,
			currentUser: (state) => state.poll.acl.currentUser,
		}),
	},
}
</script>

<style lang="scss">
.side-bar-tab-options {
	.owner {
		display: flex;
		position: relative;
		left: -16px;
		width: 0;
	}

	.draggable:hover .owner {
		display: none;
	}

	.option-item {
		border-bottom: 1px solid var(--color-border);
		padding: 8px 0;

		&:active,
		&:hover {
			transition: var(--background-dark) 0.3s ease;
			background-color: var(--color-background-dark);
		}
	}
	.option-item__option--text {
		-webkit-line-clamp: 2;
		-webkit-box-orient: vertical;
		display: -webkit-inline-box;
		overflow: clip;
		text-overflow: ellipsis;
		transition: all 0.3s ease-in-out;
		max-height: 4em;

		&:hover,
		&:active {
			-webkit-line-clamp: initial;
			max-height: 12em;
		}
	}
}

</style>
