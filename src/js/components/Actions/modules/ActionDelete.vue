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
	<div class="">
		<NcButton :name="computedTitle"
			type="tertiary"
			:aria-label="computedTitle">
			<template #icon>
				<RestoreIcon v-if="restore"
					:size="iconSize"
					@click="restoreItem()" />
				<UndoIcon v-else-if="deleteTimeout"
					:size="iconSize"
					@click="cancelDelete()" />
				<LockIcon v-else-if="lock"
					:size="iconSize"
					@click="deleteItem()" />
				<DeleteIcon v-else
					:size="iconSize"
					@click="deleteItem()" />
			</template>
		</NcButton>
	</div>
</template>

<script>
import { NcButton } from '@nextcloud/vue'
import DeleteIcon from 'vue-material-design-icons/Delete.vue'
import RestoreIcon from 'vue-material-design-icons/Recycle.vue'
import LockIcon from 'vue-material-design-icons/Lock.vue'
import UndoIcon from 'vue-material-design-icons/ArrowULeftTop.vue'

export default {
	name: 'ActionDelete',
	components: {
		DeleteIcon,
		LockIcon,
		RestoreIcon,
		UndoIcon,
		NcButton,
	},

	props: {
		// timeout in seconds
		timeout: {
			type: Number,
			default: 4,
		},
		name: {
			type: String,
			default: t('polls', 'Delete'),
		},
		iconSize: {
			type: Number,
			default: 20,
		},
		restore: {
			type: Boolean,
			default: false,
		},
		lock: {
			type: Boolean,
			default: false,
		},
	},

	data() {
		return {
			deleteInterval: null,
			deleteTimeout: null,
			countdown: 4, // seconds
		}
	},

	computed: {
		countdownTitle() {
			return n('polls', 'Deleting in {countdown} second', 'Deleting in {countdown} seconds', this.countdown, { countdown: this.countdown })
		},
		computedTitle() {
			return this.deleteTimeout ? this.countdownTitle : this.name
		},
	},

	methods: {
		deleteItem() {
			// delete immediately
			if (this.timeout === 0) {
				this.$emit('delete')
				return
			}

			this.countDown = this.timeout
			this.deleteInterval = setInterval(() => {
				this.countdown -= 1
				if (this.countdown < 0) {
					this.countdown = 0
				}
			}, 1000)
			this.deleteTimeout = setTimeout(() => {
				this.$emit('delete')
				this.deleteTimeout = null
				this.deleteInterval = null
				this.countdown = this.timeout
			}, this.timeout * 1000)
		},

		cancelDelete() {
			clearTimeout(this.deleteTimeout)
			clearInterval(this.deleteInterval)
			this.deleteTimeout = null
			this.deleteInterval = null
			this.countdown = this.timeout
		},
		restoreItem() {
			clearTimeout(this.deleteTimeout)
			clearInterval(this.deleteInterval)
			this.deleteTimeout = null
			this.deleteInterval = null
			this.$emit('restore')
		},
	},
}
</script>

<style lang ="scss">
.material-design-icon {

	&.delete-icon,
	&.undo-icon {
		cursor: pointer;
	}

	&.delete-icon:hover {
		color: var(--color-error-hover);
	}
	&.arrow-u-left-top-icon {
		/* force the undo icon always to be visible */
		visibility: visible !important;
	}
}
</style>
