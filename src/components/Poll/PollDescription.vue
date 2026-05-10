<!--
  - SPDX-FileCopyrightText: 2018 Nextcloud contributors
  - SPDX-License-Identifier: AGPL-3.0-or-later
-->

<script setup lang="ts">
import { ref } from 'vue'
import { t } from '@nextcloud/l10n'

import NcModal from '@/components/Base/modules/CustomNcModal.vue'
import MagnifyExpandIcon from 'vue-material-design-icons/MagnifyExpand.vue'

import MarkDownDescription from '@/components/Poll/MarkDownDescription.vue'
import { usePollStore } from '@/stores/poll'

defineOptions({
	inheritAttrs: false,
})

const pollStore = usePollStore()

const descriptionExpanded = ref(false)
</script>

<template>
	<div
		v-if="pollStore.configuration.description"
		v-bind="$attrs"
		class="poll_description"
		role="button"
		:aria-label="t('polls', 'Expand description')"
		@click="descriptionExpanded = true">
		<span class="poll_description_expand" aria-hidden="true">
			<MagnifyExpandIcon :size="20" />
		</span>
		<MarkDownDescription />
	</div>

	<NcModal
		v-if="descriptionExpanded"
		:name="pollStore.configuration.title"
		size="large"
		close-on-click-outside
		@close="descriptionExpanded = false">
		<MarkDownDescription />
	</NcModal>
</template>

<style lang="scss" scoped>
.poll_description {
	max-height: 13rem;
	position: relative;
	min-height: 8rem;
	overflow: hidden;
	cursor: zoom-in;

	* {
		cursor: zoom-in;
	}

	.markdown-description {
		height: 100%;
		overflow: hidden;
		background: none;
	}

	.poll_description_expand {
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

	&:hover .poll_description_expand {
		opacity: 1;
	}
}

.modal-container__content .markdown-description {
	--markdown-description-bg: var(--color-main-background);
}
</style>
