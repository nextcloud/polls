<!--
  - SPDX-FileCopyrightText: 2021 Nextcloud contributors
  - SPDX-License-Identifier: AGPL-3.0-or-later
-->

<script setup lang="ts">
import { ref, computed } from 'vue'
import { t, n } from '@nextcloud/l10n'

import NcButton, { ButtonVariant } from '@nextcloud/vue/components/NcButton'

import DeleteIcon from 'vue-material-design-icons/Delete.vue'
import RestoreIcon from 'vue-material-design-icons/Recycle.vue'
import LockIcon from 'vue-material-design-icons/Lock.vue'
import UndoIcon from 'vue-material-design-icons/ArrowULeftTop.vue'

const props = defineProps({
	timeout: {
		type: Number,
		default: 4,
	},
	name: {
		type: String,
		default: t('polls', 'Delete'),
	},
	iconSize: {
		type: Number,
		default: 20,
	},
	restore: {
		type: Boolean,
		default: false,
	},
	lock: {
		type: Boolean,
		default: false,
	},
})

const deleteInterval = ref<null | NodeJS.Timeout>(null)
const deleteTimeout = ref<null | NodeJS.Timeout>(null)
const countdown = ref(4)

const countdownTitle = computed(() =>
	n(
		'polls',
		'Deleting in {countdown} second',
		'Deleting in {countdown} seconds',
		countdown.value,
		{ countdown: countdown.value },
	),
)

const computedTitle = computed(() =>
	deleteTimeout.value ? countdownTitle.value : props.name,
)

const emit = defineEmits(['delete', 'restore'])


function deleteItem(): void {
	// delete immediately
	if (props.timeout === 0) {
		emit('delete')
		return
	}

	countdown.value = props.timeout

	deleteInterval.value = setInterval(() => {
		countdown.value -= 1
		if (countdown.value < 0) {
			countdown.value = 0
		}
	}, 1000)

	deleteTimeout.value = setTimeout(() => {
		emit('delete')
		deleteTimeout.value = null
		deleteInterval.value = null
		countdown.value = props.timeout
	}, props.timeout * 1000)
}

function cancelDelete(): void {
	clearTimeout(deleteTimeout.value as NodeJS.Timeout)
	clearInterval(deleteInterval.value as NodeJS.Timeout)
	deleteTimeout.value = null
	deleteInterval.value = null
	countdown.value = props.timeout
}

function restoreItem(): void {
	clearTimeout(deleteTimeout.value as NodeJS.Timeout)
	clearInterval(deleteInterval.value as NodeJS.Timeout)
	deleteTimeout.value = null
	deleteInterval.value = null
	emit('restore')
}
</script>

<template>
	<div class="">
		<NcButton
			:name="computedTitle"
			:variant="ButtonVariant.Tertiary"
			:aria-label="computedTitle">
			<template #icon>
				<RestoreIcon
					v-if="restore"
					:size="iconSize"
					@click="restoreItem()" />
				<UndoIcon
					v-else-if="deleteTimeout"
					:size="iconSize"
					@click="cancelDelete()" />
				<LockIcon v-else-if="lock" :size="iconSize" @click="deleteItem()" />
				<DeleteIcon v-else :size="iconSize" @click="deleteItem()" />
			</template>
		</NcButton>
	</div>
</template>

<style lang="scss">
.material-design-icon {
	&.delete-icon,
	&.undo-icon {
		cursor: pointer;
	}

	&.delete-icon:hover {
		color: var(--color-error-hover);
	}
	&.arrow-u-left-top-icon {
		/* force the undo icon always to be visible */
		visibility: visible !important;
	}
}
</style>
