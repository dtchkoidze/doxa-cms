<template>
    <slot name="label"></slot>
    <input type="text" v-model="value" :id="this.params.field.key" class="w-full form-input">
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
    props: ['_value', 'params'],
    data() {
        return {
            value: this._value
        }
    },
    updated() {
        this.$parent.updateData(this.value);
    },
    methods: {
        isGenerateTarget() {
            let target = this.getGenerateTo();
            let targetKey;
            if (target) {
                targetKey = target.key;
            } else {
                targetKey = null;
            }
            return targetKey == this.params.field.key;
        },
        getGenerateFrom() {
            if (this.params.field.generate_from) {
                return this.params.field.generate_from;
            } else {
                return null;
            }
        },
        getGenerateTo() {
            if (this.params.field.generate_from) {
                return this.params.field;
            } else {
                return null;
            }
        },

        generateUrlKey() {
            let generate_from = this.getGenerateFrom();
            let generate_to = this.getGenerateTo();

            if (generate_from == 'uuid') {
                let uuid = this.generateUUID();
                this.value = uuid;
                return;
            }

            let generate_from_key = generate_from;
            let generate_to_key = generate_to.key;

            let titleVal = document.getElementById(generate_from_key).value;
            let maxWordCount = 8;

            let words = titleVal.split(" ").slice(0, maxWordCount);

            titleVal = words.join(" ");

            let text;
            if (this.$parent.locale_id) {
                // this needs proper handling to construct method name based on locale code
                // example: 
                let locale_code;
                if (locale_code) {
                    let method_name = `transliterate_${locale_code}`
                    text = this.method_name(titleVal);
                }
            } else {
                text = titleVal;
            }


            let urlKey = slugify(text,
                {
                    lower: true,
                    strict: true,
                },
            );

            this.value = urlKey;

            if (this.params.field.key == 'link') {
                this.value = `/${this.value}`;
            }
        },

        transliterate_ge(string) {
            let dictionary = {
                ა: "a",
                ბ: "b",
                გ: "g",
                დ: "d",
                ე: "e",
                ვ: "v",
                ზ: "z",
                თ: "th",
                ი: "i",
                კ: "k",
                ლ: "l",
                მ: "m",
                ნ: "n",
                ო: "o",
                პ: "p",
                ჟ: "zh",
                რ: "r",
                ს: "s",
                ტ: "t",
                უ: "u",
                ფ: "f",
                ქ: "q",
                ღ: "gh",
                ყ: "k",
                შ: "sh",
                ჩ: "ch",
                ც: "ts",
                ძ: "dz",
                წ: "ts",
                ჭ: "tch",
                ხ: "kh",
                ჯ: "j",
                ჰ: "h",
                $: "dolari",
                '%': "protsenti",
                '&': "da",
            };
            return string
                .split("")
                .map((char) => dictionary[char] || char)
                .join("");
        },
        generateUUID() {
            if (window.crypto && window.crypto.randomUUID) {
                return window.crypto.randomUUID();
            }

            return 'xxxxxxxx-xxxx-4xxx-yxxx-xxxxxxxxxxxx'.replace(/[xy]/g, function (c) {
                const r = (Math.random() * 16) | 0;
                const v = c === 'x' ? r : (r & 0x3) | 0x8;
                return v.toString(16);
            });
        }

    },

};
</script>