<!--
  - SPDX-FileCopyrightText: 2018 Nextcloud contributors
  - SPDX-License-Identifier: AGPL-3.0-or-later
-->

<template>
	<div class="side-bar-tab-options">
		<ConfigBox v-if="!isOwner" :name="t('polls', 'As an admin you may edit this poll')" />
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
		<ConfigBox v-if="pollType === 'textRankPoll'" :name="t('polls', 'Available Options')">
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
			isOwner: (state) => state.poll.currentUserStatus.isOwner,
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
