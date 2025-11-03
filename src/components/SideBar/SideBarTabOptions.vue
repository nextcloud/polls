<!--
  - SPDX-FileCopyrightText: 2018 Nextcloud contributors
  - SPDX-License-Identifier: AGPL-3.0-or-later
-->

<script setup lang="ts">
import { onMounted, onUnmounted } from 'vue'
import { subscribe, unsubscribe } from '@nextcloud/event-bus'
import { t } from '@nextcloud/l10n'

import { useOptionsStore } from '../../stores/options'
import { usePollStore } from '../../stores/poll'

import ConfigBox from '../Base/modules/ConfigBox.vue'
import OptionsDate from '../Options/OptionsDate.vue'
import OptionsDateShift from '../Options/OptionsDateShift.vue'
import OptionsText from '../Options/OptionsText.vue'
import ConfigProposals from '../Configuration/ConfigProposals.vue'
import AddDateIcon from 'vue-material-design-icons/CalendarPlusOutline.vue'
import DateOptionsIcon from 'vue-material-design-icons/CalendarMonthOutline.vue'
import ShiftDateIcon from 'vue-material-design-icons/CalendarStartOutline.vue'
import TextOptionsIcon from 'vue-material-design-icons/FormatListBulletedSquare.vue'
import DatePollIcon from 'vue-material-design-icons/CalendarBlankOutline.vue'
import OptionsTextAddBulk from '../Options/OptionsTextAddBulk.vue'
import ActionAddOption from '../Actions/modules/ActionAddOption.vue'
import { Event } from '../../Types'
import OptionsTextAdd from '../Options/OptionsTextAdd.vue'
import { NcEmptyContent } from '@nextcloud/vue'

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

		<OptionsDate v-if="optionsStore.options.length" class="options-list" />

		<NcEmptyContent
			v-else
			:name="t('polls', 'No vote options')"
			:description="t('polls', 'Add some!')">
			<template #icon>
				<DatePollIcon />
			</template>
		</NcEmptyContent>

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

		<OptionsTextAdd v-if="!pollStore.isClosed" />

		<OptionsText v-if="optionsStore.options.length" class="options-list" />

		<NcEmptyContent
			v-else
			:name="t('polls', 'No vote options')"
			:description="t('polls', 'Add some!')">
			<template #icon>
				<TextOptionsIcon />
			</template>
		</NcEmptyContent>

		<template #actions>
			<OptionsTextAddBulk v-if="!pollStore.isClosed" />
		</template>
	</ConfigBox>
</template>

<style lang="scss">
.options-list {
	display: grid;
	grid-template-columns: auto 1fr auto auto;
	place-items: center;

	.option-item {
		grid-template-columns: subgrid;
		grid-column: 1 / 5;
	}
}
</style>
