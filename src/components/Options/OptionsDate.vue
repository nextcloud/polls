<!--
  - SPDX-FileCopyrightText: 2018 Nextcloud contributors
  - SPDX-License-Identifier: AGPL-3.0-or-later
-->

<script setup lang="ts">
	import { PropType, ref } from 'vue'
	import { t } from '@nextcloud/l10n'

	import NcEmptyContent from '@nextcloud/vue/dist/Components/NcEmptyContent.js'
	import NcModal from '@nextcloud/vue/dist/Components/NcModal.js'

	import DatePollIcon from 'vue-material-design-icons/CalendarBlank.vue'

	import OptionCloneDate from './OptionCloneDate.vue'
	import OptionItem from './OptionItem.vue'
	import { usePollStore, PollType } from '../../stores/poll.ts'
	import { useOptionsStore, Option } from '../../stores/options.ts'
	import { BoxType } from '../../Types/index.ts'
	import OptionMenu from './OptionMenu.vue'

	const pollStore = usePollStore()
	const optionsStore = useOptionsStore()

	const cloneModal = ref(false)
	const optionToClone = ref<Option>(null)
	const pollType = ref(PollType.Date)

	const cssVar = {
		'var(--content-deleted)': `" (${t('polls', 'deleted')})"`
	}

	const props = defineProps({
		display: {
			type: String as PropType<BoxType>,
			default: BoxType.Date,
		},
	})

</script>

<template>
	<div :style="cssVar">
		<TransitionGroup v-if="optionsStore.list.length"
			tag="ul"
			name="list">
			<OptionItem v-for="(option) in optionsStore.sortedOptions"
				:key="option.id"
				:option="option"
				:poll-type="pollType"
				:display="props.display"
				tag="li">
				<template #actions>
					<div class="menu-wrapper">
						<OptionMenu v-if="pollStore.permissions.edit || option.isOwner" :option="option" />
					</div>
				</template>
			</OptionItem>
		</TransitionGroup>

		<NcEmptyContent v-else
			:name="t('polls', 'No vote options')"
			:description="t('polls', 'Add some!')">
			<template #icon>
				<DatePollIcon />
			</template>
		</NcEmptyContent>

		<NcModal v-if="cloneModal" size="small" :can-close="false">
			<OptionCloneDate :option="optionToClone" class="modal__content" @close="cloneModal = false" />
		</NcModal>
	</div>
</template>

