import { validationRules, errorMessages } from "./validationRules.js";
export default class OmniValidator {
    rules = validationRules;
    error_strings = errorMessages;
    errors = {};

    /**
     * @param {*} data should contain key value pairs
     * value of which should be an object with a value and rules key
     *
     * @returns true|false
     */
    async validate(data) {
        let failed = [];
        for (let key in data) {
            let field = data[key];
            let rules_to_validate = field.rules ? [...field.rules] : [];

            if (rules_to_validate.length === 0) {
                continue;
            }

            for (let rule of rules_to_validate) {
                let { rule_name, params } = await this.parse_rule(rule);

                let validator = this.rules[rule_name];

                if (
                    validator && field.value &&
                    !(await validator(field.value, params, data))
                ) {
                    let error_string =
                        this.error_strings[rule_name] ||
                        this.error_strings["default"];
                    this.errors[key] = error_string(key, params);

                    failed.push(key);
                }
            }
        }

        if (failed.length && failed.length >= 1) {
            console.log("Validation failed");
            return false;
        } else {
            console.log("Validation passed");
            return true;
        }
    }

    async parse_rule(rule) {
        if (rule.includes("::")) {
            let [rule_name, ...params] = rule.split("::");
            return {
                rule_name,
                params,
            };
        } else {
            let [rule_name, ...params] = rule.split(":");
            return {
                rule_name,
                params,
            };
        }
    }

    get_errors() {
        return this.errors;
    }
}
