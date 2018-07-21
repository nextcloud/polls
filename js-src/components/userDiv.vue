/* global Vue, oc_userconfig */
<template>
	<div class="userRow">
	<div v-show="description" class="description">{{description}}</div>
		<div class="avatar">
			<img :src="avatarURL" :width="size" :height="size">
		</div>
		<div v-show="nothidden" class="avatar imageplaceholderseed" :data-username="userId" :data-displayname="computedDisplayName" data-seed="Poll users 1">
			{{ computedDisplayName.toUpperCase().substr(0,1) }}
		</div>
		<div class="user">{{ computedDisplayName }}</div>
	</div>

	<div class="avatar imageplaceholderseed"</div>
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

		data: function () {
			return {
				nothidden: false,
			}
		},

		computed: {
			computedDisplayName: function () {
				var value = this.displayName;
				
				if (this.userId === OC.getCurrentUser().uid) {
					value = OC.getCurrentUser().displayName;
				} else {
					if (!this.displayName) {
						value = this.userId;
					}
				}
				if (this.type === 'group') {
					value = value + ' (' + t('polls','Group') +')';
				}
				return value;
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
		flex-grow: 1;
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
	.imageplaceholderseed {
		height: 32px; 
		width: 32px; 
		background-color: rgb(185, 185, 185); 
		color: rgb(255, 255, 255); 
		font-weight: normal; 
		text-align: center; 
		line-height: 32px; 
		font-size: 17.6px;
	}
</style>
