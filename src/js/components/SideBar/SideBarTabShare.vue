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
	<div class="sidebar-share">
		<ConfigPublicPollsEmail />
		<SharesUnsent class="shares unsent" />
		<SharesEffective class="shares effective" />
	</div>
</template>

<script>
import SharesEffective from '../Shares/SharesEffective'
import SharesUnsent from '../Shares/SharesUnsent'
import ConfigPublicPollsEmail from '../Configuration/ConfigPublicPollsEmail'

export default {
	name: 'SideBarTabShare',

	components: {
		SharesEffective,
		SharesUnsent,
		ConfigPublicPollsEmail,
	},

}
</script>

<style lang="scss">
	.sidebar-share {
		display: flex;
		flex-direction: column;
	}

	.shared-list {
		display: flex;
		flex-flow: column;
		justify-content: flex-start;
		padding-top: 8px;
		max-height: 450px;
		overflow: auto;

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
		padding-left: 8px;
		white-space: nowrap;
		overflow: hidden;
		text-overflow: ellipsis;
	}
</style>
