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
	<div class="description" v-html="markedDescription">
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

<style lang="scss" scoped>
.description {
	white-space: pre-wrap;
}

.description a {
	font-weight: bold;
}
</style>
