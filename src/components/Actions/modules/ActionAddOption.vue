<!--
  - SPDX-FileCopyrightText: 2024 Nextcloud contributors
  - SPDX-License-Identifier: AGPL-3.0-or-later
-->

<script setup lang="ts">
import { computed } from 'vue'
import { t } from '@nextcloud/l10n'
import { emit } from '@nextcloud/event-bus'
import NcButton, { ButtonType } from '@nextcloud/vue/components/NcButton'
import AddDateIcon from 'vue-material-design-icons/CalendarPlus.vue'

const buttonAriaLabel = computed(() => props.caption ?? t('polls', 'Add option'))

const props = defineProps({
	caption: {
		type: String,
		default: undefined,
	},
})

async function clickAction() {
	emit('polls:options:add-date', null)
}
</script>

<template>
	<div class="action option-date-add">
		<NcButton
			:type="ButtonType.Primary"
			:aria-label="buttonAriaLabel"
			@click="clickAction">
			<template #icon>
				<AddDateIcon />
			</template>
			<template #default>
				{{ caption }}
			</template>
		</NcButton>
	</div>
</template>
