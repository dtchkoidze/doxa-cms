<template>
    <div class="flex flex-row">
        <div class="relative mr-2" v-if="channelSelectorMode() == 'selector'">
            <dropdown>
                <template #toggle>
                    <span>{{ channels[current_channel_id].name }}</span>
                </template>
                <template #options>
                    <button v-for="channel in channels" :key="channel.id"
                        class="flex items-center px-3 py-1 cursor-pointer hover:bg-gray-50 hover:dark:bg-gray-700/20"
                        :class="channel.id === current_channel_id && 'text-violet-500'"
                        @click="setCurrentChannel(channel)"
                    >
                        <svg class="mr-2 fill-current shrink-0 text-violet-500"
                            :class="channel.id !== current_channel_id && 'invisible'" width="12" height="9" viewBox="0 0 12 9">
                            <path
                                d="M10.28.28L3.989 6.575 1.695 4.28A1 1 0 00.28 5.695l3 3a1 1 0 001.414 0l7-7A1 1 0 0010.28.28z" />
                        </svg>
                        <span>{{ channel.name }}</span>
                    </button>
                </template>
            </dropdown>
        </div>
        
        <div class="relative" v-if="localeSelectorMode() == 'selector'">

            <dropdown>
                <template #toggle>
                    <span>{{ locales[current_locale_id].code.toUpperCase() }}</span>
                </template>
                <template #options>
                    <button v-for="locale in locales" :key="locale.id"
                        class="flex items-center px-3 py-1 cursor-pointer hover:bg-gray-50 hover:dark:bg-gray-700/20"
                        :class="locale.id === current_locale_id && 'text-violet-500'"
                        @click="setCurrentLocale(locale)"
                    >
                        <svg class="mr-2 fill-current shrink-0 text-violet-500"
                            :class="locale.id !== current_locale_id && 'invisible'" width="12" height="9" viewBox="0 0 12 9">
                            <path
                                d="M10.28.28L3.989 6.575 1.695 4.28A1 1 0 00.28 5.695l3 3a1 1 0 001.414 0l7-7A1 1 0 0010.28.28z" />
                        </svg>
                        <span>{{ locale.code.toUpperCase() }}</span>
                    </button>
                </template>
            </dropdown>
        </div> 

        <div v-if="localeSelectorMode() == 'label'">
            <div
                class="flex w-full max-w-max cursor-pointer appearance-none text-d4 items-end justify-between gap-x-2 rounded-md border bg-white px-2.5 py-1.5 text-center leading-6 text-gray-600 transition-all marker:shadow hover:border-gray-400 focus:border-gray-400 dark:border-gray-800 dark:bg-gray-900 dark:text-gray-300 dark:hover:border-gray-400 dark:focus:border-gray-400"
            >
                <span class="test-lh" v-text="locales[current_locale_id].code.toUpperCase()"></span>
            </div>
        </div>   
    </div>
</template>

<script>

//import dropdown from "./dropdown.vue";
import dropdown from "../components/dropdown/DropdownClassic.vue";

export default {
    data() {
        return {
            ch_lo: this._ch_lo,
            current_channel_id: this._current_channel_id,
            current_locale_id: this._current_locale_id,
            channels: {},
            locales: {},
        }
    },
    components: {
        dropdown,  
    },
    props: ['_set', 'callback', '_ch_lo', '_current_channel_id', '_current_locale_id'],
    mounted() {
        //console.log('mounted Chlo _ch_lo: ', this._ch_lo);
        //console.log('created Chlo current_channel_id: ', this.current_channel_id, 'current_locale_id: ', this.current_locale_id);

        if(this.ch_lo.channels){
            this.channels = this.ch_lo.channels;
        }

        if(this.ch_lo.locales){
            this.locales = this.ch_lo.locales;
        }

        // console.log('this.channels: ', this.channels);
        // console.log('this.locales: ', this.locales);
        // console.log('this.current_channel_id: ', this.current_channel_id);
        // console.log('this.current_locale_id: ', this.current_locale_id);
    },
    methods: {
        
        /**
         * Returns the mode for the channel selector based on the number of channels available.
         *
         * @return {string} 'selector' if there are multiple channels, otherwise an empty string
         */
        channelSelectorMode(){
            if( Object.keys(this.channels).length ){
                return 'selector';
            }
            return '';
        },
        /**
         * Returns the mode for the locale selector based on the number of locales available.
         *
         * @return {string} 'selector' if there are multiple locales, otherwise an empty string
         */
        localeSelectorMode(){
            //console.log(this.ch_lo);
            if( Object.keys(this.channels).length){
                this.locales = this.channels[this.current_channel_id].locales;
            } 

            var ln = Object.keys(this.locales).length;
            if( ln > 1 ){
                return 'selector';
            } else {
                if( Object.keys(this.channels).length ){
                    return 'label';
                }
            }
            return '';
        },

        setCurrentChannel(channel) {
            if(channel.id == this.current_channel_id){
                return false;
            }
            this.current_channel_id = channel.id;
            var locales = this.channels[this.current_channel_id].locales;
            if(!locales[this.current_locale_id]){
                this.current_locale_id = Object.values(locales)[0].id;
            }
            if(this.callback){
                this.callback(this.current_channel_id, this.current_locale_id);
            }
            this.$emitter.emit('close-dropdown', {});
        },
        setCurrentLocale(locale) {
            console.log(this.$children);
            if(locale.id == this.current_locale_id){
                return false;
            }
            this.current_locale_id = locale.id;
            if(this.callback){
                this.callback(this.current_channel_id, this.current_locale_id);
            }
            //this.$emit('closeDropdownOnSelect');
            this.$emitter.emit('close-dropdown', {});
        },
        setChLo(obj) {
            this.setCurrentChannel(obj.channel);
            this.setCurrentLocale(obj.locale);
        },
        registerGlobalEvents() {
            this.$emitter.on('switch-chlo', this.setChLo);
        },
        
    },            
};
</script>

