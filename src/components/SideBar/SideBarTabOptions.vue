<!--
  - SPDX-FileCopyrightText: 2018 Nextcloud contributors
  - SPDX-License-Identifier: AGPL-3.0-or-later
-->

<template>
	<div class="side-bar-tab-options">
		<ConfigBox v-if="!aclStore.currentUser.isOwner"
			v-bind="configBoxProps.delegatedAdminHint" />
		<ConfigBox v-bind="configBoxProps.allowProposals">
			<template #icon>
				<AddDateIcon />
			</template>
			<ConfigProposals />
		</ConfigBox>

		<ConfigBox v-if="pollStore.type === 'datePoll' && optionsStore.list.length && !pollStore.isClosed" v-bind="configBoxProps.shiftDate">
			<template #icon>
				<ShiftDateIcon />
			</template>
			<OptionsDateShift />
		</ConfigBox>

		<ConfigBox v-if="pollStore.type === 'datePoll'" v-bind="configBoxProps.dateOptions">
			<template #icon>
				<DateOptionsIcon />
			</template>

			<OptionsDate />

			<template #actions>
				<OptionsDateAdd v-if="!pollStore.isClosed" v-bind="optionAddDatesProps" />
			</template>
		</ConfigBox>

		<ConfigBox v-if="pollStore.type === 'textPoll'" v-bind="configBoxProps.textOptions">
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

<script>
import { mapStores } from 'pinia'
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
import { t } from '@nextcloud/l10n'
import { useAclStore } from '../../stores/acl.ts'
import { useOptionsStore } from '../../stores/options.ts'
import { usePollStore } from '../../stores/poll.ts'

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

	data() {
		return {
			configBoxProps: {
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
					name: t('polls', 'Available Options'),
				},
				textOptions: {
					name: t('polls', 'Available Options'),
				},
			},
			optionAddDatesProps: {
				caption: t('polls', 'Add a date'),
				showCaption: true,
				primary: true,
			},
		}
	},

	computed: {
		...mapStores(useAclStore, useOptionsStore, usePollStore),
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
