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
	<div>
		<div class="config-box">
			<label class="title icon-toggle-filelist">
				{{ t('polls', 'Add a new text option') }}
			</label>

			<InputDiv v-model="newPollText" :placeholder="t('polls', 'Enter option text')"
				@input="addOption()" />
		</div>

		<ul class="config-box poll-table">
			<label class="title icon-calendar">
				{{ t('polls', 'Available Options') }}
			</label>
			<PollItemText v-for="(option) in sortedOptions" :key="option.id" :option="option"
				@remove="removeOption(option)" />
		</ul>
	</div>
</template>

<script>
import { mapGetters, mapState } from 'vuex'
import PollItemText from '../Base/PollItemText'
import InputDiv from '../Base/InputDiv'

export default {
	name: 'SideBarTabOptionsText',

	components: {
		InputDiv,
		PollItemText
	},

	data() {
		return {
			newPollText: ''
		}
	},

	computed: {
		...mapState({
			options: state => state.options
		}),

		...mapGetters(['sortedOptions'])
	},

	methods: {

		addOption() {
			if (this.newPollText) {
				this.$store.dispatch('addOptionAsync', {
					pollOptionText: this.newPollText
				})
					.then(() => {
						this.newPollText = ''
					})
			}
		},

		removeOption(option) {
			this.$store.dispatch('removeOptionAsync', {
				option: option
			})
		}

	}

}
</script>

<style lang="scss">
	.config-box {

		&.poll-table > li {
			border-bottom-color: rgb(72, 72, 72);
			margin-left: 18px;
		}

	}
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

	.poll-table {
		> li {
			display: flex;
			align-items: center;
			padding-left: 8px;
			padding-right: 8px;
			line-height: 2em;
			min-height: 4em;
			border-bottom: 1px solid var(--color-border);
			overflow: hidden;
			white-space: nowrap;

			&:active,
			&:hover {
				transition: var(--background-dark) 0.3s ease;
				background-color: var(--color-background-dark); //$hover-color;
			}

			> div {
				display: flex;
				flex: 1;
				font-size: 1.2em;
				opacity: 0.7;
				white-space: normal;
				padding-right: 4px;
				&.avatar {
					flex: 0;
				}
			}

			> div:nth-last-child(1) {
				justify-content: center;
				flex: 0 0;
			}
		}
	}
</style>
