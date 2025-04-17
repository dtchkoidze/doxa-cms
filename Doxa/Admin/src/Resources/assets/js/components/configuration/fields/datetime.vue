<template>
    <slot name="label"></slot>

    <flat-pickr
        class="form-input pl-9 dark:bg-gray-800 text-gray-600 hover:text-gray-800 dark:text-gray-300 dark:hover:text-gray-100 font-medium w-[15.5rem]"
        :config="config" :model-value="value" @update:model-value="updateValue"></flat-pickr>

    <slot name="error"></slot>
</template>

<script>
import flatPickr from 'vue-flatpickr-component';

export default {
    components: {
        flatPickr,
    },
    props: ['params', 'value'],
    emits: ['updateValue'],
    data() {
        return {
            config: {
                allowInput: true,
                disable: this.params?.disable ?? [],
                minDate: this.params?.minDate ?? '',
                maxDate: this.params?.maxDate ?? '',
                dateFormat: "d.m.Y H:i",
                altFormat: "d.m.Y H:i",
                altInput: true,
                enableTime: true,
                onChange: (selectedDates, dateStr) => {
                    this.updateValue(dateStr);
                }
            }
        };
    },
    methods: {
        updateValue(new_value) {
            this.$emit('updateValue', new_value);
        }
    }
};
</script>
