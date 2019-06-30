<template>
	<div>
		<ul name="comments">
			<li v-for="(comment) in comments" :key="comment.id">
				<div class="comment-item">
					<user-div :user-id="comment.userId" />
					<div class="date">
						{{ comment.date }}
					</div>
				</div>
				<div class="message wordwrap comment-content">
					{{ comment.comment}}
				</div>
			</li>

		</ul>
	</div>
</template>

<script>
import moment from 'moment'
import { mapState } from 'vuex'

export default {
	name: 'Create',
	computed: {
		...mapState({
			comments: state => state.poll.comments,
		})
	},
	methods: {
		realtiveDate(date) {
			return moment(date).fromNow()
		}
	}
}
</script>

<style scoped lang="scss">

ul {
	&> li {
		margin-bottom: 30px;
		&> .comment-item {
			display: flex;
			align-items: center;

			&> .date {
				right: 0;
				top: 5px;
				opacity: 0.5;
			}
		}
		&> .message {
			margin-left: 44px;
			flex-grow: 1;
			flex-shrink: 1;
		}
	}
}
</style>
