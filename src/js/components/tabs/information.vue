<template>
	<div>
		<user-div :user-id="event.owner" :description="t('polls', 'Owner')" />
		<h3> {{ t('polls', 'Title') }} </h3>
		<div>{{ event.title }}</div>
		<h3> {{ t('polls', 'Description') }} </h3>
		<div>{{ event.description }}</div>
		<h3> {{ t('polls', 'Access') }} </h3>
		<div>{{ event.access }}</div>
		<div>{{ event.hash }}</div>
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
			event: state => state.poll.event
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
