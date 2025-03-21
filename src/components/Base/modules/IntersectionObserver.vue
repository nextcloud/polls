<!--
  - SPDX-FileCopyrightText: 2024 Nextcloud contributors
  - SPDX-License-Identifier: AGPL-3.0-or-later
-->

<script setup lang="ts">
import { onBeforeUnmount, onMounted, ref } from 'vue'

const inViewport = ref(false)
const observer = ref(null)

const observerTarget = ref<Element>(null)
const emit = defineEmits(['visible'])

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

	observer.observe(observerTarget.value)
})

onBeforeUnmount(() => {
	if (observer.value) {
		observer.value.disconnect()
	}
})
</script>

<template>
	<div ref="observerTarget">
		<slot :in-viewport="inViewport" />
	</div>
</template>
