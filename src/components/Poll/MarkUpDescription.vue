<!--
  - SPDX-FileCopyrightText: 2018 Nextcloud contributors
  - SPDX-License-Identifier: AGPL-3.0-or-later
-->

<script setup lang="ts">
	import { computed } from 'vue'
	import { marked } from 'marked'
	import { gfmHeadingId } from 'marked-gfm-heading-id'
	import DOMPurify from 'dompurify'
	import { usePollStore } from '../../stores/poll.ts'

	const pollStore = usePollStore()

	const markedPrefix = {
		prefix: 'desc-',
	}

	const markedDescription = computed(() => {
		marked.use(gfmHeadingId(markedPrefix))
		return DOMPurify.sanitize(marked.parse(pollStore.descriptionSafe).toString())
	})

</script>

<template>
	<!-- eslint-disable-next-line vue/no-v-html -->
	<div class="markup-description" v-html="markedDescription" />
</template>

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
