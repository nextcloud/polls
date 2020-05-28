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
	<div class="create-dialog">
		<ConfigBox :title="t('polls', 'Title')" icon-class="icon-sound">
			<input id="pollTitle"
				ref="pollTitle"
				v-model="title"
				type="text"
				:placeholder="t('polls', 'Enter Title')"
				@keyup.enter="confirm">
		</ConfigBox>

		<ConfigBox :title="t('polls', 'Poll type')" icon-class="icon-checkmark">
			<input id="datePoll"
				v-model="type"
				value="datePoll"
				type="radio"
				class="radio">
			<label for="datePoll">
				{{ t('polls', 'Date poll') }}
			</label>
			<input id="textPoll"
				v-model="type"
				value="textPoll"
				type="radio"
				class="radio">
			<label for="textPoll">
				{{ t('polls', 'Text poll') }}
			</label>
		</ConfigBox>

		<div class="create-buttons">
			<button class="button" @click="cancel">
				{{ t('polls', 'Cancel') }}
			</button>
			<button :disabled="titleEmpty" class="button primary" @click="confirm">
				{{ t('polls', 'Apply') }}
			</button>
		</div>
	</div>
</template>

<script>
import { mapState, mapMutations, mapActions } from 'vuex'
import { emit } from '@nextcloud/event-bus'
import ConfigBox from '../Base/ConfigBox'

export default {
	name: 'CreateDlg',

	components: {
		ConfigBox,
	},

	data() {
		return {
			id: 0,
			type: 'datePoll',
			title: '',
		}
	},

	computed: {
		...mapState({
			poll: state => state.poll,
		}),

		titleEmpty() {
			return this.title === ''
		},
	},

	methods: {
		...mapMutations(['setPollProperty']),

		...mapActions(['resetPoll']),

		cancel() {
			this.title = ''
			this.type = 'datePoll'
			this.$emit('closeCreate')
		},

		confirm() {
			this.resetPoll()
			this.setPollProperty({ id: 0 })
			this.setPollProperty({ title: this.title })
			this.setPollProperty({ type: this.type })
			this.$store.dispatch('writePollPromise')
				.then(() => {
					emit('update-polls')
					this.cancel()
					OC.Notification.showTemporary(t('polls', 'Poll "%n" added', 1, this.poll.title), { type: 'success' })
					this.$router.push({ name: 'vote', params: { id: this.poll.id } })
				})
				.catch(() => {
					OC.Notification.showTemporary(t('polls', 'Error while creating Poll "%n"', 1, this.poll.title), { type: 'error' })
				})
		},

		setFocus() {
			this.$nextTick(() => {
				this.$refs.pollTitle.focus()
			})
		},
	},
}
</script>

<style lang="css" scoped>
.create-dialog {
	/* display: flex; */
	/* flex-direction: column; */
	background-color: var(--color-main-background);
	padding: 8px 20px;
}

/* #pollTitle {
	width: 100%;
} */

.create-buttons {
	display: flex;
	justify-content: space-between;
}
</style>
