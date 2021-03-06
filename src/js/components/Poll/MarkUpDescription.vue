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
	<!-- eslint-disable-next-line vue/no-v-html -->
	<div class="markup-description" v-html="markedDescription">
		{{ markedDescription }}
	</div>
</template>

<script>
import marked from 'marked'
import DOMPurify from 'dompurify'
import { mapState } from 'vuex'

export default {
	name: 'MarkUpDescription',

	data() {
		return {
			delay: 50,
			isLoading: false,
			manualViewDatePoll: '',
			manualViewTextPoll: '',
			ranked: false,
			voteSaved: false,
		}
	},

	computed: {
		...mapState({
			description: state => state.poll.description,
			descriptionSafe: state => state.poll.descriptionSafe,
		}),

		markedDescription() {
			if (this.description) {
				return DOMPurify.sanitize(marked(this.description))
			} else {
				return t('polls', 'No description provided')
			}
		},
	},
}

</script>

<style lang="scss">
.markup-description {
	white-space: pre;
	p {
		white-space: pre-wrap;
		margin: 16px 0;
	}
	a {
		font-weight: bold;
		text-decoration: underline;
	}
	h1 {
		font-size: revert;
	}
	ul, ol {
		list-style: revert;
		margin-left: 16px;
	}
	input[type='checkbox'] {
		min-height: revert;
		&:disabled {
			opacity: 1;
		}
	}
	table {
		border-spacing: 2px;
	}
	thead {
		background-color: var(--color-background-darker);
		color: var(--color-text-light);
	}

	td, th {
		padding: 1px 4px;
	}
}
</style>
