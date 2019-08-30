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
		<user-div :user-id="event.owner" :description="t('polls', 'Owner')" />
		<div>{{ accessType }}</div>
		<h3> {{ t('polls', 'Created') }} </h3>
		<div>{{ timeSpanCreated }}</div>
		<h3> {{ t('polls', 'Expires') }} </h3>
		<div>{{ timeSpanExpiration }}</div>
		<div>{{ countCommentsHint }}</div>
	</div>
</template>

<script>
import { mapState, mapGetters } from 'vuex'

export default {
	name: 'InformationTab',
	computed:	{
		...mapState({
			event: state => state.event
		}),

		...mapGetters([
			'accessType',
			'countComments',
			'timeSpanCreated',
			'timeSpanExpiration'
		]),

		countCommentsHint: function() {
			return n('polls', 'There is %n comment', 'There are %n comments', this.countComments)
		}
	}
}

</script>
