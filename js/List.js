import { $ } from "/js/main.js";
import EventEmitter from "/js/EventEmitter.js";

export default class List {
    constructor(container, data, displayCol, valueCol, groupCol) {
        this.container = container;
        this.data = data;
        this.dataAdapter = null;
        this.displayCol = displayCol;
        this.valueCol = valueCol;
        this.groupCol = groupCol;
        this.displayField = null;
        this.valueField = null;
        this.searchField = null;
        this.selectedElement = null;
        this.selectedData = null;
        this.selectedIndex = -1;

        this.events = new EventEmitter();

        // Handling mouse selection
        this.container.addEventListener('click', e => {
            if (this.setSelectedElement(this.getSelectedElement(e.target))) {
                this.updateLinkedFields(this.selectedElement.dataset.display, this.selectedElement.dataset.value);

                this.events.emit('item-selected', this.selectedElement, this.selectedData);
            }
        });

        this.container.classList.add('no-items');
    }

    setDisplayField(elem) {
        if (!(elem instanceof HTMLElement)) {
            console.log('HTMLElement required');
        }

        this.displayField = elem;
    }

    setValueField(elem) {
        if (!(elem instanceof HTMLElement)) {
            console.log('HTMLElement required');
        }

        this.valueField = elem;
    }

    setSearchField(elem) {
        if (!(elem instanceof HTMLInputElement)) {
            console.log('HTMLInputElement required');
        }

        this.searchField = elem;

        this.searchField.addEventListener('input', e => {
            // Clear value field
            if (e.target.value.trim() == '') {
                this.updateLinkedFields('', '');
            }

            this.search(e.target.value);
        });

        // Handling keyboard selection
        this.searchField.addEventListener('keydown', e => {
            let isChanged = false;

            switch (e.code) {
                case 'ArrowUp':
                    this.selectedIndex--;

                    if (this.selectedIndex < 0) {
                        this.selectedIndex = 0;
                    }

                    this.setSelectedIndex(this.selectedIndex);

                    isChanged = true;
                    break;

                case 'ArrowDown':
                    this.selectedIndex++;

                    if (this.selectedIndex > this.data.length - 1) {
                        this.selectedIndex = this.data.length - 1;
                    }

                    this.setSelectedIndex(this.selectedIndex);

                    isChanged = true;
                    break;

                case 'Tab':
                    if (!this.selectedElement) {
                        return;
                    }

                    this.selectedElement.click();
                    break;

                case 'Enter':
                    if (!this.selectedElement) {
                        return;
                    }

                    this.selectedElement.click();

                    isChanged = true;
                    break;
            }

            if (isChanged) {
                e.preventDefault();
            }
        });
    }

    setSelectedElement(elem) {
        if (!elem) {
            this.selectedElement = null;
            this.selectedData = null;
            this.selectedIndex = -1;

            return false;
        }

        if (this.selectedElement) {
            this.selectedElement.classList.remove('selected');
        }

        this.selectedElement = elem;

        this.selectedElement.classList.add('selected');
        this.selectedData = this.data[this.selectedElement.dataset.id];
        this.selectedIndex = this.selectedElement.dataset.index;

        return true;
    }

    setSelectedIndex(index) {
        const elem = $(`[data-index="${index}"]`, this.container);

        return this.setSelectedElement(elem);
    }

    render(data, searchStr) {
        this.container.innerHTML = '';
        this.data = data || this.data || [];
        this.selectedIndex = -1;

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

        let index = 0;

        this.container.classList.remove('no-items');

        this.data.forEach((item, id) => {
            let display = item[this.displayCol];
            let value = item[this.valueCol];

            if (typeof this.displayCol == 'function') {
                display = this.displayCol(item);
            }

            if (typeof this.valueCol == 'function') {
                value = this.valueCol(item);
            }

            // Search
            if (this.dataAdapter === null && searchStr && display.toUpperCase().indexOf(searchStr.toUpperCase()) == -1) {
                return;
            }

            if (this.groupCol && item[this.groupCol] != group) {
                const label = this.container.appendChild(document.createElement('label'));
                label.className = 'list-group';
                group = item[this.groupCol];
                label.innerHTML = group
            }

            const label = this.container.appendChild(document.createElement('label'));

            // Use item index as an id (we can't depend on valueCol as it's possible to have none unique values)
            label.dataset.id = id;
            label.dataset.index = index++;
            label.dataset.display = display;
            label.dataset.value = value;
            label.innerHTML = display;
        });

        if (this.container.children.length == 0) {
            this.container.classList.add('no-items');
        }
    }

    getSelectedElement(target) {
        let elem = null;
        if (target.tagName == 'LABEL') {
            elem = target;
        } else {
            elem = target.closest('label');
        }

        if (elem && elem.classList.contains('list-group')) {
            elem = null;
        }

        return elem;
    }

    getItemByField(field, value) {
        return this.data.find(i => i[field] == value);
    }

    getValues() {
        return this.data.map(i => i[this.valueCol]);
    }

    search(str) {
        if (typeof this.dataAdapter == 'function') {
            this.dataAdapter(str, data => {
                this.render(data, str);
            });
        } else {
            this.render(this.data, str);
        }
    }

    updateLinkedFields(display, value) {
        if (this.displayField) {
            if (this.displayField instanceof HTMLInputElement) {
                this.displayField.value = display;
            } else {
                this.displayField.textContent = display;
            }
        }

        if (this.valueField) {
            if (this.valueField instanceof HTMLInputElement) {
                this.valueField.value = value;
            } else {
                this.valueField.textContent = value;
            }
        }
    }
}