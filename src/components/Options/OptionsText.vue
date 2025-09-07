<!--
  - SPDX-FileCopyrightText: 2018 Nextcloud contributors
  - SPDX-License-Identifier: AGPL-3.0-or-later
-->

<script setup lang="ts">
import { nextTick, useTemplateRef } from 'vue'
import { useSortable } from '@vueuse/integrations/useSortable'

import { t } from '@nextcloud/l10n'

import OptionItem from './OptionItem.vue'
import { usePollStore } from '../../stores/poll'
import { useOptionsStore } from '../../stores/options'
import OptionMenu from './OptionMenu.vue'

const pollStore = usePollStore()
const optionsStore = useOptionsStore()
// const element = useTemplateRef<HTMLElement>('list')

useSortable(useTemplateRef<HTMLElement>('list'), optionsStore.options, {
	animation: 200,
	group: 'options',
	disabled: false,
	onUpdate: (e: { oldIndex: number; newIndex: number }) => {
		onSort({ oldIndex: e.oldIndex, newIndex: e.newIndex })
		// nextTick required here as moveArrayElement is executed in a microtask
		// so we need to wait until the next tick until that is finished.
		nextTick(() => {
			/* do nothing, wait for nextTick() */
		})
	},
})

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
	<div ref="list" :style="cssVar">
		<div
			v-for="option in optionsStore.options"
			:key="option.id"
			class="sortable">
			<OptionItem
				:key="option.id"
				:option="option"
				:draggable="true"
				show-owner>
				<template #actions>
					<OptionMenu
						v-if="pollStore.permissions.edit || option.isOwner"
						:option="option" />
				</template>
			</OptionItem>
		</div>
	</div>
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
