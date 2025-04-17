<template>
    <slot name="label"></slot>

    <div class="grid mt-4">
        <div class="flex flex-wrap gap-1">
            <template v-if="isAddAllowed()">
                <label
                    class="grid h-[120px] max-h-[120px] min-h-[110px] w-full min-w-[110px] max-w-[120px] cursor-pointer items-center justify-items-center rounded border border-dashed border-gray-300 transition-all hover:border-gray-400 dark:border-gray-800 dark:mix-blend-exclusion dark:invert"
                    style="max-width: 120px; max-height: 120px" :for="'new_imageInput_' + params.field.key">
                    <div class="flex flex-col items-center">
                        <span class="text-2xl icon-image"></span>
                        <p class="grid text-sm font-semibold text-center text-gray-600 dark:text-gray-300">
                            Add Image
                            <span class="text-xs"> png, jpeg, jpg </span>
                        </p>
                        <input type="file" class="hidden" :id="'new_imageInput_' + params.field.key" accept="image/*"
                            multiple="" @change="add" />
                    </div>
                </label>
            </template>

            <div v-if="images" id="sortableList" class="flex flex-wrap gap-1">
                <template v-for="image in images" :key="'image'+image.id">
                    <div
                        class="d-drag cursor-grabbing group relative grid max-h-[120px] min-w-[120px] justify-items-center overflow-hidden rounded transition-all hover:border-gray-400">
                        <!-- Image Preview -->
                        <img :src="image.url" :style="{ width: this.width, height: this.height }" />

                        <div
                            class="absolute top-0 bottom-0 flex flex-col justify-between invisible w-full p-3 transition-all bg-white opacity-80 group-hover:visible dark:bg-gray-900">
                            <!-- Image Name -->
                            <p class="text-xs font-semibold text-gray-600 break-all dark:text-gray-300"></p>

                            <!-- Actions -->
                            <div class="flex justify-between">
                                <span
                                    class="cursor-pointer rounded-md p-1.5 text-2xl hover:bg-gray-200 dark:hover:bg-gray-800"
                                    @click="remove(image)">
                                    <i class="fa-solid fa-trash"></i>
                                </span>

                                <label
                                    class="cursor-pointer rounded-md p-1.5 text-2xl hover:bg-gray-200 dark:hover:bg-gray-800"
                                    :for="image.id">
                                    <i class="fa-solid fa-pen-to-square"></i>
                                </label>

                                <input type="hidden" v-if="!image.is_new" />

                                <input type="file" :name="params.field.key" class="hidden uploaded_image"
                                    accept="image/*" multiple="" :id="image.id" :ref="`imageInput_` + image.id"
                                    @change="edit" />
                            </div>
                        </div>
                    </div>
                </template>
            </div>
        </div>
    </div>

    <slot name="error"></slot>
</template>

<script>
export default {
    props: {
        _value: Array | String,
        params: Object,
        width: {
            type: String,
            default: "120px",
        },

        height: {
            type: String,
            default: "120px",
        },
    },
    data() {
        return {
            images: this._value ? this._value : [],
            set: this._set,
            assocImages: {},
        };
    },
    // created() {
    //     console.log('this._value :',this._value);
    //     console.log('this.images :',this.images);
    // },
    updated() {
        console.log('updated');
        //this.addSortable();
    },
    mounted() {
        // console.log('2 this._value :',this._value);
        // console.log('2 this.images :',this.images);
        this.updateData();
        this.updateIndex();
        this.addSortable();
    },
    methods: {
        isAddAllowed() {
            var limit = 1;
            if (this.params.field.multiple) {
                if (this.params.field.limit) {
                    limit = this.params.field.limit;
                } else {
                    if (this.params.field.multiple === true) {
                        return true;
                    }
                    var _limit = parseInt(this.params.field.multiple);
                    if (!isNaN(_limit)) {
                        limit = _limit;
                    }
                }
            }
            return this.images.length < limit;

        },
        addSortable() {
            var el = document.getElementById('sortableList');
            if (el) {
                let obj = this;
                var sortable = Sortable.create(el, {
                    handle: '.d-drag',
                    animation: 150,
                    onEnd: function (event) {
                        const container = document.querySelector("#sortableList");
                        const _images = container.querySelectorAll(".uploaded_image");
                        obj.images = [];
                        _images.forEach((node) => {
                            var id = node.id;
                            obj.images.push(obj.assocImages[id]);
                        })
                        obj.updateData();
                    },
                });
            }
        },
        updateData() {
            this.$parent.updateData(this.images);
        },
        updateIndex() {
            this.assocImages = {};
            for (var i = 0; i < this.images.length; i++) {
                this.assocImages[this.images[i].id] = this.images[i];
            }
        },
        remove(image) {
            let index = this.images.indexOf(image);
            this.images.splice(index, 1);
            this.updateData();
        },
        edit(target) {
            let imageInput = target.originalTarget;
            let id = imageInput.getAttribute('id');

            if (!this.check(imageInput)) {
                return;
            }

            for (var i = 0; i < this.images.length; i++) {
                if (this.images[i].id == id) {
                    var current_image_i = i;
                    break;
                }
            }

            this.images.splice(current_image_i, 1);

            imageInput.files.forEach((file, index) => {
                var obj = this;
                var reader = new FileReader();
                reader.onloadend = function (e) {
                    var id = obj.getRndId();
                    var img = {
                        type: 'image',
                        id: id,
                        url: e.target.result,
                        file: file
                    };
                    obj.images.splice(current_image_i, 0, img);
                    obj.assocImages[id] = img;
                };
                reader.readAsDataURL(file);
            });

            this.updateData();
        },
        add(target) {
            let imageInput = target.target;

            console.log('imageInput: ', imageInput);
            console.log('imageInput.files: ', imageInput.files);
            console.log('typeof imageInput.files: ', typeof imageInput.files);

            if (!this.check(imageInput)) {
                return;
            }

            for (let i = 0; i < imageInput.files.length; i++) {
                let file = imageInput.files[i];
                console.log(file);

                let obj = this;
                let reader = new FileReader();
                reader.onloadend = function (e) {
                    let id = obj.getRndId();
                    let img = {
                        type: 'image',
                        id: id,
                        url: e.target.result,
                        file: file
                    };
                    obj.images.push(img);
                    obj.assocImages[id] = img;
                };

                console.info('reader: ', reader);
                console.info("obj.images: ", obj.images);
                reader.readAsDataURL(file);
            }

            this.updateData();

            // imageInput.files.forEach((file, index) => {

            // });

            // console.log('------------1------------');

            // imageInput.files.forEach((file, index) => {
            //     var obj = this;
            //     var reader = new FileReader();
            //     reader.onloadend = function (e) {
            //         var id = obj.getRndId();
            //         var img = {
            //             type: 'image',
            //             id: id,
            //             url: e.target.result,
            //             file: file
            //         };
            //         obj.images.push(img);
            //         obj.assocImages[id] = img;
            //     };
            //     reader.readAsDataURL(file);
            // });

            // this.updateData();
        },
        check(imageInput) {

            if (imageInput.files == undefined) {
                return false;
            }

            const validFiles = Array.from(imageInput.files).every(file => file.type.includes('image/'));

            if (!validFiles) {
                this.$emitter.emit('add-flash', {
                    type: 'warning',
                    message: "Uploaded file is not an image"
                });
                return false;
            }

            return true;
        },
        getRndId() {
            return 'image_' + (Math.random() * 10000);
        },
    },
};
</script>
