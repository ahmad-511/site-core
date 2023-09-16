import { $$ } from "/js/main.js";
import EventEmitter from "/js/EventEmitter.js";

export default class Checklist {
    constructor(container, data, displayCol, valueCol, groupCol) {
        this.container = container;
        this.data = data;
        this.displayCol = displayCol;
        this.valueCol = valueCol;
        this.groupCol = groupCol;

        this.events = new EventEmitter();

        container.addEventListener('click', e => {
            if (e.target.tagName == 'INPUT') {
                const selectedData = this.data.find(item => item[this.valueCol] == e.target.value);
                const totalSelected = $$('input:checked', this.container).length;

                this.events.emit('item-selected', e.target, selectedData, totalSelected);
            }
        })
    }

    listen(event, callback){
        this.events.listen(event, callback);
    }

    render(data) {
        this.container.innerHTML = '';
        this.data = data || this.data || [];

        let group = '';

        // Sort data by groupCol
        if (this.groupCol) {
            this.data.sort((a, b) => {
                const aGroup = a[this.groupCol];
                const bGroup = b[this.groupCol];

                if (aGroup < bGroup) {
                    return -1;
                } else if (aGroup > bGroup) {
                    return +1;
                } else {
                    return 0
                }
            });
        }

        this.data.forEach(item => {
            if (this.groupCol && item[this.groupCol] != group) {
                const label = this.container.appendChild(document.createElement('label'));
                label.className = 'checklist-group';
                group = item[this.groupCol];
                label.innerHTML = group
            }

            const label = this.container.appendChild(document.createElement('label'));
            let display = item[this.displayCol];
            let value = item[this.valueCol];

            if (typeof this.displayCol == 'function') {
                display = this.displayCol(item);
            }

            if (typeof this.valueCol == 'function') {
                value = this.valueCol(item);
            }

            label.innerHTML = `<input type="checkbox" value="${value}"><span>${display}</span>`;
        });
    }

    setSelected(ids) {
        // Convert ids to string for none strict comparison
        ids = ids.map(id => id.toString());

        let checkedCount = 0;

        $$('input[type="checkbox"]', this.container).forEach(item => {
            const isFound = ids.indexOf(item.value) > -1;
            item.checked = isFound;
            if (isFound) {
                checkedCount++;
            }
        });

        return checkedCount;
    }

    getSelected() {
        const ids = Array.from($$('input[type="checkbox"]:checked', this.container)).reduce((acc, item) => {
            acc.push(item.value);

            return acc;
        }, []);

        return ids;
    }
}