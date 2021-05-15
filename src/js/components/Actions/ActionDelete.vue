<!--
  - @copyright Copyright (c) 2021 René Gieling <github@dartcafe.de>
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
	<Actions>
		<ActionButton v-if="deleteTimeout" icon="icon-history" @click="cancelDelete()">
			{{ n('polls', 'Deleting in {countdown} second', 'Deleting in {countdown} seconds', countdown, { countdown }) }}
		</ActionButton>
		<ActionButton v-else icon="icon-delete" @click="deleteItem()">
			{{ deleteCaption }}
		</ActionButton>
	</Actions>
</template>

<script>
import { Actions, ActionButton } from '@nextcloud/vue'

export default {
	name: 'ActionDelete',
	components: {
		Actions,
		ActionButton,
	},

	props: {
		useTimeOut: {
			type: Number,
			default: 7,
		},
		deleteCaption: {
			type: String,
			default: t('polls', 'Delete'),
		},
	},

	data() {
		return {
			deleteInterval: null,
			deleteTimeout: null,
			countdown: 7,
		}
	},

	methods: {
		deleteItem() {
			this.countDown = this.useTimeOut
			this.deleteInterval = setInterval(() => {
				this.countdown -= 1
				if (this.countdown < 0) {
					this.countdown = 0
				}
			}, 1000)
			this.deleteTimeout = setTimeout(async() => {
				this.$emit('delete')
				this.deleteTimeout = null
				this.deleteInterval = null
				this.countdown = this.useTimeOut
			}, this.useTimeOut * 1000)
		},

		cancelDelete() {
			clearTimeout(this.deleteTimeout)
			clearInterval(this.deleteInterval)
			this.deleteTimeout = null
			this.deleteInterval = null
			this.countdown = this.useTimeOut
		},
	},
}
</script>
