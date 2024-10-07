<!--
  - SPDX-FileCopyrightText: 2018 Nextcloud contributors
  - SPDX-License-Identifier: AGPL-3.0-or-later
-->

<script setup lang="ts">
	import { ref } from 'vue'
	import { t } from '@nextcloud/l10n'

	import NcActions from '@nextcloud/vue/dist/Components/NcActions.js'
	import NcActionButton from '@nextcloud/vue/dist/Components/NcActionButton.js'
	import NcEmptyContent from '@nextcloud/vue/dist/Components/NcEmptyContent.js'
	import NcModal from '@nextcloud/vue/dist/Components/NcModal.js'

	import CloneDateIcon from 'vue-material-design-icons/CalendarMultiple.vue'
	import DatePollIcon from 'vue-material-design-icons/CalendarBlank.vue'
	import DeleteIcon from 'vue-material-design-icons/Delete.vue'
	import RestoreIcon from 'vue-material-design-icons/Recycle.vue'
	import ConfirmIcon from 'vue-material-design-icons/CheckboxBlankOutline.vue'
	import UnconfirmIcon from 'vue-material-design-icons/CheckboxMarkedOutline.vue'

	import OptionCloneDate from './OptionCloneDate.vue'
	import OptionItem from './OptionItem.vue'
	import OptionItemOwner from './OptionItemOwner.vue'
	import { usePollStore, PollType } from '../../stores/poll.ts'
	import { useOptionsStore, Option } from '../../stores/options.ts'
	import { BoxType } from '../../Types/index.ts'

	const pollStore = usePollStore()
	const optionsStore = useOptionsStore()

	const cloneModal = ref(false)
	const optionToClone = ref<Option>(null)
	const pollType = ref(PollType.Date)

	const cssVar = {
		'var(--content-deleted)': `" (${t('polls', 'deleted')})"`
	}

	function cloneOptionModal(option: Option) {
		optionToClone.value = option
		cloneModal.value = true
	}

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
				:display="BoxType.Date"
				tag="li">
				<template #icon>
					<OptionItemOwner v-if="pollStore.permissions.addOptions"
						:avatar-size="24"
						:option="option"
						class="owner" />
				</template>
				<template v-if="pollStore.permissions.edit" #actions>
					<NcActions v-if="!pollStore.isClosed" class="action">
						<NcActionButton v-if="!option.deleted" :name="t('polls', 'Delete option')" @click="optionsStore.delete({ option })">
							<template #icon>
								<DeleteIcon />
							</template>
						</NcActionButton>
						<NcActionButton v-if="option.deleted" :name="t('polls', 'Restore option')" @click="optionsStore.restore({ option })">
							<template #icon>
								<RestoreIcon />
							</template>
						</NcActionButton>
						<NcActionButton v-if="!pollStore.isClosed" :name="t('polls', 'Clone option')" @click="cloneOptionModal(option)">
							<template #icon>
								<CloneDateIcon />
							</template>
						</NcActionButton>

						<NcActionButton v-if="!option.deleted && !pollStore.isClosed"
							:name="option.confirmed ? t('polls', 'Unconfirm option') : t('polls', 'Confirm option')"
							@click="optionsStore.confirm({ option })">
							<template #icon>
								<UnconfirmIcon v-if="option.confirmed" />
								<ConfirmIcon v-else />
							</template>
						</NcActionButton>
					</NcActions>
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

