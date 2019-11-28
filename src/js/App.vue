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
	<div id="app-polls">
		<Navigation v-if="!isPublic" />
		<router-view />
	</div>
</template>

<script>
import Navigation from './components/navigation/navigation'

export default {
	name: 'App',
	components: {
		Navigation
	},

	computed: {
		isPublic() {
			return (this.$route.name === 'publicVote')
		}
	}
}

</script>

<style  lang="scss">

.list-enter-active,
.list-leave-active {
    transition: all 0.5s ease;
}

.list-enter,
.list-leave-to {
    opacity: 0;
}

.list-move {
    transition: transform 0.5s;
}

.fade-leave-active {
  transition: opacity 2.5s;
}
.fade-enter, .fade-leave-to {
  opacity: 0;
}

#app-polls {
	width: 100%;
	// display: flex;
}

#app-content {
    display: flex;
	width: auto;

	input.hasTimepicker {
        width: 75px;
    }

	.label {
		border: solid 1px;
		border-radius: var(--border-radius);
		padding: 1px 4px;
		margin: 0 4px;
		font-size: 60%;
		text-align: center;
		&.error {
			border-color: var(--color-error);
			background-color: var(--color-error);
			color: var(--color-primary-text);
		}
		&.success {
			border-color: var(--color-success);
			background-color: var(--color-success);
			color: var(--color-primary-text);
		}
	}
}
</style>
