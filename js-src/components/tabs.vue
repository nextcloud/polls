<template>
    <div class="tab-container" :data-tab-uuid="uuid">
        <ul class="tab-titles">
            <li
                v-for="(tab, name) in tabs"
                :key="name"
                :data-tab="name"
                class="tab-title"
                :class="{ active: isCurrentTab(name) }"
                :style="tabStyle"
                @click="setCurrentTab(name)">
                {{tab}}
            </li>
        </ul>
        <div class="tab-contents">
            <div v-for="(tab, name) in tabs" :data-tab="name" class="tab-content" v-bind:class="{ active: isCurrentTab(name) }">
                <slot :name="name"></slot>
            </div>
        </div>
    </div>
</template>

<script>

    export default {
        props: {
            tabs: {
                type: Object
            },
            uuid: {
                type: String
            }
        },

        data() {
            return {
                tab: Object.keys(this.tabs)[0]
            }
        },

        computed: {
            tabStyle() {
                if (OCA.Theming) {
                    return {
                        'border-color': OCA.Theming.color
                    };
                }

                return {};
            }
        },

        methods: {
            isCurrentTab(tab) {
                return tab === this.tab
            },
            setCurrentTab(tab) {
                this.tab = tab;
            }
        },
        watch  : {
            uuid: function() {
                this.tab = Object.keys(this.tabs)[0];
            }
        }
    }
</script>


