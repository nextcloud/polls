<!--
  - SPDX-FileCopyrightText: 2021 Nextcloud contributors
  - SPDX-License-Identifier: AGPL-3.0-or-later
-->

<script setup lang="ts">
import { PropType } from 'vue'
import { t } from '@nextcloud/l10n'

import NcButton, { ButtonType } from '@nextcloud/vue/components/NcButton'
import NcModal from '@nextcloud/vue/components/NcModal'

import { NcActionButton, NcAppNavigationNew } from '@nextcloud/vue'
import { ButtonMode } from '../../../Types'

const showModal = defineModel('showModal', {
	type: Boolean,
})

defineProps({
	buttonCaption: {
		type: String,
		default: t('polls', 'Click'),
	},
	modalSize: {
		type: String,
		default: 'normal',
	},
	buttonMode: {
		type: String as PropType<ButtonMode>,
		default: ButtonMode.Native,
	},
	buttonType: {
		type: String as PropType<ButtonType>,
		default: ButtonType.Primary,
	},
	noClose: {
		type: Boolean,
		default: false,
	},
})
</script>

<template>
	<div class="button-modal">
		<!-- The NcAppNavigationNew component is used to display a button in
			the navigation bar (Edge case for ActionAddPoll). -->
		<NcAppNavigationNew
			v-if="buttonMode === ButtonMode.Navigation"
			button-class="icon-add"
			:text="buttonCaption"
			@click="showModal = true" />
		<NcActionButton
			v-else-if="buttonMode === ButtonMode.ActionMenu"
			button-class="icon-add"
			:text="buttonCaption"
			@click="showModal = true" />
		<NcButton
			v-else-if="buttonMode === ButtonMode.Native"
			:type="buttonType"
			:aria-label="buttonCaption"
			@click="showModal = true">
			<template #icon>
				<slot name="icon" />
			</template>
			<slot name="caption">
				{{ buttonCaption }}
			</slot>
		</NcButton>

		<NcModal
			v-model:show="showModal"
			:name="buttonCaption"
			:size="modalSize"
			:no-close="noClose"
			@close="showModal = false">
			<h2>{{ buttonCaption }}</h2>
			<slot name="modal-content" />
		</NcModal>
	</div>
</template>
