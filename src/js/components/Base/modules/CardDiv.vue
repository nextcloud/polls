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
		padding-right: 8px;
	}

	.notecard .card-content {
		display: flex;
		align-items: center;
		flex: 1;
		column-gap: 8px;
	}

</style>
