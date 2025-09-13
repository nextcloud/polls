<!--
  - SPDX-FileCopyrightText: 2023 Nextcloud contributors
  - SPDX-License-Identifier: AGPL-3.0-or-later
-->

<script setup lang="ts">
import QRCode from 'qrcode'
import { Logger } from '../../../helpers/modules/logger'
import { onMounted, ref } from 'vue'

import type { AxiosError } from '@nextcloud/axios'

interface Props {
	name?: string
	subTitle?: string
	description?: string
	encodeText: string
}

const {
	name = '',
	subTitle = '',
	description = '',
	encodeText,
} = defineProps<Props>()

const qrUri = ref<string>('')

/**
 * Generate QR code
 */
async function generateQr() {
	try {
		qrUri.value = await QRCode.toDataURL(encodeText)
	} catch (e) {
		const error = e as AxiosError
		Logger.error('Error on generating QR code', { error: error.message })
	}
}

onMounted(() => {
	generateQr()
})
</script>

<template>
	<div class="qr-code">
		<h2>{{ name }}</h2>
		<slot name="description">
			{{ description }}
		</slot>
		<div class="canvas">
			<img :src="qrUri" :alt="encodeText" />
		</div>
		<h3>{{ subTitle }}</h3>
		<p />
		<p class="qr-url">
			{{ encodeText }}
		</p>
	</div>
</template>

<style lang="scss">
.canvas {
	margin: auto;
	padding: 32px;
}

.qr-url {
	font-size: 0.6em;
	margin-top: 16px;
}

@media print {
	.noprint {
		display: none;
	}
}
</style>
