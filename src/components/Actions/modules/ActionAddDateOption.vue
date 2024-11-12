<!--
  - SPDX-FileCopyrightText: 2024 Nextcloud contributors
  - SPDX-License-Identifier: AGPL-3.0-or-later
-->

<script setup lang="ts">
	import { computed, ref } from 'vue'
	import { t } from '@nextcloud/l10n'

	import NcModal from '@nextcloud/vue/dist/Components/NcModal.js'
	import NcButton, { ButtonType } from '@nextcloud/vue/dist/Components/NcButton.js'

	import AddDateIcon from 'vue-material-design-icons/CalendarPlus.vue'

	import OptionsDateAddScreen from '../../Options/OptionsDateAddModal.vue';

	const showModal = ref(false)
	const buttonAriaLabel = computed(() => props.caption ?? t('polls', 'Add date'))

	const props = defineProps({
		caption: {
			type: String,
			default: undefined,
		},
	})

	async function clickAction() {
		showModal.value = true
	}
</script>

<template>
	<div class="action send-confirmations">
		<NcButton :type="ButtonType.Primary"
			:aria-label="buttonAriaLabel"
			@click="clickAction">

			<template #icon>
				<AddDateIcon />
			</template>
			<template #default>
				{{ caption }}
			</template>
		</NcButton>

		<NcModal v-model:show="showModal"
			size="large">
			<OptionsDateAddScreen />
		</NcModal>
	</div>
</template>

<style lang="scss">
.modal-confirmation-result {
	padding: 24px;
	ul {
		list-style: initial;
	}

	.sent-confirmations, .error-confirmations {
		padding: 12px;
	}
}
</style>
