<template>
    <slot name="label"></slot>
    <input type="text" :value="value" @input="updateValue($event.target.value)" :id="params.field.key"
        class="w-full form-input">
    <div class="flex items-center justify-start">
        <button v-if="isGenerateTarget()" @click="generateUrlKey()"
            class="mt-2 px-3 py-1 text-sm text-white bg-blue-500 rounded-md hover:bg-blue-600">
            Generate URL Key
        </button>
    </div>
    <slot name="error"></slot>
</template>

<script>
import slugify from 'slugify';

export default {
    props: ['params', 'value'],
    emits: ['updateValue'],
    methods: {
        updateValue(new_value) {
            console.log("Emitting updateValue:", new_value);
            this.$emit('updateValue', new_value);
        },

        isGenerateTarget() {
            return this.params.field.key === this.getGenerateTo()?.key;
        },

        getGenerateFrom() {
            return this.params.field.generate_from || null;
        },

        getGenerateTo() {
            return this.params.field.generate_from ? this.params.field : null;
        },

        generateUrlKey() {
            let generate_from = this.getGenerateFrom();
            let generate_to = this.getGenerateTo();

            if (generate_from === 'uuid') {
                let uuid = this.generateUUID();
                this.updateValue(uuid);
                return;
            }

            let titleVal = document.getElementById(generate_from)?.value || '';
            let text = titleVal.split(" ").slice(0, 8).join(" ");

            let urlKey = slugify(text, { lower: true, strict: true });
            this.updateValue(generate_to.key === 'link' ? `/${urlKey}` : urlKey);
        },

        generateUUID() {
            return window.crypto?.randomUUID?.() || 'xxxxxxxx-xxxx-4xxx-yxxx-xxxxxxxxxxxx'.replace(/[xy]/g, c => {
                const r = Math.random() * 16 | 0;
                return (c === 'x' ? r : (r & 0x3 | 0x8)).toString(16);
            });
        }
    }
};
</script>
