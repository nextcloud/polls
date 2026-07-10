<!--
  - SPDX-FileCopyrightText: 2024 Nextcloud contributors
  - SPDX-License-Identifier: AGPL-3.0-or-later
-->

<script setup lang="ts">
import { onBeforeUnmount, onMounted, ref } from 'vue'
import NcLoadingIcon from '@nextcloud/vue/components/NcLoadingIcon'

// const model = ref(false)
interface Props {
	orientation?: 'horizontal' | 'vertical'
	loading?: boolean
}
const model = defineModel<boolean>()

const { orientation = 'horizontal', loading = false } = defineProps<Props>()

const emit = defineEmits(['visible', 'invisible'])

const observer = ref<null | IntersectionObserver>(null)

const observerTarget = ref<null | Element>(null)
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
	<div
		ref="observerTarget"
		:class="{
			'horizontal-fixed': orientation === 'horizontal',
			'vertical-fixed': orientation === 'vertical',
		}">
		<NcLoadingIcon v-if="loading" :size="15" />
		<slot v-else :inViewport="model" />
	</div>
</template>

<style lang="css" scoped>
.vertical-fixed {
	position: sticky;
	top: 0;
	height: 100%;
}

.horizontal-fixed {
	position: sticky;
	inset-inline-start: 0;
	width: 100%;
}
</style>
