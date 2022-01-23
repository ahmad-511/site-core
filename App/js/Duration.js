import EventEmitter from "/App/js/EventEmitter.js";

export class Component {

    constructor (name, value, min, max, step, required = false, label){
        this.name = name;
        this.value = value;
        this.min = min;
        this.max = max;
        this.step = step;
        this.required = required;
        this.label = label;
    }
}

export default class Duration {
    constructor(container, name = '', components = []){
        if(!(container instanceof HTMLElement)){
            console.log('HTMLElement is expected in container argument');
        }

        if(!(components instanceof Array)){
            console.log('Array of type Compenent is expected in components argument');
        }

        if((components instanceof Array) && components.length && !(components[0] instanceof Component)){
            console.log('Array of type Component is expected in components argument');
        }

        container.classList.add('duration-control')

        this.container = container;
        this.name = name;
        this.components = components;

        this.events = new EventEmitter();

        this.render();
    }
    
    render(){
        this.container.innerHTML = '';

        const handleChange = (value, component) => {
            this.events.emit('value-changed', value, component);
        }

        this.components.forEach(c => {
            const elem = document.createElement('input');
            elem.type = 'number';
            elem.name = c.name;
            elem.id = `${this.name}_${c.name}`;
            elem.setAttribute('value', c.value);
            elem.min = c.min;
            elem.max = c.max;
            elem.step = c.step;
            elem.required = c.required;
            elem.className = `duration-component ${c.name.toLowerCase()}`;
            c.element = elem;

            elem.addEventListener('change', e => {
                c.value = elem.value;
                handleChange(this.getValue(), c)
            });

            if(c.label){
                const label = document.createElement('label');
                label.textContent = `${c.label} `;
                label.appendChild(elem);
                this.container.appendChild(label);
            }else{
                this.container.appendChild(elem);
            }
        });
    }

    setValue(obj){
        Object.entries(obj).forEach(([key, value]) => {
            const comp = this.components.find(c => c.name == key);
            
            if(comp){
                comp.element.value = value;
                comp.element.dispatchEvent(new Event('change'));
            }
        });
    }

    getValue(){
        return this.components.reduce( (acc, curr) => {
            acc[curr.name] = curr.value;
            return acc;
        }, {});
    }

    getComponent(name){
        return this.components.find(c => c.name == name);
    }
}