<!--
  - SPDX-FileCopyrightText: 2018 Nextcloud contributors
  - SPDX-License-Identifier: AGPL-3.0-or-later
-->

<template>
	<NcNoteCard v-bind="$attrs">
		<div class="card-content">
			<div class="left-card-side">
				<slot name="default" />
			</div>
			<div class="right-card-side">
				<slot name="button" />
			</div>
		</div>
	</NcNoteCard>
</template>

<script>
import { NcNoteCard } from '@nextcloud/vue'

export default {
	name: 'CardDiv',
	components: {
		NcNoteCard,
	},
}

</script>

<style lang="scss">
	.left-card-side {
		flex: 1 0 180px;
		ul {
			list-style: initial;
		}
	}

	.right-card-side {
		flex: 0;
		padding-inline-end: 8px;
	}

	.notecard .card-content {
		display: flex;
		flex-wrap: wrap;
		align-items: center;
		flex: 1;
		column-gap: 8px;
	}

</style>
