<!--
  - SPDX-FileCopyrightText: 2024 Nextcloud contributors
  - SPDX-License-Identifier: AGPL-3.0-or-later
-->

<script setup lang="ts">
import { onBeforeUnmount, onMounted, ref } from 'vue'
import NcLoadingIcon from '@nextcloud/vue/components/NcLoadingIcon'

const inViewport = ref(false)
const observer = ref<null | IntersectionObserver>(null)

const observerTarget = ref<null | Element>(null)
const emit = defineEmits(['visible'])

const { loading } = defineProps<{ loading: boolean }>()

onMounted(() => {
	const observer = new IntersectionObserver((entries) => {
		entries.forEach((entry) => {
			if (entry.isIntersecting) {
				inViewport.value = true
				emit('visible')
			} else {
				inViewport.value = false
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
		<slot v-else :in-viewport="inViewport" />
	</div>
</template>
