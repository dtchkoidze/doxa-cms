<template>
    <slot name="label"></slot>
    <textarea :value="value" @input="updateValue($event.target.value)" class="w-full form-input">
    </textarea>
    <slot name="error"></slot>
</template>

<script>
export default {
    props: ['params', 'value'],
    emits: ['updateValue'],
    methods: {
        updateValue(new_value) {
            console.log("Emitting update-value:", new_value);
            this.$emit('updateValue', new_value);
        },
    }
};
</script>
