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
		<ConfigBox v-if="!isOwner" :title="t('polls', 'As an admin you may edit this poll')" />
		<ConfigBox :title="t('polls', 'Allow proposals from users')">
			<template #icon>
				<AddDateIcon />
			</template>
			<ConfigProposals />
		</ConfigBox>

		<ConfigBox v-if="pollType === 'datePoll' && countOptions && !closed" :title="t('polls', 'Shift all date options')">
			<template #icon>
				<ShiftDateIcon />
			</template>
			<OptionsDateShift />
		</ConfigBox>

		<ConfigBox v-if="pollType === 'datePoll'" :title="t('polls', 'Available Options')">
			<template #icon>
				<DateOptionsIcon />
			</template>

			<OptionsDate />

			<template #actions>
				<OptionsDateAdd v-if="!closed" />
			</template>
		</ConfigBox>

		<ConfigBox v-if="pollType === 'textPoll'" :title="t('polls', 'Available Options')">
			<template #icon>
				<TextOptionsIcon />
			</template>

			<OptionsText />

			<template #actions>
				<OptionsTextAddBulk v-if="!closed" />
			</template>
		</ConfigBox>
	</div>
</template>

<script>
import { mapGetters, mapState } from 'vuex'
import ConfigBox from '../Base/ConfigBox.vue'
import OptionsDate from '../Options/OptionsDate.vue'
import OptionsDateShift from '../Options/OptionsDateShift.vue'
import OptionsText from '../Options/OptionsText.vue'
import ConfigProposals from '../Configuration/ConfigProposals.vue'
import AddDateIcon from 'vue-material-design-icons/CalendarPlus.vue'
import DateOptionsIcon from 'vue-material-design-icons/CalendarMonth.vue'
import ShiftDateIcon from 'vue-material-design-icons/CalendarStart.vue'
import TextOptionsIcon from 'vue-material-design-icons/FormatListBulletedSquare.vue'

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
		OptionsDateAdd: () => import('../Options/OptionsDateAdd.vue'),
		OptionsTextAddBulk: () => import('../Options/OptionsTextAddBulk.vue'),
	},

	computed: {
		...mapGetters({
			closed: 'poll/isClosed',
			countOptions: 'options/count',
			pollTypeIcon: 'poll/typeIcon',
		}),
		...mapState({
			pollType: (state) => state.poll.type,
			isOwner: (state) => state.poll.acl.isOwner,
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
		&:active,
		&:hover {
			transition: var(--background-dark) 0.3s ease;
			background-color: var(--color-background-dark);
		}
	}
}

</style>
