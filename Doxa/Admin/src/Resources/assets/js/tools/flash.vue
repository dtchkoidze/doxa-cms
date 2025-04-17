<template>

    <transition-group v-if="flashes.length > 0"
        tag='div'
        name="flash-group"
        enter-from-class="ltr:translate-x-full rtl:-translate-x-full"
        enter-active-class="transition duration-200 ease-in-out transform"
        enter-to-class="ltr:translate-x-0 rtl:-translate-x-0"
        leave-from-class="ltr:translate-x-0 rtl:-translate-x-0"
        leave-active-class="transition duration-200 ease-in-out transform"
        leave-to-class="ltr:translate-x-full rtl:-translate-x-full"
        class='fixed top-5 z-[10002] grid justify-items-end gap-2.5 right-5 rtl:left-5'
    >

        <template 
            v-for="flash in flashes"
            :key='flash.uid'
            :flash="flash"
            
        >
            <Toast2 :type="flash.type">
                {{ flash.message }}
            </Toast2>
        </template>



    </transition-group>
</template>

<script>

import Toast2 from "../components/toast2.vue";

export default {
    data() {
        return {
            uid: 0,

            flashes: [],
            
            iconClasses: {
                success: 'fa-circle-check',
                error: 'fa-circle-xmark',
                warning: 'fa-circle-exclamation',
                info: 'fa-circle-exclamation',
            },
            typeStyles: {
                success: {
                    container: 'background: #059669',
                    message: 'color: #FFFFFF',
                    icon: 'color: #ffffff',
                    closeIcon: 'color: yellow'
                },
                error: {
                    container: 'background: #EF4444',
                    message: 'color: #FFFFFF',
                    icon: 'color: #ffffff',
                    closeIcon: 'color: yellow'
                },
                warning: {
                    container: 'background: #FACC15',
                    message: 'color: #1F2937',
                    icon: 'color: #1F2937'
                },
                info: {
                    container: 'background: #0284C7',
                    message: 'color: #FFFFFF',
                    icon: 'color: #0284C7'
                },
            },
        };
    },
    components: {
        Toast2,
    },
    created() {
        this.registerGlobalEvents();
    },

    methods: {
        add(flash) {
            flash.uid = this.uid++;
            this.flashes.push(flash);
            var self = this;
            setTimeout(function() {
                self.remove(flash)
            }, 5000)
        },

        remove(flash) {
            let index = this.flashes.indexOf(flash);
            this.flashes.splice(index, 1);
        },

        registerGlobalEvents() {
            this.$emitter.on('add-flash', this.add);
        },
    }       
};
</script>

