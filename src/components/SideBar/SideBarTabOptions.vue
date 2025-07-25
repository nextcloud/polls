<!--
  - SPDX-FileCopyrightText: 2018 Nextcloud contributors
  - SPDX-License-Identifier: AGPL-3.0-or-later
-->

<script setup lang="ts">
import { onMounted, onUnmounted } from 'vue'
import { subscribe, unsubscribe } from '@nextcloud/event-bus'
import { t } from '@nextcloud/l10n'

import { useOptionsStore } from '../../stores/options.ts'
import { usePollStore } from '../../stores/poll.ts'

import ConfigBox from '../Base/modules/ConfigBox.vue'
import OptionsDate from '../Options/OptionsDate.vue'
import OptionsDateShift from '../Options/OptionsDateShift.vue'
import OptionsText from '../Options/OptionsText.vue'
import ConfigProposals from '../Configuration/ConfigProposals.vue'
import AddDateIcon from 'vue-material-design-icons/CalendarPlus.vue'
import DateOptionsIcon from 'vue-material-design-icons/CalendarMonth.vue'
import ShiftDateIcon from 'vue-material-design-icons/CalendarStart.vue'
import TextOptionsIcon from 'vue-material-design-icons/FormatListBulletedSquare.vue'
import OptionsTextAddBulk from '../Options/OptionsTextAddBulk.vue'
import ActionAddOption from '../Actions/modules/ActionAddOption.vue'
import { Event } from '../../Types/index.ts'

const optionsStore = useOptionsStore()
const pollStore = usePollStore()

const configBoxProps = {
	delegatedAdminHint: {
		name: t('polls', 'As an admin you may edit this poll'),
	},
	allowProposals: {
		name: t('polls', 'Allow proposals from participants'),
	},
	shiftDate: {
		name: t('polls', 'Shift all date options'),
	},
	dateOptions: {
		name: t('polls', 'Available options'),
	},
	textOptions: {
		name: t('polls', 'Available options'),
	},
}

onMounted(() => {
	subscribe(Event.UpdateOptions, () => optionsStore.load())
})

onUnmounted(() => {
	unsubscribe(Event.UpdateOptions, () => optionsStore.load())
})
</script>

<template>
	<div class="side-bar-tab-options">
		<ConfigBox
			v-if="!pollStore.currentUserStatus.isOwner"
			v-bind="configBoxProps.delegatedAdminHint" />
		<ConfigBox v-bind="configBoxProps.allowProposals">
			<template #icon>
				<AddDateIcon />
			</template>
			<ConfigProposals />
		</ConfigBox>

		<ConfigBox
			v-if="
				pollStore.type === 'datePoll'
				&& optionsStore.options.length
				&& !pollStore.isClosed
			"
			v-bind="configBoxProps.shiftDate">
			<template #icon>
				<ShiftDateIcon />
			</template>
			<OptionsDateShift />
		</ConfigBox>

		<ConfigBox
			v-if="pollStore.type === 'datePoll'"
			v-bind="configBoxProps.dateOptions">
			<template #icon>
				<DateOptionsIcon />
			</template>

			<OptionsDate />

			<template v-if="!pollStore.isClosed" #actions>
				<ActionAddOption :caption="t('polls', 'Add a date')" />
			</template>
		</ConfigBox>

		<ConfigBox
			v-if="pollStore.type === 'textPoll'"
			v-bind="configBoxProps.textOptions">
			<template #icon>
				<TextOptionsIcon />
			</template>

			<OptionsText />

			<template #actions>
				<OptionsTextAddBulk v-if="!pollStore.isClosed" />
			</template>
		</ConfigBox>
	</div>
</template>

<style lang="scss">
.side-bar-tab-options {
	.option-item {
		border-bottom: 1px solid var(--color-border);

		&:active,
		&:hover {
			transition: var(--background-dark) 0.3s ease;
			background-color: var(--color-background-dark);
		}
		.option-item__option--text {
			-webkit-line-clamp: 2;
			line-clamp: 2;
			max-height: 4em;
			-webkit-box-orient: vertical;
			display: -webkit-inline-box;
			overflow: clip;
			text-overflow: ellipsis;
			transition: all 0.3s ease-in-out;
		}
	}

	.sortable:not(.sortable-chosen) {
		.option-item {
			.option-item__option--text {
				&:hover,
				&:active {
					-webkit-line-clamp: initial;
					line-clamp: initial;
					max-height: 12em;
				}
			}
		}
	}
}
</style>
