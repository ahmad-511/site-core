export default class Validator {
    constructor(){ 
        this.validations = [];
    }

    static inList(list){
        return function (elem){
            let options = [];

            if(typeof list == 'function'){
                options = list();
            }else{
                options = list;
            }
            
            return options.indexOf(elem.value) > -1;
        }
    }

    add(elem, message, errorContainer, validationFunc){
        this.validations.push({elem, message, errorContainer, validationFunc});
    }

    clear(){
        this.validations.forEach(({elem, errorContainer}) => {
            elem.setCustomValidity('');
            
            if(errorContainer instanceof HTMLElement){
                errorContainer.textContent = '';
            }
        });
    }

    validate(excludeElems){
        excludeElems = excludeElems || [];

        let isAllValid = true;
        this.clear();

        this.validations.forEach(({elem, message, errorContainer, validationFunc}) => {
            let isValidElement = true;
            let isValidFunction = true;

            if(excludeElems.indexOf(elem) > -1){
                return;
            }

            // Check element validation attributes
            isValidElement = elem.validity.valid;
            
            // Check validation function
            if(typeof validationFunc == 'function'){
                isValidFunction = validationFunc(elem);
            }
            
            if(!(isValidElement && isValidFunction)){
                isAllValid = false;

                elem.setCustomValidity(message);
                if(errorContainer instanceof HTMLElement){
                    errorContainer.textContent = message;
                }
            }
        });

        return isAllValid;
    }
}