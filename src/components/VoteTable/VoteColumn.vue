<!--
  - SPDX-FileCopyrightText: 2018 Nextcloud contributors
  - SPDX-License-Identifier: AGPL-3.0-or-later
-->

<script setup lang="ts">
	import { computed, PropType } from 'vue'
	import { t } from '@nextcloud/l10n'
	import { getCurrentUser } from '@nextcloud/auth'
	
	import NcButton, { ButtonType } from '@nextcloud/vue/dist/Components/NcButton.js'
	
	import UnconfirmIcon from 'vue-material-design-icons/CheckboxMarkedOutline.vue'
	import ConfirmIcon from 'vue-material-design-icons/CheckboxBlankOutline.vue'

	import Counter from '../Options/Counter.vue'
	import OptionItem from '../Options/OptionItem.vue'
	import VoteItem from './VoteItem.vue'
	import CalendarPeek from '../Calendar/CalendarPeek.vue'
	import OptionItemOwner from '../Options/OptionItemOwner.vue'
	import { usePollStore, PollType } from '../../stores/poll.ts'
	import { usePreferencesStore } from '../../stores/preferences.ts'
	import { useOptionsStore, Option } from '../../stores/options.ts'
	import { BoxType } from '../../Types/index.ts'

	const pollStore = usePollStore()
	const preferencesStore = usePreferencesStore()
	const optionsStore = useOptionsStore()

	const props = defineProps({
		option: {
			type: Object as PropType<Option>,
			default: undefined,
		},
	})

	const componentClass = computed(() => {
		const classList = ['vote-column']
		if (props.option.locked) {
			classList.push('locked')
		}

		if (props.option.confirmed && pollStore.isClosed) {
			classList.push('confirmed')
		}
		if (props.option.votes.currentUser) {
			classList.push(props.option.votes.currentUser)
		}

		return classList
	})

	const confirmButtonCaption = computed(() => props.option.confirmed ? t('polls', 'Unconfirm option') : t('polls', 'Confirm option'))
	const showCalendarPeek = computed(() => pollStore.type === PollType.Date && getCurrentUser() && preferencesStore.user.calendarPeek)


</script>

<template>
	<div :class="componentClass">
		<div class="column-header">
			<OptionItem :option="option" :poll-type="pollStore.type" :display="pollStore.type === PollType.Date ? BoxType.Date : BoxType.Text" />
	
			<Counter v-if="pollStore.permissions.seeResults"
				:show-maybe="pollStore.configuration.allowMaybe"
				:option="option" />
	
			<CalendarPeek v-if="showCalendarPeek"
				:focus-trap="false"
				:option="option" />
		</div>

		<VoteItem v-for="(participant) in pollStore.safeParticipants"
			:key="participant.id"
			:user="participant"
			:option="option" />

		<OptionItemOwner v-if="optionsStore.proposalsExist"
			:option="option"
			:avatar-size="24"
			class="owner" />

		<!-- <FlexSpacer v-if="pollStore.type === PollType.Date && viewMode === ViewMode.ListView" /> -->

		<div v-if="pollStore.permissions.edit && pollStore.isClosed" class="action confirm">
			<NcButton :title="confirmButtonCaption"
				:aria-label="confirmButtonCaption"
				:type=ButtonType.Tertiary
				@click="optionsStore.confirm({option: props.option})">
				<template #icon>
					<UnconfirmIcon v-if="option.confirmed" :size="20" />
					<ConfirmIcon v-else :size="20" />
				</template>
			</NcButton>
		</div>
	</div>
</template>

<style lang="scss">

.vote-style-beta-510 .vote-column {
	border-radius: var(--border-radius-large);

	&:hover {
		background-color: var(--color-background-dark);
	}

	&.locked:hover {
		background-color: var(--color-polls-background-no);
	}
}

</style>
