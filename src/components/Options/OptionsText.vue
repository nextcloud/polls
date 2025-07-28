<!--
  - SPDX-FileCopyrightText: 2018 Nextcloud contributors
  - SPDX-License-Identifier: AGPL-3.0-or-later
-->

<script setup lang="ts">
import { nextTick, useTemplateRef } from 'vue'
import { useSortable } from '@vueuse/integrations/useSortable'

import { t } from '@nextcloud/l10n'
import NcEmptyContent from '@nextcloud/vue/components/NcEmptyContent'
import NcActions from '@nextcloud/vue/components/NcActions'
import NcActionButton from '@nextcloud/vue/components/NcActionButton'

import DeleteIcon from 'vue-material-design-icons/Delete.vue'
import RestoreIcon from 'vue-material-design-icons/Recycle.vue'
import TextPollIcon from 'vue-material-design-icons/FormatListBulletedSquare.vue'
import ConfirmIcon from 'vue-material-design-icons/CheckboxBlankOutline.vue'
import UnconfirmIcon from 'vue-material-design-icons/CheckboxMarkedOutline.vue'

import OptionItem from './OptionItem.vue'
import OptionsTextAdd from './OptionsTextAdd.vue'
import { usePollStore } from '../../stores/poll'
import { useOptionsStore } from '../../stores/options'

const pollStore = usePollStore()
const optionsStore = useOptionsStore()
const element = useTemplateRef<HTMLElement>('list')

const dragOptions = {
	animation: 200,
	group: 'description',
	disabled: false,
	ghostClass: 'ghost',
	onUpdate: (e: { oldIndex: number; newIndex: number }) => {
		onSort({ oldIndex: e.oldIndex, newIndex: e.newIndex })
		// nextTick required here as moveArrayElement is executed in a microtask
		// so we need to wait until the next tick until that is finished.
		nextTick(() => {
			/* do nothing, wait for nextTick() */
		})
	},
}

useSortable(element, optionsStore.options, dragOptions)

const cssVar = {
	'--content-deleted': `" (${t('polls', 'deleted')})"`,
}

/**
 *
 * @param event
 * @param event.oldIndex
 * @param event.newIndex
 */
function onSort(event: { oldIndex: number; newIndex: number }) {
	optionsStore.changeOrder(event.oldIndex, event.newIndex)
}
</script>

<template>
	<OptionsTextAdd v-if="!pollStore.isClosed" />
	<div v-if="optionsStore.options.length" ref="list" :style="cssVar">
		<div
			v-for="option in optionsStore.options"
			:key="option.id"
			class="sortable">
			<OptionItem
				:key="option.id"
				:option="option"
				:draggable="true"
				show-owner>
				<template v-if="pollStore.permissions.edit" #actions>
					<NcActions v-if="!pollStore.isClosed" class="action">
						<NcActionButton
							v-if="!option.deleted"
							:name="t('polls', 'Delete option')"
							:aria-label="t('polls', 'Delete option')"
							@click="optionsStore.delete({ option })">
							<template #icon>
								<DeleteIcon />
							</template>
						</NcActionButton>
						<NcActionButton
							v-if="option.deleted"
							:name="t('polls', 'Restore option')"
							:aria-label="t('polls', 'Restore option')"
							@click="optionsStore.restore({ option })">
							<template #icon>
								<RestoreIcon />
							</template>
						</NcActionButton>
						<NcActionButton
							v-if="!option.deleted && !pollStore.isClosed"
							:name="
								option.confirmed
									? t('polls', 'Unconfirm option')
									: t('polls', 'Confirm option')
							"
							:aria-label="
								option.confirmed
									? t('polls', 'Unconfirm option')
									: t('polls', 'Confirm option')
							"
							type="tertiary"
							@click="optionsStore.confirm({ option })">
							<template #icon>
								<UnconfirmIcon v-if="option.confirmed" />
								<ConfirmIcon v-else />
							</template>
						</NcActionButton>
					</NcActions>
				</template>
			</OptionItem>
		</div>
	</div>

	<NcEmptyContent
		v-else
		:name="t('polls', 'No vote options')"
		:description="t('polls', 'Add some!')">
		<template #icon>
			<TextPollIcon />
		</template>
	</NcEmptyContent>
</template>

<style lang="scss">
.optionAdd {
	display: flex;
}

.newOption {
	margin-inline-start: 40px;
	flex: 1;
	&:empty:before {
		color: grey;
	}
}

.flip-list-move {
	transition: transform 0.5s;
}

.no-move {
	transition: transform 0s;
}

.ghost {
	opacity: 0.5;
	background: var(--color-primary-element-hover);
}

.submit-option {
	width: 30px;
	background-color: transparent;
	border: none;
	opacity: 0.3;
	cursor: pointer;
}
</style>
