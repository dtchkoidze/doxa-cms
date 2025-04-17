<template>
    <slot name="label"></slot>
    <textarea 
        v-model="value"
        class="w-full form-input"
    >
    </textarea>
    <slot name="error"></slot>
</template>

<script>
export default {
    props: ['_value', 'params'],
    data() {
       return {
            value: this._value
       }
    },
    updated() {
        this.$parent.updateData(this.value);
    },
};
</script>

