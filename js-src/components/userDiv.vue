/* global Vue, oc_userconfig */
<template>
	<div class="userRow">
	<div v-show="description" class="description">{{description}}</div>
		<div class="avatar">
			<img :src="avatarURL" :width="size" :height="size">
		</div>
		<div class="user">{{showDisplayName}}</div>
	</div>
</template>

<script>
	export default {
		props: {
			userId: {
				type: String,
				default: OC.getCurrentUser().uid
			},
			displayName: {
				type: String
			},
			size: {
				type: Number,
				default: 32
			},
			type: {
				type: String,
				default: 'user'
			},
			description: String
		},
		computed: {
			showDisplayName: function () {
					if (this.userId === OC.getCurrentUser().uid) {
						return OC.getCurrentUser().displayName
					} else {
						if (!this.displayName) {
							return this.userId;
						}
					}
				},
			avatarURL: function() {
				if (this.userId === OC.getCurrentUser().uid) {
					return OC.generateUrl(
						'/avatar/{user}/{size}?v={version}',
						{
							user: OC.getCurrentUser().uid,
							size: Math.ceil(this.size * window.devicePixelRatio),
							version: oc_userconfig.avatar.version
					})
				} else {
					return OC.generateUrl(
						'/avatar/{user}/{size}',
						{
							user: this.userId,
							size: Math.ceil(this.size * window.devicePixelRatio),
					})
					
				}
			}
		}	
	}
</script>
<style scoped>
	.userRow {
		display: flex;
		flex-direction: row;
		flex-grow: 0;
		align-items: center;
		margin-left: 0;
		margin-top: 0;
	}
	.description {
		opacity: 0.7;
		margin-right: 4px;
	}
	.avatar {
		height: 32px;
		width: 32px;
	}
	.user {
		margin-left: 8px;
		opacity: 0.5;
		flex-grow: 1;
	}
</style>
