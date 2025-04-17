<template>
    <slot name="label"></slot>
    <!-- <input
        type="text"
        v-model="value"
        class="w-full rounded-md border px-3 py-2.5 text-sm text-gray-600 transition-all hover:border-gray-400 focus:border-gray-400 dark:border-gray-800 dark:bg-gray-900 dark:text-gray-300 dark:hover:border-gray-400 dark:focus:border-gray-400"
    > -->

    <flat-pickr class="form-input pl-9 dark:bg-gray-800 text-gray-600 hover:text-gray-800 dark:text-gray-300 dark:hover:text-gray-100 font-medium w-[15.5rem]" :config="config" v-model="date"></flat-pickr>

    <!-- <datepicker ::allow-input="false">
        <input
            :value="value"
            class="flex min-h-[39px] w-full rounded-md border px-3 py-2 text-sm text-gray-600 transition-all hover:border-gray-400 dark:border-gray-800 dark:bg-gray-900 dark:text-gray-300 dark:hover:border-gray-400"
            placeholder="Date"
            @change="onChange($event)"
        />
    </datepicker> -->

    <slot name="error"></slot>
</template>

<script>
// import datepicker from "../../components/datepicker/date.vue";

import flatPickr from 'vue-flatpickr-component'

export default {
    props: ["_value", "params"],
    components: {
        //datepicker,
        flatPickr,
    },
    data() {
        return {
            value: this._value || this.getCurrentDate(),
            config: {
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
                    //self.$emit("onChange", dateStr);
                    this.$parent.updateData(this.value);
                }
            }
        };
    },
    mounted() {
        this.$nextTick(() => {
            this.$parent.updateData(this.value);
        });
    },
    updated() {
        console.log("updated");
        this.$parent.updateData(this.value);
    },
    methods: {
        getCurrentDate() {
            const date = new Date();
            const year = date.getFullYear();
            const month = String(date.getMonth() + 1).padStart(2, "0");
            const day = String(date.getDate()).padStart(2, "0");
            const hour = String(date.getHours()).padStart(2, "0");
            const minute = String(date.getMinutes()).padStart(2, "0");
            const second = String(date.getSeconds()).padStart(2, "0");
            return `${year}-${month}-${day} ${hour}:${minute}:${second}`;
        },
        onChange(event) {
            this.value = event.target.value;
            //console.log(this.value);
            this.$parent.updateData(this.value);
        },
    },
};
</script>
