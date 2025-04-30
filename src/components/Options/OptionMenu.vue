<!--
  - SPDX-FileCopyrightText: 2018 Nextcloud contributors
  - SPDX-License-Identifier: AGPL-3.0-or-later
-->

<script setup lang="ts">
import { computed, PropType, ref } from 'vue'
import { t } from '@nextcloud/l10n'

import NcActions from '@nextcloud/vue/components/NcActions'
import NcActionButton from '@nextcloud/vue/components/NcActionButton'
import NcModal from '@nextcloud/vue/components/NcModal'

import CloneDateIcon from 'vue-material-design-icons/CalendarMultiple.vue'
import DeleteIcon from 'vue-material-design-icons/Delete.vue'
import RestoreIcon from 'vue-material-design-icons/Recycle.vue'
import ConfirmIcon from 'vue-material-design-icons/CheckboxBlankOutline.vue'
import UnconfirmIcon from 'vue-material-design-icons/CheckboxMarkedOutline.vue'
import OptionSortIcon from 'vue-material-design-icons/SortBoolAscendingVariant.vue'

import OptionCloneDate from './OptionCloneDate.vue'
import { usePollStore } from '../../stores/poll.ts'
import { useOptionsStore, Option } from '../../stores/options.ts'
import { useVotesStore } from '../../stores/votes.ts'

const pollStore = usePollStore()
const optionsStore = useOptionsStore()
const votesStore = useVotesStore()

const cloneModal = ref(false)

const props = defineProps({
	option: {
		type: Object as PropType<Option>,
		default: null,
	},
	useSort: {
		type: Boolean,
		default: false,
	},
})

const deleteOrRestoreStaticText = computed(() =>
	props.option.deleted
		? t('polls', 'Restore option')
		: t('polls', 'Delete option'),
)

const deleteAllowed = computed(
	() =>
		(props.option.isOwner || pollStore.permissions.edit) && !pollStore.isClosed,
)
const confirmAllowed = computed(
	() => !props.option.deleted && pollStore.isClosed && pollStore.permissions.edit,
)
const cloneAllowed = computed(
	() => !props.option.deleted && !pollStore.isClosed && pollStore.permissions.edit,
)

/**
 *
 */
function cloneOptionModal() {
	cloneModal.value = true
}

/**
 *
 */
function deleteRestoreOption() {
	if (props.option.deleted) {
		optionsStore.restore({ option: props.option })
		return
	}
	optionsStore.delete({ option: props.option })
}

/**
 *
 */
function confirmOption() {
	optionsStore.confirm({ option: props.option })
}
</script>

<template>
	<NcActions class="option-menu">
		<NcActionButton
			v-if="deleteAllowed"
			:name="deleteOrRestoreStaticText"
			@click="deleteRestoreOption()">
			<template #icon>
				<DeleteIcon v-if="!props.option.deleted" />
				<RestoreIcon v-else />
			</template>
		</NcActionButton>

		<NcActionButton
			v-if="cloneAllowed"
			:name="t('polls', 'Clone option')"
			@click="cloneOptionModal()">
			<template #icon>
				<CloneDateIcon />
			</template>
		</NcActionButton>

		<NcActionButton
			v-if="confirmAllowed"
			:name="
				props.option.confirmed
					? t('polls', 'Unconfirm option')
					: t('polls', 'Confirm option')
			"
			@click="confirmOption()">
			<template #icon>
				<UnconfirmIcon v-if="props.option.confirmed" />
				<ConfirmIcon v-else />
			</template>
			{{
				props.option.confirmed
					? t('polls', 'Unconfirm option')
					: t('polls', 'Confirm option')
			}}
		</NcActionButton>

		<NcActionButton
			v-if="useSort && pollStore.permissions.edit"
			close-after-click
			:name="t('polls', 'Sort')"
			@click="votesStore.setSort({ optionId: option.id })">
			<template #icon>
				<OptionSortIcon />
			</template>
		</NcActionButton>
	</NcActions>

	<NcModal v-if="cloneModal" size="small" no-close>
		<OptionCloneDate
			:option="props.option"
			class="modal__content"
			@close="cloneModal = false" />
	</NcModal>
</template>
