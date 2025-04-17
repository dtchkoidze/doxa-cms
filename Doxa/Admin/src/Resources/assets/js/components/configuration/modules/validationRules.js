export const validationRules = {
    required: (value) => {
        // console.log("Validating required:", { value });
        return value !== "" && value !== null && value !== undefined;
    },

    max: (value, param) => {
        // console.log("Validating max:", { value, param });
        return value.length <= parseInt(param, 10);
    },

    min: (value, param) => {
        // console.log("Validating min:", { value, param });
        return value.length >= parseInt(param, 10);
    },

    requiredIf: (value, params, all_fields) => {
        // console.log("params: ", params);

        // console.log("all_fields: ", all_fields);

        let [ref_field, expected_val] = String(params).split(",");
        let ref_value = all_fields[ref_field]?.value;
        console.log("Validating requiredIf:", {
            value,
            ref_field,
            expected_val,
            ref_value,
            all_fields,
        });

        let ref_value_is_expected = ref_value == expected_val;
        let is_value_set = value != "";
        return ref_value_is_expected && is_value_set;
    },

    string: (value) => {
        // console.log("Validating string:", { value });
        return typeof value === "string";
    },

    number: (value) => {
        // console.log("Validating number:", { value });
        return typeof value === "number";
    },

    integer: (value) => {
        // console.log("Validating integer:", { value });
        return Number.isInteger(value);
    },

    between: (value, params) => {
        let [min, max] = String(params).split(",");
        console.log("value: ", value);
        let failed_imgs = [];
        for (let v of value) {
            console.log("v: ", v);
            let file = v.file;
            let size;
            if (file && file instanceof File) {
                size = file.size / 1024;
                console.log("size is: ", size);
            } else {
                size = value.length;
            }

            if (size < min || size > max) {
                failed_imgs.push(file.name);
            }
        }

        return failed_imgs.length === 0;
    },

    dimensions: (value, params) => {
        let dims = String(params).split("=");
        let [ratio_x, ratio_y] = dims[1].split("/");
        console.log("ratio_x: ", ratio_x);
        console.log("ratio_y: ", ratio_y);
        return new Promise((resolve, reject) => {
            if (!value || value.length === 0) {
                return resolve(true);
            }

            let promises = value.map((v) => {
                return new Promise((res, rej) => {
                    let file = v.file;
                    console.log("File: ", file);
                    if (!(file instanceof File)) {
                        return res(true);
                    }

                    let reader = new FileReader();
                    reader.onload = (e) => {
                        let img = new Image();
                        img.src = e.target.result;
                        img.onload = () => {
                            console.log(
                                `Image Dimensions: ${img.width}x${img.height}`
                            );
                            let actual_ratio = img.width / img.height;
                            let expected_ratio =
                                Number(ratio_x) / Number(ratio_y);

                            console.log("actual_ratio: ", actual_ratio);
                            console.log("expected: ", expected_ratio);
                            if (
                                Math.abs(actual_ratio - expected_ratio) < 0.01
                            ) {
                                res(true);
                            } else {
                                res(false);
                            }
                        };
                    };
                    reader.onerror = rej;
                    reader.readAsDataURL(file);
                });
            });

            Promise.all(promises)
                .then((results) => {
                    console.log("results: ", results);
                    resolve(results.every((r) => r === true));
                })
                .catch(() => resolve(false));
        });
    },
};

export const errorMessages = {
    required: (field) => `${field} is required`,
    max: (field, param) => `${field} should be less than ${param} characters`,
    min: (field, param) => `${field} should be more than ${param} characters`,
    requiredIf: (field, params) =>
        `${field} is required when ${params[0]} is ${params[1]}`,
    string: (field) => `${field} should be a string`,
    number: (field) => `${field} should be a number`,
    integer: (field) => `${field} should be an integer`,
    between: (field, params) =>
        `${field} size shoud be between ${params.toString()} KiloBytes`,
    dimensions: (field, params) => `${field} dimensions should match ${params}`,
    default: (field) => `Validation failed for ${field}`,
};
