<!--
  - @copyright Copyright (c) 2023 René Gieling <github@dartcafe.de>
  -
  - @author René Gieling <github@dartcafe.de>
  -
  - @license GNU AGPL version 3 or any later version
  -
  - This program is free software: you can redistribute it and/or modify
  - it under the terms of the GNU Affero General Public License as
  - published by the Free Software Foundation, either version 3 of the
  - License, or (at your option) any later version.
  -
  - This program is distributed in the hope that it will be useful,
  - but WITHOUT ANY WARRANTY; without even the implied warranty of
  - MERCHANTABILITY or FITNESS FOR A PARTICULAR PURPOSE.  See the
  - GNU Affero General Public License for more details.
  -
  - You should have received a copy of the GNU Affero General Public License
  - along with this program.  If not, see <http://www.gnu.org/licenses/>.
  -
  -->

<template>
	<div class="qr-code">
		<h2>{{ title }}</h2>
		<slot name="description">
			{{ description }}
		</slot>
		<div class="canvas">
			<img :src="qrUri" :alt="encodeText">
		</div>
		<h3>{{ subTitle }}</h3>
		<p />
		<div class="modal__buttons noprint">
			<NcButton type="primary" @click="$emit('close')">
				{{ t('polls', 'Close') }}
			</NcButton>
		</div>
		<p class="qr-url">
			{{ encodeText }}
		</p>
	</div>
</template>

<script>

import QRCode from 'qrcode'
import { NcButton } from '@nextcloud/vue'

export default {
	name: 'QrModal',

	components: {
		NcButton,
	},

	props: {
		title: {
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
	},

	data() {
		return {
			qrUri: {
				type: String,
				default: '',
			},
		}
	},
	created() {
		this.generateQr()
	},
	methods: {
		async generateQr() {
			try {
				this.qrUri = await QRCode.toDataURL(this.encodeText)
			} catch (err) {
				console.error(err)
			}
		},
	},
}

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
