<template>
    <span class="relative inline-block w-full">
        <slot></slot>
        <i class="absolute text-2xl text-gray-400 -translate-y-1/2 pointer-events-none icon-calendar top-1/2 ltr:right-2 rtl:left-2"></i>
    </span>
</template>

<script>

export default {
    props: {
        name: String,

        value: String,

        allowInput: {
            type: Boolean,
            default: false,
        },

        disable: Array,

        minDate: String,

        maxDate: String,
    },

    data: function() {
        return {
            datepicker: null
        };
    },

    mounted: function() {
        let options = this.setOptions();

        this.activate(options);
    },

    methods: {
        setOptions: function() {
            let self = this;

            return {
                allowInput: this.allowInput ?? true,
                disable: this.disable ?? [],
                minDate: this.minDate ?? '',
                maxDate: this.maxDate ?? '',
                format: "d.m.Y H:i",
                altFormat: "d.m.Y H:i",
                altInput: true,
                enableTime: true,
                // defaultDate: this.value ?? new Date().toISOString(),
                // altFormat: "Y-m-d",
                // dateFormat: "Y-m-d",
                // weekNumbers: true,

                onChange: function(selectedDates, dateStr, instance) {
                    self.$emit("onChange", dateStr);
                }
            };
        },

        activate: function(options) {
            let element = this.$el.getElementsByTagName("input")[0];

            this.datepicker = new Flatpickr(element, options);
        },

        clear: function() {
            this.datepicker.clear();
        }
    }
};

</script>