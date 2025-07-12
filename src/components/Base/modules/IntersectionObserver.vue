<!--
  - SPDX-FileCopyrightText: 2024 Nextcloud contributors
  - SPDX-License-Identifier: AGPL-3.0-or-later
-->

<script setup lang="ts">
import { onBeforeUnmount, onMounted, ref } from 'vue'
import NcLoadingIcon from '@nextcloud/vue/components/NcLoadingIcon'

// const model = ref(false)
const model = defineModel<boolean>()

const observer = ref<null | IntersectionObserver>(null)

const observerTarget = ref<null | Element>(null)
const emit = defineEmits(['visible', 'invisible'])

const { loading = false } = defineProps<{ loading?: boolean }>()

onMounted(() => {
	const observer = new IntersectionObserver((entries) => {
		entries.forEach((entry) => {
			if (entry.isIntersecting) {
				model.value = true
				emit('visible')
			} else {
				model.value = false
				emit('invisible')
			}
		})
	})

	observer.observe(observerTarget.value as Element)
})

onBeforeUnmount(() => {
	if (observer.value) {
		observer.value.disconnect()
	}
})
</script>

<template>
	<div ref="observerTarget">
		<NcLoadingIcon v-if="loading" :size="15" />
		<slot v-else :in-viewport="model" />
	</div>
</template>
