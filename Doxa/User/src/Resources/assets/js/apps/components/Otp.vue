<template>
    <div class="flex justify-start gap-2">
        <input v-for="(val, index) in length" :key="index" v-model="code[index]" :ref="`code[${index}]`" :i="index"
            @keyup="isNumber($event)" @keypress="isNumber($event)" @paste="paste($event)"
            @input="inputNumber($event, index)"
            class="w-10 p-1 text-lg text-center form-input code-input focus:bg-gray-200 focus:dark:text-black"
            maxlength="1" />
    </div>
</template>

<script>
export default {
    props: {
        length: {
            type: Number,
            default: 6,
        }
    },
    data() {
        return {
            code: [],
        };
    },
    methods: {
        paste(event) {
            event.preventDefault();
            let pasted_value = event.clipboardData.getData('text');

            if (!pasted_value) {
                return;
            }

            if (isNaN(pasted_value)) {
                return;
            }

            if (pasted_value && pasted_value.length === this.length) {
                this.code = pasted_value.split('');
                this.$emitter.emit('set-otp', this.code.join(''));

            }

            return;
        },
        isNumber: function (evt) {
            evt = (evt) ? evt : window.event;
            var charCode = (evt.which) ? evt.which : evt.keyCode;
            if ((charCode > 31 && (charCode < 48 || charCode > 57)) && charCode !== 46) {
                evt.preventDefault();;
            } else {
                return true;
            }
        },
        inputNumber(event, index) {
            let value = event.data || "";
            console.log('value: ', value);
            this.code[index] = value;
            if (!value) {
                if (index > 0) {
                    this.$refs[`code[${index - 1}]`][0].focus();
                } else if (index === 0) {
                    this.$refs[`code[${index}]`][0].focus();
                }
            }
            else if (value.length > 1) {
                console.log('value.length > 1 => ', value.length);
                this.code = value.split();
                this.code = [...value];
                //console.log('this.code: ', this.code);
                for (let i = 0; i < this.length; i++) {
                    this.$refs[`code[${i}]`][0].blur();
                }
            } else {
                if (index < this.length - 1) {
                    this.$refs[`code[${index + 1}]`][0].focus();
                } else {
                    this.$refs[`code[${index}]`][0].blur();
                }
            }
            this.$emitter.emit('set-otp', this.code.join(''));
        },
        clear() {
            this.code = [];
        },
    },
    mounted() {
        this.$refs[`code[0]`][0].focus();
        this.$emitter.on('clear-otp', this.clear);
    },
    unmounted() {
        this.$emitter.off('clear-otp', this.clear);
    }
}

</script>
