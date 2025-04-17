<template>
    <slot name="label"></slot>

    <Editor :ref="'editor_'+params.dotted_name" v-model="value" :init="getConfig()" />

    <slot name="error"></slot>
</template>

<script>
import Editor from "@tinymce/tinymce-vue";


// Changed base_url to "{{ asset(" / ") }}" from "window.location.origin + "/" ".
export default {
    props: ["_value", "params"],
    inject: ["csrf_token"],
    data() {
        return {
            value: this._value,
            currentSkin: '',
            currentContentCSS: '',
            document_base_url: "{{ asset(" / ") }}",
            uploadRoute: window.location.origin + "/admin/tinymce/upload",
            csrfToken: this.csrf_token,
        };
    },
    components: {
        Editor,
    },
    updated() {
        this.$parent.updateData(this.value);
    },
    methods: {
        setSkins(){
            this.currentSkin = this.calcCurrentSkin();
            this.currentContentCSS = this.calcCurrentContentCSS();
        },
        // observeHtmlClassChange() {
        //     let observer = new MutationObserver((mutations) => {
        //         mutations.forEach((mutation) => {
        //             if (mutation.attributeName === "class") {
        //                 this.currentSkin = this.calcCurrentSkin();
        //                 this.currentContentCSS = this.calcCurrentContentCSS();
        //             }
        //         });
        //     });
        // },
        calcCurrentSkin() {
            return document.querySelector("html").classList.contains("dark")
                ? "oxide-dark"
                : "oxide";
        },

        calcCurrentContentCSS() {
            return document.querySelector("html").classList.contains("dark")
                ? "dark"
                : "default";
        },
        getConfig() {
            var self = this;
            var config = {
                plugins:
                    "image media wordcount save fullscreen code table lists link",
                menubar: "file edit view insert format tools table help",
                toolbar:
                    "formatselect | bold italic strikethrough forecolor backcolor image media alignleft aligncenter alignright alignjustify link hr numlist bullist outdent indent removeformat code table | fullscreen",
                skin: this.currentSkin,
                content_css: this.currentContentCSS,
                promotion: false,
                image_advtab: true,

                document_base_url: this.document_base_url,
                uploadRoute: this.uploadRoute,
                csrfToken: this.csrf_token,

                automatic_uploads: true,
                images_reuse_filename: true,
                images_upload_handler: (blobInfo, progress) =>
                    new Promise((resolve, reject) => {
                        self.uploadImageHandler(
                            blobInfo,
                            resolve,
                            reject,
                            progress
                        );
                    }),
                file_picker_callback: function (cb, value, meta) {
                    self.filePickerCallback(cb, value, meta);
                },
            };
            return config;
        },
        uploadImageHandler(blobInfo, resolve, reject, progress) {
            let xhr, formData;

            xhr = new XMLHttpRequest();

            xhr.withCredentials = false;

            xhr.open("POST", this.uploadRoute);

            xhr.upload.onprogress = (e) => progress((e.loaded / e.total) * 100);

            xhr.onload = function () {
                let json;

                if (xhr.status === 403) {
                    reject("@lang('admin::app.error.tinymce.http-error')", {
                        remove: true,
                    });

                    return;
                }

                if (xhr.status < 200 || xhr.status >= 300) {
                    reject("@lang('admin::app.error.tinymce.http-error')");

                    return;
                }

                json = JSON.parse(xhr.responseText);

                if (!json || typeof json.location != "string") {
                    reject(
                        "@lang('admin::app.error.tinymce.invalid-json')" +
                            xhr.responseText
                    );

                    return;
                }

                resolve(json.location);
            };

            xhr.onerror = () =>
                reject("@lang('admin::app.error.tinymce.upload-failed')");

            formData = new FormData();
            formData.append("_token", this.csrfToken);
            formData.append("file", blobInfo.blob(), blobInfo.filename());

            xhr.send(formData);
        },
        filePickerCallback: function (cb, value, meta) {
            let input = document.createElement("input");
            input.setAttribute("type", "file");
            input.setAttribute("accept", "image/*");

            input.onchange = function () {
                let file = this.files[0];

                let reader = new FileReader();
                reader.readAsDataURL(file);
                reader.onload = function () {
                    let id = "blobid" + new Date().getTime();
                    let blobCache = tinymce.activeEditor.editorUpload.blobCache;
                    let base64 = reader.result.split(",")[1];
                    let blobInfo = blobCache.create(id, file, base64);

                    blobCache.add(blobInfo);

                    cb(blobInfo.blobUri(), {
                        title: file.name,
                    });
                };
            };

            input.click();
        },
        reinitializeEditor() {   
            this.setSkins();
            this.$refs['editor_'+this.params.dotted_name].rerender(this.getConfig());
        },
    },
    created() {
        //console.log('this._value :',this._value);
        this.setSkins();
        window.addEventListener("change-theme", () => {
            this.reinitializeEditor();
        });
    },
    unmounted() {
        window.removeEventListener("change-theme", () => {});
    },
};
</script>
