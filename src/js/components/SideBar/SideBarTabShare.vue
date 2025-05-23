<!--
  - SPDX-FileCopyrightText: 2018 Nextcloud contributors
  - SPDX-License-Identifier: AGPL-3.0-or-later
-->

<template>
	<div class="sidebar-share">
		<SharesListUnsent v-if="appPermissions.shareCreate" class="shares unsent" />
		<SharesList class="shares effective" />
		<SharesListLocked v-if="appPermissions.shareCreate" class="shares" />
	</div>
</template>

<script>
import { mapState } from 'vuex'
import SharesList from '../Shares/SharesList.vue'
import SharesListUnsent from '../Shares/SharesListUnsent.vue'
import SharesListLocked from '../Shares/SharesListLocked.vue'

export default {
	name: 'SideBarTabShare',

	components: {
		SharesList,
		SharesListUnsent,
		SharesListLocked,
		},
	computed: {
		...mapState({
			appPermissions: (state) => state.acl.appPermissions,
		}),
	},
}
</script>

<style lang="scss">
	.sidebar-share {
		display: flex;
		flex-direction: column;
	}

	.shares-list {
		display: flex;
		flex-flow: column;
		justify-content: flex-start;
		padding-top: 8px;

		> li {
			display: flex;
			align-items: stretch;
			margin: 4px 0;
		}
	}

	.share-item {
		display: flex;
		flex: 1;
		align-items: center;
		max-width: 100%;
	}

	.share-item__description {
		flex: 1;
		min-width: 50px;
		color: var(--color-text-maxcontrast);
		padding-inline-start: 8px;
		white-space: nowrap;
		overflow: hidden;
		text-overflow: ellipsis;
	}
</style>
