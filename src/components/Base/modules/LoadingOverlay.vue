<!--
  - SPDX-FileCopyrightText: 2018 Nextcloud contributors
  - SPDX-License-Identifier: AGPL-3.0-or-later
-->

<script setup lang="ts">
import { t } from '@nextcloud/l10n'
import { Spinner } from '../../AppIcons/index.ts'
import { onMounted, ref, watch } from 'vue'

const description = ref(t('polls', 'Please wait…'))

const {
	show = false,
	name = t('polls', 'Loading …'),
	loadingTexts = '',
	teleportTo = '#content-vue',
} = defineProps<{
	show: boolean
	name: string
	loadingTexts?: string | string[]
	teleportTo?: string
}>()

const sequentialDescriptionOutput = () => {
	if (loadingTexts instanceof String) {
		description.value = loadingTexts as string
		return
	}

	if (loadingTexts.length === 0) {
		description.value = ''
		return
	}

	if (loadingTexts.length === 1) {
		description.value = loadingTexts[0]
		return
	}

	let index = 0

	const showDescription = () => {
		if (index < loadingTexts.length) {
			if (show === false) {
				return
			}
			description.value = loadingTexts[index]
			index = index + 1
			const delay = 1500 + Math.floor(Math.random() * 1001) - 500
			setTimeout(showDescription, delay)
		} else {
			description.value = loadingTexts[loadingTexts.length - 1]
		}
	}
	showDescription()
}

watch(
	() => show,
	(newValue) => {
		if (newValue === true && loadingTexts.length > 0) {
			sequentialDescriptionOutput()
		}
	},
)

onMounted(() => {
	if (show) {
		sequentialDescriptionOutput()
	}
})
</script>

<template>
	<Teleport :to="teleportTo">
		<div v-show="show" class="loading-overlay">
			<div class="loading-overlay__inner">
				<Spinner class="loading-overlay__spinner" :size="70" />
				<span class="loading-overlay__name">
					{{ name }}
				</span>
				<p class="loading-overlay__description">
					{{ description }}
				</p>
			</div>
		</div>
	</Teleport>
</template>

<style lang="scss">
.loading-overlay {
	position: absolute;
	inset-inline-start: 0;
	top: 0;
	width: 100vw;
	height: 100vh;
	background: var(--color-main-background);
	opacity: 0.9;
	z-index: 9999;

	.loading-overlay__inner {
		display: flex;
		flex-direction: column;
		align-items: center;
		justify-content: center;
		height: 100%;
	}
	.loading-overlay__name {
		margin-bottom: 10px;
		text-align: center;
		font-weight: bold;
		font-size: 20px;
		line-height: 30px;
	}

	.loading-overlay__description {
		color: var(--color-text-maxcontrast);
		text-align: center;
		text-wrap-style: balance;
	}

	.loading-overlay__spinner {
		inset-inline-start: 50%;
	}
}
</style>
