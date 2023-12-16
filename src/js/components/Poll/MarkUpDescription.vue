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
	<!-- eslint-disable-next-line vue/no-v-html -->
	<div class="markup-description" v-html="markedDescription" />
</template>

<script>
import { marked } from 'marked'
import { gfmHeadingId } from 'marked-gfm-heading-id'
import DOMPurify from 'dompurify'
import { mapState } from 'vuex'

const markedPrefix = {
	prefix: 'desc-',
}

export default {
	name: 'MarkUpDescription',

	computed: {
		...mapState({
			description: (state) => state.poll.descriptionSafe,
		}),

		markedDescription() {
			marked.use(gfmHeadingId(markedPrefix))
			return DOMPurify.sanitize(marked.parse(this.description))
		},
	},
}

</script>

<style lang="scss">
.markup-description * {
	margin: revert;
	padding: revert;
	font-size: revert;
	text-decoration: revert;
	list-style: revert;
	opacity: revert;
	min-height: revert;
}

.markup-description {
	table {
		border-spacing: 2px;
	}

	thead {
		background-color: var(--color-background-darker);
		color: var(--color-main-text);
	}

	td, th {
		padding: 1px 4px;
	}
}
</style>
