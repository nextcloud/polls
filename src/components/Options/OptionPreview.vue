<!--
  - SPDX-FileCopyrightText: 2018 Nextcloud contributors
  - SPDX-License-Identifier: AGPL-3.0-or-later
-->

<script setup lang="ts">
import { n, t } from '@nextcloud/l10n'
import { DateTime } from 'luxon'
import { computed, ref } from 'vue'
import CalendarBlankOutlineIcon from 'vue-material-design-icons/CalendarBlankOutline.vue'
import FormatListBulletedSquareIcon from 'vue-material-design-icons/FormatListBulletedSquare.vue'
import MagnifyExpandIcon from 'vue-material-design-icons/MagnifyExpand.vue'
import NcModal from '@/components/Base/modules/CustomNcModal.vue'
import OptionItem from '@/components/Options/OptionItem.vue'
import { getDatesFromOption } from '@/composables/optionDateTime'
import { useOptionsStore } from '@/stores/options'
import { usePollStore } from '@/stores/poll'

defineOptions({
	inheritAttrs: false,
})

const optionsStore = useOptionsStore()
const pollStore = usePollStore()

const MAX_OPTIONS = 5

const optionsExpanded = ref(false)

const previewOptions = computed(() => optionsStore.options.slice(0, MAX_OPTIONS))

function optionLabel(option: (typeof optionsStore.options)[0]): string {
	if (pollStore.type === 'datePoll') {
		const { optionStart, optionEnd, isSameTime, isFullDays, isSameDay } =
			getDatesFromOption(option)
		const fmt = isFullDays ? DateTime.DATE_MED : DateTime.DATETIME_MED
		const start = optionStart.toLocaleString(fmt)
		if (isSameTime || (isFullDays && isSameDay)) return start
		return `${start} – ${optionEnd.toLocaleString(fmt)}`
	}
	return option.text
}
</script>

<template>
	<div
		v-if="optionsStore.options.length"
		v-bind="$attrs"
		class="options_preview"
		role="button"
		:aria-label="t('polls', 'Expand options')"
		@click="optionsExpanded = true">
		<span class="options_preview__expand" aria-hidden="true">
			<MagnifyExpandIcon :size="20" />
		</span>
		<h3>{{ t('polls', 'Available options') }}</h3>
		<ul>
			<li
				v-for="option in previewOptions"
				:key="option.id"
				class="options_preview__item">
				<CalendarBlankOutlineIcon
					v-if="pollStore.type === 'datePoll'"
					:size="16" />
				<FormatListBulletedSquareIcon v-else :size="16" />
				{{ optionLabel(option) }}
			</li>
		</ul>
		<p
			v-if="optionsStore.options.length > MAX_OPTIONS"
			class="options_preview__more">
			{{
				n(
					'polls',
					'+ %n more option',
					'+ %n more options',
					optionsStore.options.length - MAX_OPTIONS,
				)
			}}
		</p>
	</div>

	<NcModal
		v-if="optionsExpanded"
		:name="t('polls', 'Available options')"
		size="normal"
		closeOnClickOutside
		@close="optionsExpanded = false">
		<ul class="options_preview__modal">
			<OptionItem
				v-for="option in optionsStore.options"
				:key="option.id"
				:option="option"
				tag="li" />
		</ul>
	</NcModal>
</template>

<style lang="scss" scoped>
.options_preview {
	position: relative;
	cursor: zoom-in;

	* {
		cursor: zoom-in;
	}

	h3 {
		margin: 0 0 0.5rem;
	}

	ul {
		list-style: none;
		padding: 0;
		margin: 0;
	}

	.options_preview__item {
		display: flex;
		align-items: center;
		gap: 0.4rem;
		white-space: nowrap;
		overflow: hidden;
		text-overflow: ellipsis;
		padding: 0.25rem 0;
		border-bottom: 1px solid var(--color-border);
		background: transparent;

		&:last-child {
			border-bottom: none;
		}
	}

	.options_preview__expand {
		position: absolute;
		top: 0.25rem;
		inset-inline-end: 0.25rem;
		z-index: 1;
		display: flex;
		align-items: center;
		justify-content: center;
		width: 44px;
		height: 44px;
		border-radius: var(--border-radius-large);
		background-color: var(--color-background-hover);
		border: 2px solid var(--color-border);
		color: var(--color-main-text);
		pointer-events: none;
		opacity: 0;
		transition: opacity 0.2s;
	}

	&:hover .options_preview__expand {
		opacity: 1;
	}
	.options_preview__more {
		text-align: center;
		font-weight: 600;
	}
}

.options_preview__modal {
	list-style: none;
	padding: 1rem;
	margin: 0;

	.options_preview__item {
		padding: 0.4rem 0;
		border-bottom: 1px solid var(--color-border);

		&:last-child {
			border-bottom: none;
		}
	}
}
</style>
