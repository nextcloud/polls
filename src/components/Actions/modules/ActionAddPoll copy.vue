<!--
  - SPDX-FileCopyrightText: 2021 Nextcloud contributors
  - SPDX-License-Identifier: AGPL-3.0-or-later
-->

<script setup lang="ts">
import { computed, ref } from 'vue'
import { t } from '@nextcloud/l10n'

import NcButton, { ButtonType } from '@nextcloud/vue/components/NcButton'
import NcModal from '@nextcloud/vue/components/NcModal'

import CreateDlg from '../../Create/CreateDlg.vue'

import PlusIcon from 'vue-material-design-icons/Plus.vue'
import { NcAppNavigationNew } from '@nextcloud/vue'

const props = defineProps({
	caption: {
		type: String,
		default: t('polls', 'Add poll'),
	},
	modalSize: {
		type: String,
		default: 'normal',
	},
	inNavigation: {
		type: Boolean,
		default: false,
	},
})

const buttonCaption = computed(() =>
	props.inNavigation ? t('polls', 'New poll') : props.caption,
)
const showModal = ref(false)
</script>

<template>
	<div class="action">
		<NcAppNavigationNew
			v-if="inNavigation"
			button-class="icon-add"
			:text="buttonCaption"
			@click="showModal = true" />

		<NcButton
			v-else
			:type="ButtonType.Primary"
			:aria-label="buttonCaption"
			@click="showModal = true">
			<template #icon>
				<PlusIcon size="20" decorative />
			</template>
			{{ buttonCaption }}
		</NcButton>

		<NcModal
			v-model:show="showModal"
			:name="buttonCaption"
			:size="modalSize"
			:can-close="true"
			@close="showModal = false">
			<h2>{{ buttonCaption }}</h2>
			<CreateDlg @cancel="showModal = false" />
		</NcModal>
	</div>
</template>
