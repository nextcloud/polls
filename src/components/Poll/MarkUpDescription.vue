<!--
  - SPDX-FileCopyrightText: 2018 Nextcloud contributors
  - SPDX-License-Identifier: AGPL-3.0-or-later
-->

<template>
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
