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
	<ConfigBox v-if="!closed" :title="t('polls', 'Add a new text option')" icon-class="icon-add">
		<InputDiv v-model="newPollText" :placeholder="t('polls', 'Enter option text')"
			@submit="addOption()" />
	</ConfigBox>
</template>

<script>
import { mapGetters } from 'vuex'
import ConfigBox from '../Base/ConfigBox'
import InputDiv from '../Base/InputDiv'

export default {
	name: 'OptionsTextAdd',

	components: {
		ConfigBox,
		InputDiv,
	},

	data() {
		return {
			newPollText: '',
		}
	},

	computed: {
		...mapGetters({
			closed: 'poll/closed',
		}),

	},

	methods: {
		addOption() {
			if (this.newPollText) {
				this.$store.dispatch('poll/options/add', {
					pollOptionText: this.newPollText,
				})
					.then(() => {
						this.newPollText = ''
					})
			}
		},
	},
}
</script>

<style lang="scss" scoped>
	.optionAdd {
		display: flex;
	}

	.newOption {
		margin-left: 40px;
		flex: 1;
		&:empty:before {
			color: grey;
		}
	}

	.submit-option {
		width: 30px;
		background-color: transparent;
		border: none;
		opacity: 0.3;
		cursor: pointer;
	}

</style>
