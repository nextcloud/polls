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
		<div class="wrapper group-master">
			<div class="wrapper group-1">
				<div class="wrapper group-1-1">
					<div class="flex-column name">
						{{ t('polls', 'Title') }}
					</div>
				</div>
				<div class="wrapper group-1-2">
					<div class="flex-column actions" />
				</div>
			</div>
			<div class="wrapper group-2">
				<div class="wrapper group-2-1">
					<div class="flex-column access">
						{{ t('polls', 'Access') }}
					</div>
					<div class="flex-column created">
						{{ t('polls', 'Created') }}
					</div>
				</div>
				<div class="flex-column owner">
					{{ t('polls', 'By') }}
				</div>
				<div class="wrapper group-2-2">
					<div class="flex-column expiry">
						{{ t('polls', 'Expires') }}
					</div>
				</div>
			</div>
		</div>
	</div>
</template>
