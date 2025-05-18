<!--
  - SPDX-FileCopyrightText: 2024 Nextcloud contributors
  - SPDX-License-Identifier: AGPL-3.0-or-later
-->

<script setup lang="ts">
import { computed } from 'vue'
import { t } from '@nextcloud/l10n'
import { emit } from '@nextcloud/event-bus'
import NcButton from '@nextcloud/vue/components/NcButton'
import AddDateIcon from 'vue-material-design-icons/CalendarPlus.vue'
import { Event } from '../../../Types'

const { caption } = defineProps<{ caption?: string }>()
const buttonAriaLabel = computed(() => caption ?? t('polls', 'Add option'))

async function clickAction() {
	emit(Event.AddDate, null)
}
</script>

<template>
	<div class="action option-date-add">
		<NcButton
			:variant="'primary'"
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
