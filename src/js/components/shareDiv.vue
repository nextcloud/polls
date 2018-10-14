<template>
	<div>
		<h2> {{ t('polls', 'Share with') }}</h2>
		<multiselect 
			v-model="shares" 
			:options="users" 
			:option-height=32
			:multiple="true" 
			:close-on-select="false" 
			:clear-on-select="false" 
			:preserve-search="true" 
			label="displayName" 
			track-by="id" 
			:preselect-first="true"
			:placeholder="placeholder">
			<template slot="selection" slot-scope="{ values, search, isOpen }">
				<span class="multiselect__single" v-if="values.length &amp;&amp; !isOpen">
					{{ values.length }} users selected
				</span>
			</template>
			<template slot="option" slot-scope="props">
				<div class="option__desc">
					<user-div :user-id="props.option.id" :display-name="props.option.displayName" :type="props.option.type"></user-div>
				</div>
			</template>
		</multiselect>
		
		<transition-group tag="ul" v-bind:css="false" class="shared-list">
			<li v-for="(item, index) in sortedShares" 
				v-bind:key="item.displayName" 
				v-bind:data-index="index">
				<user-div :user-id="item.id" :display-name="item.displayName" :type="item.type" only-avatar="true"></user-div>
			</li>
		</transition-group>
	</div>
</template>

<script>
	import axios from 'axios';
	import { Multiselect } from 'nextcloud-vue';

	export default {
		components: {
			Multiselect
		},
		
		props: ['placeholder', 'activeShares'],
		
		data: function () {
			return {
				shares: [],
				query: '',
				users: [],
				siteUsersListOptions: {
					getUsers: true,
					getGroups: true,
				}
			}
		},
		
		created: function() {
			this.loadSiteUsers();
		},

		computed: {
			sortedShares: function() {
				return this.activeShares.sort(this.sortByDisplayname)
			}
		},
		
		methods: {
			removeShare: function (index, item){
				this.$emit('remove-share', item);
				this.users.push(item);
			},
			
			loadSiteUsers: function () {
				axios.post(OC.generateUrl('apps/polls/get/siteusers'), this.siteUsersListOptions)
				.then((response) => {
					this.users = response.data.siteusers;
				}, (error) => {
					console.log(error.response);
				});
			},
			
			sortByDisplayname: function (a, b) {
					if (a.displayName.toLowerCase() < b.displayName.toLowerCase()) return -1;
					if (a.displayName.toLowerCase() > b.displayName.toLowerCase()) return 1;
					return 0;
			}
			
		},
		watch: {
			activeShares(value) {
			this.shares = value.slice(0);
		}
}
	}
</script>

<style lang="scss">

	.shared-list {
		display: flex;
		padding-top: 8px;
		flex-grow: 0;
		justify-content: flex-start;
	
		> li {
			display: flex;
			flex-grow: 0;
		}
	}

	div, select {
		&.multiselect:not(.multiselect-vue), &.multiselect:not(.multiselect-vue) {
			max-width: unset;
		}
	}

	.multiselect {
		width: 100%;
		.multiselect__content-wrapper li > span {
			height: unset;
		}
		.option__desc {
			flex-grow: 1;
		}
	

		.multiselect__option--highlight {
			background: #41b883;
			outline: none;
			color: #fff;
			&::after {
				content: attr(data-select);
				background: #41b883;
				color: #fff;
			}
		}


		.multiselect__option--selected {
			&::after {
				content: attr(data-selected);
				color: silver;
			}
			&.multiselect__option--highlight {
				background: #ff6a6a;
				color: #fff;
				&::after {
					background: #ff6a6a;
					content: attr(data-deselect);
					color: #fff;
				}
			}
		}
	}



</style>
