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

<template lang="html">
	<div class="createDlg" class="create-dialog">
		<h2>{{ t('polls', 'Create new poll') }}</h2>
		<input id="pollTitle" v-model="event.title" type="text" :placeholder="t('polls', 'Enter Title')">

		<div class="configBox">
			<label class="title icon-checkmark">
				{{ t('polls', 'Poll type') }}
			</label>
			<input id="datePoll" v-model="event.type" value="datePoll"
				:disabled="protect" type="radio" class="radio">
			<label for="datePoll">
				{{ t('polls', 'Event schedule') }}
			</label>
			<input id="textPoll" v-model="event.type" value="textPoll"
				:disabled="protect" type="radio" class="radio">
			<label for="textPoll">
				{{ t('polls', 'Text based') }}
			</label>
		</div>

		<div class="create-buttons">
			<button class="button" @click="cancel">
				{{ t('polls', 'Cancel') }}
			</button>
			<button :disabled="titleEmpty" class="button primary" @click="confirm" >
				{{ t('polls', 'Publish') }}
			</button>
		</div>

	</div>
</template>

<script>
export default {
	name: 'createDlg',

	data() {
		return {
			event: {
				title: '' ,
				description: '',
				type: 'datePoll',
				allowMaybe: false,
				isAnonymous: false,
				trueAnonymous: false,
				expiration: false,
				expirationDate: '',
				access: 'hidden'

			}
		}
	},

	computed: {
		titleEmpty() {
			return this.event.title === ''
		}
	},

	methods: {
		cancel() {
			this.event.title = ''
			this.event.type = 'datePoll'
			this.$emit('closeCreate')
		},

		confirm() {
			this.$store
				.dispatch('addEventPromise', { event: this.event })
				.then((response) => {
					OC.Notification.showTemporary(t('polls', 'Poll "%n" added', 1, this.event.title), { type: 'success' })
					this.$store.dispatch('loadPolls')
					this.$router.push({ name: 'vote', params: { id: response.data.id } })
					this.cancel()
				})
				.catch(() => {
					OC.Notification.showTemporary(t('polls', 'Error while creating Poll "%n"', 1, this.event.title), { type: 'error' })
				})
		}
	}

}
</script>

<style lang="css" scoped>
.create-dialog {
	display: flex;
	flex-direction: column;
	background-color: var(--color-main-background);
	padding: 20px;
}

#pollTitle {
	width: 100%;
}

.create-buttons {
	display: flex;
	justify-content: space-between;
}
</style>
