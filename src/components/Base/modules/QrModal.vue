<!--
  - SPDX-FileCopyrightText: 2023 Nextcloud contributors
  - SPDX-License-Identifier: AGPL-3.0-or-later
-->

<template>
	<div class="qr-code">
		<h2>{{ name }}</h2>
		<slot name="description">
			{{ description }}
		</slot>
		<div class="canvas">
			<img :src="qrUri" :alt="encodeText">
		</div>
		<h3>{{ subTitle }}</h3>
		<p />
		<p class="qr-url">
			{{ encodeText }}
		</p>
	</div>
</template>

<script setup>

import QRCode from 'qrcode'
import { Logger } from '../../../helpers/index.js'
import { onMounted, ref } from 'vue';

const props = defineProps({
	name: {
		type: String,
		default: '',
	},
	subTitle: {
		type: String,
		default: '',
	},
	description: {
		type: String,
		default: '',
	},
	encodeText: {
		type: String,
		default: '',
	},
})

const qrUri = ref({
	type: String,
	default: '',
})

/**
 * Generate QR code
 */
async function generateQr() {
	try {
		qrUri.value = await QRCode.toDataURL(props.encodeText)
	} catch (error) {
		Logger.error('Error on generating QR code', { error: error.message })
	}
}

onMounted(() => {
	generateQr()
})

</script>

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
