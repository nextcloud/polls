<!--
  - SPDX-FileCopyrightText: 2021 Nextcloud contributors
  - SPDX-License-Identifier: AGPL-3.0-or-later
-->

<script setup lang="ts">
import { t } from '@nextcloud/l10n'

import NcButton, { ButtonVariant } from '@nextcloud/vue/components/NcButton'
import NcModal from '@nextcloud/vue/components/NcModal'

import { NcActionButton, NcAppNavigationNew } from '@nextcloud/vue'
import { ButtonMode } from '../../../Types'

interface Props {
	buttonVariant?: ButtonVariant
	buttonMode?: ButtonMode
	buttonCaption?: string
	modalSize?: string
	noClose?: boolean
}

const showModal = defineModel<boolean>('showModal', { default: false })

const {
	buttonVariant = 'primary',
	buttonMode = 'native',
	buttonCaption = t('polls', 'Click'),
	modalSize = 'normal',
	noClose = false,
} = defineProps<Props>()
</script>

<template>
	<div class="button-modal">
		<!-- The NcAppNavigationNew component is used to display a button in
			the navigation bar (Edge case for ActionAddPoll). -->
		<NcAppNavigationNew
			v-if="buttonMode === 'navigation'"
			button-class="icon-add"
			:text="buttonCaption"
			@click="showModal = true" />
		<NcActionButton
			v-else-if="buttonMode === 'actionMenu'"
			button-class="icon-add"
			:text="buttonCaption"
			@click="showModal = true" />
		<NcButton
			v-else-if="buttonMode === 'native'"
			:variant="buttonVariant"
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
