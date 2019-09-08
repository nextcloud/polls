<!--
  - @copyright Copyright (c) 2018 René Gieling <github@dartcafe.de>
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
	<div v-if="visible" class="modal-dialog">
		<div class="modal-header">
			<h2>{{ title }}</h2>
		</div>
		<div class="modal-text">
			<p>{{ text }}</p>
		</div>
		<slot />
		<div class="modal-buttons">
			<button class="button" @click="hide">
				{{ buttonHideText }}
			</button>
			<button class="button primary" @click="confirm">
				{{ buttonConfirmText }}
			</button>
		</div>
	</div>
</template>

<script>
// we must import our Modal plugin instance
// because it contains reference to our Eventbus
import ModalDlg from './plugin.js'

export default {
	data() {
		return {
			visible: false,
			title: '',
			text: '',
			buttonHideText: 'Close',
			buttonConfirmText: 'OK',
			onConfirm: {}
		}
	},
	beforeMount() {
		// here we need to listen for emited events
		// we declared those events inside our plugin
		ModalDlg.EventBus.$on('show', (params) => {
			this.show(params)
		})
	},
	methods: {
		hide() {
			this.visible = false
		},
		confirm() {
			// we must check if this.onConfirm is function
			if (typeof this.onConfirm === 'function') {
				// run passed function and then close the modal
				this.onConfirm()
				this.hide()
			} else {
				// we only close the modal
				this.hide()
			}
		},
		show(params) {
			// making modal visible
			this.visible = true
			// setting texts
			this.title = params.title
			this.text = params.text
			this.buttonHideText = params.buttonHideText
			this.buttonConfirmText = params.buttonConfirmText
			// setting callback function
			this.onConfirm = params.onConfirm
		}
	}
}

</script>

<style scoped lang="scss">
.modal-dialog {
	display: flex;
	flex-direction: column;
	position: fixed;
	top: 50%;
	left: 50%;
	transform: translate(-50%, -50%);
	min-width: 300px;
	max-width: 500px;
	z-index: 1000;
	background-color: var(--color-main-background);
	box-shadow: 0 0 3px rgba(77, 77, 77, 0.5);
	padding: 20px;
}

.modal-buttons {
	display: flex;
	justify-content: space-between;
}

</style>
