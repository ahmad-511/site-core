export default class Validator {
    constructor() {
        this.validations = [];
        this.validationErrors = new Map();
    }

    add(elem, message, errorContainer, validationFunc) {
        this.validations.push({ elem, message, errorContainer, validationFunc });
    }

    clear() {
        this.validations.forEach(({ elem, errorContainer }) => {
            elem.setCustomValidity('');

            if (errorContainer instanceof HTMLElement) {
                errorContainer.textContent = '';
            }
        });
    }

    validate(excludeElems) {
        excludeElems = excludeElems || [];

        let isAllValid = true;
        this.clear();

        this.validationErrors.clear();

        this.validations.forEach(({ elem, message, errorContainer, validationFunc }) => {
            let isValidElement = true;
            let isValidFunction = true;

            if (excludeElems.indexOf(elem) > -1) {
                return;
            }

            // Check element validation attributes
            isValidElement = elem.validity.valid;

            // Check validation function
            if (typeof validationFunc == 'function') {
                isValidFunction = validationFunc(elem);
            }

            if (!(isValidElement && isValidFunction)) {
                isAllValid = false;

                if (this.validationErrors.has(elem)) {
                    message = [this.validationErrors.get(elem).message, message].join(', ');
                }

                this.validationErrors.set(elem, { message, errorContainer });
            }
        });

        // If we set a custom validity inside the validations loop above and we have more than one validity rule for an element then all validity messages will appear once one rule is broken
        // So we combine the messages one by one in the validations loop and set them all at once
        for (const [elem, { message, errorContainer }] of this.validationErrors) {
            elem.setCustomValidity(message);
            if (errorContainer instanceof HTMLElement) {
                errorContainer.textContent = message;
            }
        }

        return isAllValid;
    }

    getValidationErrors() {
        return new Map(this.validationErrors);
    }
}

// This is to support Safari on ios 13.3+, otherwise we can use static methods
Validator.inList = (list) => {
    return function (elem) {
        let options = [];

        if (typeof list == 'function') {
            options = list();
        } else {
            options = list;
        }

        return options.indexOf(elem.value) > -1;
    }
}

Validator.json = (elem) => {
    try {
        JSON.parse(elem.value)
        return true;
    }
    catch (ex) {
        return false;
    }
}